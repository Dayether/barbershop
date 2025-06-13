<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Check for appointment ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: my_appointments.php');
    exit;
}

require_once 'database.php';

$appointment_id = intval($_GET['id']);
$user_id = $_SESSION['user']['user_id'];
$errors = [];
$success_message = '';

$db = new Database();

// Get appointment details - only for the logged-in user
$appointment = $db->getAppointmentForReschedule($appointment_id, $user_id);
if (!$appointment) {
    header('Location: my_appointments.php?error=not_found');
    exit;
}

// Process form submission for rescheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get new date and time
    $new_date = $_POST['appointment-date'] ?? '';
    $new_time = $_POST['appointment-time'] ?? '';

    // Validation
    if (empty($new_date)) {
        $errors[] = 'Please select a new appointment date';
    }

    if (empty($new_time)) {
        $errors[] = 'Please select a new appointment time';
    }

    // Double-booking check and update
    if (empty($errors)) {
        $result = $db->rescheduleAppointment($appointment, $appointment_id, $user_id, $new_date, $new_time);
        if ($result['success']) {
            $success_message = 'Your appointment has been successfully rescheduled!';
            $appointment['appointment_date'] = $new_date;
            $appointment['appointment_time'] = $new_time;
        } else {
            $errors[] = $result['error_message'];
        }
    }
}

// Get available barbers
$barbers = $db->getActiveBarbers();

// For demonstration purposes - available time slots
function generateTimeSlots() {
    $slots = [];
    $start = 9 * 60; // 9:00 AM in minutes
    $end = 17 * 60 + 30; // 5:30 PM in minutes
    $interval = 30; // 30 minute intervals
    
    for ($time = $start; $time <= $end; $time += $interval) {
        $hour = floor($time / 60);
        $minute = $time % 60;
        
        // Skip lunch break (12:00 - 13:00)
        if ($hour == 12) {
            continue;
        }
        
        $formattedTime = sprintf('%02d:%02d', $hour, $minute);
        $slots[] = $formattedTime;
    }
    
    return $slots;
}

$available_times = generateTimeSlots();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/appointment.css">
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_gold.css">
    <!-- Add iziToast CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <style>
        /* Some additional styles specific to this page */
        .current-appointment {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--secondary-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .current-details {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .detail-group {
            flex: 1;
            min-width: 200px;
        }
        
        .detail-group h4 {
            font-size: 0.95rem;
            color: var(--text-light);
            margin-bottom: 5px;
        }
        
        .detail-group p {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .reschedule-form-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .form-title {
            margin-bottom: 25px;
            color: var(--secondary-color);
        }
        
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .time-slot {
            position: relative;
        }
        
        .time-slot input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        
        .time-slot label {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 44px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .time-slot input:checked + label {
            background-color: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }
        
        .time-slot label:hover {
            background-color: rgba(42, 157, 143, 0.1);
            border-color: var(--secondary-color);
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .no-slots-message {
            grid-column: 1 / -1;
            padding: 20px;
            text-align: center;
            color: var(--text-light);
            background-color: #f8f9fa;
            border-radius: 6px;
        }
        
        @media (max-width: 768px) {
            .time-slots {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            }
            .form-actions {
                flex-direction: column;
                gap: 15px;
            }
            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>Reschedule Appointment</h1>
            <p>Change your appointment to a different date and time</p>
        </div>
    </section>

    <!-- Reschedule Section -->
    <section class="appointment-section">
        <div class="container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message) ?>
                    <p>You will be redirected to your appointments page in <span id="countdown">5</span> seconds.</p>
                </div>
            <?php endif; ?>
            
            <div class="current-appointment">
                <h3>Current Appointment Details</h3>
                <div class="current-details">
                    <div class="detail-group">
                        <h4>Service</h4>
                        <p><?= htmlspecialchars($appointment['service_name'] ?? 'N/A') ?></p>
                    </div>
                    <div class="detail-group">
                        <h4>Date</h4>
                        <p><?= date('l, F j, Y', strtotime($appointment['appointment_date'])) ?></p>
                    </div>
                    <div class="detail-group">
                        <h4>Time</h4>
                        <p><?= date('g:i A', strtotime($appointment['appointment_time'])) ?></p>
                    </div>
                    <div class="detail-group">
                        <h4>Barber</h4>
                        <p><?= htmlspecialchars($appointment['barber_name'] ?? 'Not assigned') ?></p>
                    </div>
                </div>
            </div>
            
            <?php if (!$success_message): ?>
                <div class="reschedule-form-container">
                    <h3 class="form-title">Select New Date & Time</h3>
                    
                    <form method="post" id="reschedule-form">
                        <div class="form-group">
                            <label for="appointment-date">New Date</label>
                            <div class="input-icon-wrapper">
                                <i class="far fa-calendar-alt input-icon"></i>
                                <input type="text" id="appointment-date" name="appointment-date" placeholder="Select a new date" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>New Time</label>
                            <div class="time-slots" id="time-slots">
                                <div class="time-slots-loading">
                                    <i class="fas fa-spinner fa-pulse"></i>
                                    <span>Please select a date first</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="my_appointments.php" class="btn btn-outline">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="reschedule-btn">Reschedule Appointment</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Add iziToast JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize iziToast
            iziToast.settings({
                timeout: 5000,
                resetOnHover: true,
                position: 'topRight',
                transitionIn: 'flipInX',
                transitionOut: 'flipOutX',
            });
            
            <?php if (!empty($errors)): ?>
                iziToast.error({
                    title: 'Error',
                    message: '<?= htmlspecialchars($errors[0]) ?>',
                    icon: 'fas fa-exclamation-circle'
                });
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                iziToast.success({
                    title: 'Success',
                    message: 'Your appointment has been rescheduled successfully!',
                    icon: 'fas fa-check-circle'
                });
                
                // Countdown for redirect
                let seconds = 5;
                const countdownElement = document.getElementById('countdown');
                
                const countdownInterval = setInterval(() => {
                    seconds--;
                    if (countdownElement) {
                        countdownElement.textContent = seconds;
                    }
                    
                    if (seconds <= 0) {
                        clearInterval(countdownInterval);
                        window.location.href = 'my_appointments.php?rescheduled=success';
                    }
                }, 1000);
            <?php endif; ?>
            
            <?php if (!$success_message): ?>
                // Initialize flatpickr date picker
                const today = new Date();
                const maxDate = new Date();
                maxDate.setDate(today.getDate() + 30); // Allow booking up to 30 days in advance
                
                const fp = flatpickr('#appointment-date', {
                    minDate: 'today',
                    maxDate: maxDate,
                    disable: [
                        function(date) {
                            // Disable Sundays (0 is Sunday)
                            return date.getDay() === 0;
                        }
                    ],
                    locale: {
                        firstDayOfWeek: 1 // Monday
                    },
                    onChange: function(selectedDates, dateStr) {
                        if (dateStr) {
                            loadTimeSlots(dateStr);
                            
                            iziToast.info({
                                title: 'Date Selected',
                                message: 'Loading available time slots...',
                                icon: 'fas fa-calendar-alt',
                                iconColor: '#2a9d8f'
                            });
                        }
                    }
                });

                // Function to load time slots based on selected date
                function loadTimeSlots(date) {
                    const timeSlotsContainer = document.getElementById('time-slots');
                    timeSlotsContainer.innerHTML = '<div class="time-slots-loading"><i class="fas fa-spinner fa-pulse"></i><span>Loading available time slots...</span></div>';
                    
                    // Simulate loading time slots (in a real app, this would be an AJAX call)
                    setTimeout(() => {
                        const availableTimes = <?= json_encode($available_times) ?>;
                        const currentDate = "<?= $appointment['appointment_date'] ?>";
                        const currentTime = "<?= $appointment['appointment_time'] ?>";
                        
                        // Filter out slots if same date as current appointment
                        let slots = availableTimes;
                        if (date === currentDate) {
                            slots = availableTimes.filter(time => time !== currentTime);
                        }
                        
                        // For demo, randomly remove some slots to simulate availability
                        slots = slots.filter(() => Math.random() > 0.3);
                        
                        if (slots.length > 0) {
                            timeSlotsContainer.innerHTML = '';
                            
                            slots.forEach(time => {
                                const timeSlot = document.createElement('div');
                                timeSlot.className = 'time-slot';
                                timeSlot.innerHTML = `
                                    <input type="radio" name="appointment-time" id="time-${time.replace(':', '')}" value="${time}" required>
                                    <label for="time-${time.replace(':', '')}">
                                        ${formatTime(time)}
                                    </label>
                                `;
                                timeSlotsContainer.appendChild(timeSlot);
                            });
                            
                            // Add event listeners for time slot selection
                            document.querySelectorAll('.time-slot input').forEach(input => {
                                input.addEventListener('change', function() {
                                    if (this.checked) {
                                        const formattedTime = formatTime(this.value);
                                        
                                        iziToast.info({
                                            title: 'Time Selected',
                                            message: `${formattedTime}`,
                                            icon: 'fas fa-clock',
                                            iconColor: '#2a9d8f',
                                            timeout: 2000
                                        });
                                    }
                                });
                            });
                        } else {
                            timeSlotsContainer.innerHTML = '<div class="no-slots-message"><i class="fas fa-exclamation-circle"></i> No available time slots for this date. Please select another date.</div>';
                            
                            iziToast.warning({
                                title: 'No Available Slots',
                                message: 'Please select another date for your appointment',
                                icon: 'fas fa-calendar-times'
                            });
                        }
                    }, 1000);
                }

                // Helper function to format time for display
                function formatTime(timeString) {
                    const [hours, minutes] = timeString.split(':');
                    let period = 'AM';
                    let hour = parseInt(hours);
                    
                    if (hour >= 12) {
                        period = 'PM';
                        if (hour > 12) hour -= 12;
                    }
                    if (hour === 0) hour = 12;
                    
                    return `${hour}:${minutes} ${period}`;
                }
                
                // Handle form submission
                document.getElementById('reschedule-form').addEventListener('submit', function(e) {
                    const dateInput = document.getElementById('appointment-date');
                    const timeInputs = document.querySelectorAll('input[name="appointment-time"]');
                    let timeSelected = false;
                    
                    timeInputs.forEach(input => {
                        if (input.checked) {
                            timeSelected = true;
                        }
                    });
                    
                    if (!dateInput.value) {
                        e.preventDefault();
                        iziToast.warning({
                            title: 'Date Required',
                            message: 'Please select a date for your appointment',
                            icon: 'fas fa-calendar-alt'
                        });
                        return false;
                    }
                    
                    if (!timeSelected) {
                        e.preventDefault();
                        iziToast.warning({
                            title: 'Time Required',
                            message: 'Please select a time slot for your appointment',
                            icon: 'fas fa-clock'
                        });
                        return false;
                    }
                    
                    // Show processing toast
                    const submitBtn = document.getElementById('reschedule-btn');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    
                    iziToast.info({
                        title: 'Rescheduling',
                        message: 'Processing your appointment change...',
                        icon: 'fas fa-spinner fa-spin',
                        timeout: false,
                        id: 'reschedule-toast'
                    });
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>
                       
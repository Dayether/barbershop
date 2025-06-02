<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connection.php';

$user_id = $_SESSION['user']['id'];
$error_message = '';
$success_message = '';
$appointment = null;
$barbers = [];
$time_slots = [];

// Check if appointment ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my_appointments.php');
    exit;
}

$appointment_id = $_GET['id'];

// Fetch the appointment details
$stmt = $conn->prepare("SELECT a.*, s.name as service_name, s.duration, s.price, b.name as barber_name 
                        FROM appointments a 
                        LEFT JOIN services s ON a.service = s.id 
                        LEFT JOIN barbers b ON a.barber = b.id 
                        WHERE a.id = ? AND a.user_id = ? AND (a.status = 'pending' OR a.status = 'confirmed')");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Appointment not found or not eligible for rescheduling
    header('Location: my_appointments.php');
    exit;
}

$appointment = $result->fetch_assoc();
$stmt->close();

// Fetch all barbers - removing invalid "status" column reference
$stmt = $conn->prepare("SELECT * FROM barbers");  // Remove the WHERE clause with the non-existent status column
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $barbers[] = $row;
}
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_date = $_POST['appointment_date'] ?? '';
    $new_time = $_POST['appointment_time'] ?? '';
    $new_barber = $_POST['barber'] ?? $appointment['barber'];
    $notes = $_POST['notes'] ?? '';
    
    // Validate inputs
    if (empty($new_date) || empty($new_time)) {
        $error_message = "Please select both date and time for your appointment.";
    } else {
        // Check if the selected time slot is available
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments 
                                WHERE appointment_date = ? 
                                AND appointment_time = ? 
                                AND barber = ? 
                                AND status IN ('pending', 'confirmed')
                                AND id != ?");
        $stmt->bind_param("ssii", $new_date, $new_time, $new_barber, $appointment_id);
        $stmt->execute();
        $slot_check = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($slot_check['count'] > 0) {
            $error_message = "Sorry, this time slot is already booked. Please select another time.";
        } else {
            // Start a transaction for data integrity
            $conn->begin_transaction();
            
            try {
                // Update the appointment - without updated_at column
                $stmt = $conn->prepare("UPDATE appointments SET 
                                    appointment_date = ?, 
                                    appointment_time = ?, 
                                    barber = ?, 
                                    notes = ? 
                                    WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ssssii", $new_date, $new_time, $new_barber, $notes, $appointment_id, $user_id);
                $stmt->execute();
                
                // Verify the update was successful
                if ($stmt->affected_rows > 0) {
                    // Try to log to appointment_history if it exists
                    try {
                        $history_note = "Appointment rescheduled by customer to " . date('F j, Y', strtotime($new_date)) . " at " . date('g:i A', strtotime($new_time));
                        $stmt = $conn->prepare("INSERT INTO appointment_history (appointment_id, action, notes, created_at) VALUES (?, 'reschedule', ?, NOW())");
                        $stmt->bind_param("is", $appointment_id, $history_note);
                        $stmt->execute();
                    } catch (Exception $e) {
                        // History table might not exist, but that shouldn't stop the reschedule
                        error_log("Notice: appointment_history table not available - " . $e->getMessage());
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    $success_message = "Your appointment has been successfully rescheduled!";
                    
                    // Update the appointment object to show the new details
                    $appointment['appointment_date'] = $new_date;
                    $appointment['appointment_time'] = $new_time;
                    $appointment['barber'] = $new_barber;
                    $appointment['notes'] = $notes;
                    
                    // Find the barber name for display
                    foreach ($barbers as $b) {
                        if ($b['id'] == $new_barber) {
                            $appointment['barber_name'] = $b['name'];
                            break;
                        }
                    }
                } else {
                    // If no rows affected, roll back and show error
                    $conn->rollback();
                    $error_message = "No changes were made. The appointment could not be rescheduled.";
                }
                
                $stmt->close();
                
            } catch (Exception $e) {
                // Roll back transaction on error
                $conn->rollback();
                $error_message = "Database error: " . $e->getMessage();
                error_log("Appointment reschedule error: " . $e->getMessage());
            }
        }
    }
}

// Generate available time slots
$open_hour = 9; // 9 AM
$close_hour = 19; // 7 PM
$interval = 30; // 30 minutes per slot

for ($hour = $open_hour; $hour < $close_hour; $hour++) {
    for ($minute = 0; $minute < 60; $minute += $interval) {
        $time = sprintf("%02d:%02d:00", $hour, $minute);
        $time_slots[] = $time;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/appointment.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>Reschedule Appointment</h1>
            <p>Make changes to your existing appointment</p>
        </div>
    </section>

    <section class="profile-page">
        <div class="container">
            <div class="profile-tabs">
                <a href="profile.php" class="tab">
                    <i class="fas fa-user"></i> <span>Profile Information</span>
                </a>
                <a href="profile.php#security" class="tab">
                    <i class="fas fa-lock"></i> <span>Security</span>
                </a>
                <a href="my_appointments.php" class="tab active">
                    <i class="fas fa-calendar-alt"></i> <span>My Appointments</span>
                </a>
                <a href="profile.php#orders" class="tab">
                    <i class="fas fa-shopping-bag"></i> <span>My Orders</span>
                </a>
            </div>
            
            <div class="reschedule-container">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                        <div class="alert-actions">
                            <a href="my_appointments.php" class="btn btn-sm btn-outline">Back to My Appointments</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($appointment): ?>
                    <div class="reschedule-grid">
                        <!-- Current Appointment Details -->
                        <div class="current-appointment-card">
                            <h3>Current Appointment</h3>
                            
                            <div class="appointment-detail">
                                <i class="fas fa-cut"></i>
                                <div>
                                    <span class="detail-label">Service</span>
                                    <span class="detail-value"><?= htmlspecialchars($appointment['service_name'] ?? '') ?></span>
                                </div>
                            </div>
                            
                            <div class="appointment-detail">
                                <i class="fas fa-calendar-alt"></i>
                                <div>
                                    <span class="detail-label">Date</span>
                                    <span class="detail-value"><?= date('l, F j, Y', strtotime($appointment['appointment_date'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="appointment-detail">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <span class="detail-label">Time</span>
                                    <span class="detail-value"><?= date('g:i A', strtotime($appointment['appointment_time'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="appointment-detail">
                                <i class="fas fa-user"></i>
                                <div>
                                    <span class="detail-label">Barber</span>
                                    <span class="detail-value"><?= htmlspecialchars($appointment['barber_name']) ?></span>
                                </div>
                            </div>
                            
                            <div class="appointment-detail">
                                <i class="fas fa-dollar-sign"></i>
                                <div>
                                    <span class="detail-label">Price</span>
                                    <span class="detail-value">$<?= number_format($appointment['price'] ?? 0, 2) ?></span>
                                </div>
                            </div>
                            
                            <?php if (!empty($appointment['notes'])): ?>
                                <div class="appointment-detail">
                                    <i class="fas fa-sticky-note"></i>
                                    <div>
                                        <span class="detail-label">Notes</span>
                                        <span class="detail-value"><?= nl2br(htmlspecialchars($appointment['notes'])) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Reschedule Form -->
                        <div class="reschedule-form">
                            <h3>Select New Date & Time</h3>
                            
                            <form method="post" action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                                <div class="form-group">
                                    <label for="appointment_date">New Date</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-calendar input-icon"></i>
                                        <input type="text" id="appointment_date" name="appointment_date" class="form-control datepicker" placeholder="Select date" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="appointment_time">New Time</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-clock input-icon"></i>
                                        <select id="appointment_time" name="appointment_time" class="form-control" required>
                                            <option value="">Select time</option>
                                            <?php foreach ($time_slots as $time): ?>
                                                <option value="<?= $time ?>"><?= date('g:i A', strtotime($time)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="barber">Barber (Optional)</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-user-tie input-icon"></i>
                                        <select id="barber" name="barber" class="form-control">
                                            <?php foreach ($barbers as $barber): ?>
                                                <option value="<?= $barber['id'] ?>" <?= $appointment['barber'] == $barber['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($barber['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="notes">Notes (Optional)</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="3"><?= htmlspecialchars($appointment['notes'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-calendar-check"></i> Confirm Reschedule
                                    </button>
                                    <a href="my_appointments.php" class="btn btn-outline">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                
                    <!-- Reschedule Policy Information -->
                    <div class="policy-notice">
                        <h4><i class="fas fa-info-circle"></i> Rescheduling Policy</h4>
                        <ul>
                            <li>You can reschedule appointments up to 4 hours before the scheduled time.</li>
                            <li>Please arrive 10 minutes before your scheduled appointment time.</li>
                            <li>If you need to cancel completely, please visit the My Appointments page.</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date picker
            const currentDate = new Date();
            const datePicker = flatpickr("#appointment_date", {
                minDate: "today",
                maxDate: new Date().fp_incr(30), // Allow booking up to 30 days in advance
                disable: [
                    function(date) {
                        // Disable Sundays (0) if your shop is closed
                        return date.getDay() === 0;
                    }
                ],
                dateFormat: "Y-m-d",
                onChange: function(selectedDates, dateStr, instance) {
                    // You can implement AJAX to check available times for the selected date
                    checkAvailableTimes(dateStr);
                }
            });
            
            // Function to check available times via AJAX
            function checkAvailableTimes(date) {
                const timeSelect = document.getElementById('appointment_time');
                const barberSelect = document.getElementById('barber');
                const barberId = barberSelect.value;
                
                // Show loading state
                timeSelect.disabled = true;
                timeSelect.innerHTML = '<option>Loading available times...</option>';
                
                // In a real implementation, you would make an AJAX request here
                // This is a placeholder for demonstration purposes
                setTimeout(() => {
                    // Reset time slots
                    timeSelect.innerHTML = '<option value="">Select time</option>';
                    
                    // Add time slots (in a real implementation, these would come from the server)
                    const timeSlots = <?= json_encode($time_slots) ?>;
                    timeSlots.forEach(time => {
                        const option = document.createElement('option');
                        option.value = time;
                        option.textContent = new Date('1970-01-01T' + time).toLocaleTimeString([], {hour: 'numeric', minute:'2-digit'});
                        timeSelect.appendChild(option);
                    });
                    
                    timeSelect.disabled = false;
                }, 500);
            }
            
            // Update available times when barber changes
            document.getElementById('barber').addEventListener('change', function() {
                const datePicker = document.getElementById('appointment_date');
                if (datePicker.value) {
                    checkAvailableTimes(datePicker.value);
                }
            });
        });
    </script>
</body>
</html>

<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    // Store the current page as the redirect destination after login
    $_SESSION['redirect_after_login'] = 'appointment.php';
    
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Database connection to fetch active barbers and services
try {
    $db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all active barbers
    $stmt = $db->prepare("SELECT barber_id, name FROM barbers WHERE active = 1 ORDER BY name ASC");
    $stmt->execute();
    $activeBarbers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all active services (no deduplication)
    $stmt = $db->prepare("SELECT service_id, name, description, duration, price, image FROM services WHERE active = 1 ORDER BY name ASC, service_id ASC");
    $stmt->execute();
    $allServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build a PHP associative array for service lookup by ID
    $serviceMap = [];
    foreach ($allServices as $service) {
        $serviceMap[$service['service_id']] = $service;
    }
} catch (PDOException $e) {
    $activeBarbers = [];
    $allServices = [];
    // Handle error silently - will show "Any Available Barber" option only
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/appointment.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/effects.css">
    <link rel="stylesheet" href="css/banner-styles.css">
    <link rel="stylesheet" href="css/color-themes.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_gold.css">
    <!-- Add iziToast CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <link rel="stylesheet" href="css/hamburger.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner services-banner parallax-background">
        <div class="banner-overlay gradient-bg"></div>
        <div class="container">
            <h1 data-aos="fade-up" class="text-gradient" style="background-size: 200%; background-position: left center;">Book Your Appointment</h1>
            <p data-aos="fade-up" data-aos-delay="200">Welcome back, 
                <?php
                    if (isset($_SESSION['user'])) {
                        $user = $_SESSION['user'];
                        if (!empty($user['first_name']) || !empty($user['last_name'])) {
                            echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
                        } elseif (!empty($user['name'])) {
                            echo htmlspecialchars($user['name']);
                        } else {
                            echo 'Valued Customer';
                        }
                    }
                ?>! Schedule your next grooming session.</p>
        </div>
    </section>

    <!-- Appointment Section -->
    <section class="appointment-section">
        <div class="container">
            <div class="appointment-container">
                <div class="appointment-info">
                    <h2>Ready for a Fresh Look?</h2>
                    <p>Book your appointment in just a few easy steps. Our expert barbers are ready to help you achieve your perfect style.</p>
                    
                    <div class="appointment-steps-container">
                        <div class="appointment-steps">
                            <div class="step active" id="step-indicator-1">
                                <div class="step-number">1</div>
                                <div class="step-text">Select Service</div>
                            </div>
                            <div class="step" id="step-indicator-2">
                                <div class="step-number">2</div>
                                <div class="step-text">Choose Date & Time</div>
                            </div>
                            <div class="step" id="step-indicator-3">
                                <div class="step-number">3</div>
                                <div class="step-text">Your Details</div>
                            </div>
                            <div class="step" id="step-indicator-4">
                                <div class="step-number">4</div>
                                <div class="step-text">Confirmation</div>
                            </div>
                        </div>
                        <div class="steps-progress">
                            <div class="progress-bar"></div>
                        </div>
                    </div>
                    
                    <div class="why-book">
                        <h3>Why Book With Us?</h3>
                        <ul class="benefits-list">
                            <li>
                                <div class="benefit-icon"><i class="fas fa-cut"></i></div>
                                <div class="benefit-text">Expert barbers with years of experience</div>
                            </li>
                            <li>
                                <div class="benefit-icon"><i class="fas fa-pump-soap"></i></div>
                                <div class="benefit-text">Premium products for the best results</div>
                            </li>
                            <li>
                                <div class="benefit-icon"><i class="fas fa-couch"></i></div>
                                <div class="benefit-text">Relaxing atmosphere and excellent service</div>
                            </li>
                            <li>
                                <div class="benefit-icon"><i class="fas fa-mobile-alt"></i></div>
                                <div class="benefit-text">Easy online booking and rescheduling</div>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="appointment-form-container">
                    <form id="appointment-form" action="process_appointment.php" method="post">
                        <!-- Step 1: Service Selection -->
                        <div class="form-step active" id="step-1">
                            <h3><i class="fas fa-list-ul"></i> Select Your Service</h3>
                            
                            <div class="service-options" id="service-options">
                                <?php foreach ($allServices as $i => $service): ?>
                                <div class="service-option">
                                    <input type="radio" name="service_id" id="service-<?php echo $service['service_id']; ?>" value="<?php echo $service['service_id']; ?>" data-duration="<?php echo htmlspecialchars($service['duration']); ?>" data-price="<?php echo htmlspecialchars($service['price']); ?>" <?php echo $i === 0 ? 'checked' : ''; ?>>
                                    <label for="service-<?php echo $service['service_id']; ?>">
                                        <div class="service-image">
                                            <img src="<?= !empty($service['image']) ? 'uploads/services/' . basename($service['image']) : 'uploads/services/service-placeholder.jpg' ?>" alt="<?= htmlspecialchars($service['name']) ?>">
                                        </div>
                                        <div class="service-icon"><i class="fas fa-cut"></i></div>
                                        <div class="service-details">
                                            <h4><?php echo htmlspecialchars($service['name']); ?></h4>
                                            <div class="service-meta">
                                                <span class="duration"><i class="far fa-clock"></i> <?php echo htmlspecialchars($service['duration']); ?> min</span>
                                                <span class="price">$<?php echo htmlspecialchars(number_format($service['price'], 2)); ?></span>
                                            </div>
                                            <?php if (!empty($service['description'])): ?>
                                                <div class="service-desc"><?php echo htmlspecialchars($service['description']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="form-nav">
                                <button type="button" class="btn btn-primary next-step">
                                    Continue <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 2: Date & Time Selection -->
                        <div class="form-step" id="step-2">
                            <h3><i class="far fa-calendar-alt"></i> Choose Date & Time</h3>
                            
                            <div class="date-time-selection">
                                <div class="form-group">
                                    <label for="appointment-date">Select Date</label>
                                    <div class="input-icon-wrapper">
                                        <i class="far fa-calendar-alt input-icon"></i>
                                        <input type="text" id="appointment-date" name="appointment-date" placeholder="Select a date" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Select Time</label>
                                    <div class="time-slots" id="time-slots">
                                        <!-- Time slots will be generated dynamically -->
                                        <div class="time-slots-loading">
                                            <i class="fas fa-spinner fa-pulse"></i>
                                            <span>Please select a date first</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group barber-selection">
                                    <label for="barber_id">Choose Barber (Optional)</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-user-tie input-icon"></i>
                                        <select id="barber_id" name="barber_id">
                                            <option value="">Any Available Barber</option>
                                            <?php foreach($activeBarbers as $barber): ?>
                                                <option value="<?php echo (int)$barber['barber_id']; ?>"><?php echo htmlspecialchars($barber['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-nav">
                                <button type="button" class="btn btn-outline prev-step">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="btn btn-primary next-step" id="date-time-next">
                                    Continue <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 3: Personal Details -->
                        <div class="form-step" id="step-3">
                            <h3><i class="fas fa-user"></i> Your Details</h3>
                            
                            <div class="personal-details">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" id="name" name="name" placeholder="Enter your full name" required 
                                        value="<?php
                                            if (isset($_SESSION['user'])) {
                                                $user = $_SESSION['user'];
                                                if (!empty($user['first_name']) || !empty($user['last_name'])) {
                                                    echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
                                                } elseif (!empty($user['name'])) {
                                                    echo htmlspecialchars($user['name']);
                                                }
                                            }
                                        ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-envelope input-icon"></i>
                                        <input type="email" id="email" name="email" placeholder="Enter your email address" required
                                        value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['email']) : '' ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-phone input-icon"></i>
                                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="notes">Special Requests (Optional)</label>
                                    <div class="input-icon-wrapper textarea">
                                        <i class="fas fa-comment-alt input-icon"></i>
                                        <textarea id="notes" name="notes" placeholder="Any special requests or comments?"></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-nav">
                                <button type="button" class="btn btn-outline prev-step">
                                    <i class="fas fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="btn btn-primary next-step" id="details-next">
                                    Review Booking <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 4: Booking Summary -->
                        <div class="form-step" id="step-4">
                            <h3><i class="fas fa-clipboard-check"></i> Review Your Booking</h3>
                            
                            <div class="booking-summary">
                                <div class="summary-section">
                                    <h4>Service Information</h4>
                                    <div class="summary-row">
                                        <div class="summary-label">Service:</div>
                                        <div class="summary-value" id="summary-service"></div>
                                    </div>
                                    
                                    <div class="summary-row">
                                        <div class="summary-label">Date:</div>
                                        <div class="summary-value" id="summary-date"></div>
                                    </div>
                                    
                                    <div class="summary-row">
                                        <div class="summary-label">Time:</div>
                                        <div class="summary-value" id="summary-time"></div>
                                    </div>
                                    
                                    <div class="summary-row">
                                        <div class="summary-label">Barber:</div>
                                        <div class="summary-value" id="summary-barber"></div>
                                    </div>
                                </div>
                                
                                <div class="summary-section">
                                    <h4>Personal Information</h4>
                                    <div class="summary-row">
                                        <div class="summary-label">Name:</div>
                                        <div class="summary-value" id="summary-name"></div>
                                    </div>
                                    
                                    <div class="summary-row">
                                        <div class="summary-label">Email:</div>
                                        <div class="summary-value" id="summary-email"></div>
                                    </div>
                                    
                                    <div class="summary-row">
                                        <div class="summary-label">Phone:</div>
                                        <div class="summary-value" id="summary-phone"></div>
                                    </div>
                                    
                                    <div class="summary-row" id="summary-notes-container">
                                        <div class="summary-label">Special Requests:</div>
                                        <div class="summary-value" id="summary-notes"></div>
                                    </div>
                                </div>
                                
                                <div class="summary-section price-section">
                                    <div class="summary-price">
                                        <div class="total-label">Total:</div>
                                        <div class="total-value" id="summary-price"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-nav">
                                <button type="button" class="btn btn-outline prev-step">
                                    <i class="fas fa-arrow-left"></i> Edit Booking
                                </button>
                                <button type="submit" class="btn btn-primary" id="confirm-booking">
                                    <i class="fas fa-check"></i> Confirm Booking
                                </button>
                            </div>
                        </div>
                        
                        <!-- Booking Confirmation -->
                        <div class="form-step" id="step-confirmation">
                            <div class="booking-confirmation">
                                <div class="confirmation-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3>Booking Confirmed!</h3>
                                <p>Your appointment has been successfully scheduled. A confirmation email has been sent to your email address.</p>
                                <div class="confirmation-details">
                                    <p><strong>Booking Reference:</strong> <span id="booking-reference"></span></p>
                                    <p><strong>Service:</strong> <span id="confirmation-service"></span></p>
                                    <p><strong>Date & Time:</strong> <span id="confirmation-datetime"></span></p>
                                </div>
                                <div class="confirmation-actions">
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-home"></i> Back to Home
                                    </a>
                                    <a href="my_appointments.php" class="btn btn-primary">
                                        <i class="fas fa-calendar-alt"></i> View My Appointments
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Add iziToast JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="js/common.js"></script>
    <script>
        // Initialize iziToast configuration
        iziToast.settings({
            timeout: 4000,
            resetOnHover: true,
            position: 'topRight',
            transitionIn: 'flipInX',
            transitionOut: 'flipOutX',
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Form step navigation
            const formSteps = document.querySelectorAll('.form-step');
            const stepIndicators = document.querySelectorAll('.step');
            const progressBar = document.querySelector('.progress-bar');
            let currentStep = 0;

            // Initialize flatpickr date picker
            const today = new Date();
            const maxDate = new Date();
            maxDate.setDate(today.getDate() + 30); // Allow booking up to 30 days in advance

            flatpickr('#appointment-date', {
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
                            icon: 'fas fa-calendar-alt'
                        });
                    }
                }
            });

            // Handle next step button clicks
            const nextButtons = document.querySelectorAll('.next-step');
            nextButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Validate current step
                    if (!validateStep(currentStep)) return;
                    
                    // Move to next step if validation passed
                    currentStep++;
                    updateFormStep();
                    
                    // Show step transition notification
                    showStepNotification(currentStep);
                });
            });

            // Handle previous step button clicks
            const prevButtons = document.querySelectorAll('.prev-step');
            prevButtons.forEach(button => {
                button.addEventListener('click', function() {
                    currentStep--;
                    updateFormStep();
                });
            });

            // Update the visible form step and progress indicators
            function updateFormStep() {
                formSteps.forEach((step, index) => {
                    step.classList.toggle('active', index === currentStep);
                });

                stepIndicators.forEach((indicator, index) => {
                    if (index < currentStep) {
                        indicator.classList.add('completed');
                        indicator.classList.remove('active');
                    } else if (index === currentStep) {
                        indicator.classList.add('active');
                        indicator.classList.remove('completed');
                    } else {
                        indicator.classList.remove('active', 'completed');
                    }
                });

                // Update progress bar
                const progressPercent = ((currentStep) / (stepIndicators.length - 1)) * 100;
                progressBar.style.width = `${progressPercent}%`;

                // If we're on step 4 (summary), update the summary info
                if (currentStep === 3) {
                    updateSummary();
                }
            }

            // Validate each step before proceeding
            function validateStep(step) {
                switch(step) {
                    case 0: // Service selection
                        const selectedService = document.querySelector('input[name="service_id"]:checked');
                        if (!selectedService) {
                            iziToast.warning({
                                title: 'Service Required',
                                message: 'Please select a service to continue',
                                icon: 'fas fa-exclamation-triangle'
                            });
                            return false;
                        }
                        return true;

                    case 1: // Date & Time selection
                        const selectedDate = document.getElementById('appointment-date').value;
                        const selectedTime = document.querySelector('input[name="appointment-time"]:checked');
                        
                        if (!selectedDate) {
                            iziToast.warning({
                                title: 'Date Required',
                                message: 'Please select an appointment date',
                                icon: 'fas fa-calendar-alt'
                            });
                            return false;
                        }
                        
                        if (!selectedTime) {
                            iziToast.warning({
                                title: 'Time Required',
                                message: 'Please select an appointment time',
                                icon: 'fas fa-clock'
                            });
                            return false;
                        }
                        return true;

                    case 2: // Personal details
                        const name = document.getElementById('name').value;
                        const email = document.getElementById('email').value;
                        const phone = document.getElementById('phone').value;
                        
                        if (!name || !email || !phone) {
                            iziToast.warning({
                                title: 'Missing Information',
                                message: 'Please fill in all required fields',
                                icon: 'fas fa-user'
                            });
                            
                            // Highlight missing fields
                            if (!name) document.getElementById('name').classList.add('error');
                            if (!email) document.getElementById('email').classList.add('error');
                            if (!phone) document.getElementById('phone').classList.add('error');
                            
                            return false;
                        }
                        
                        // Simple email validation
                        if (!validateEmail(email)) {
                            iziToast.warning({
                                title: 'Invalid Email',
                                message: 'Please enter a valid email address',
                                icon: 'fas fa-envelope'
                            });
                            document.getElementById('email').classList.add('error');
                            return false;
                        }
                        
                        return true;
                    
                    default:
                        return true;
                }
            }
            
            // Show notification for each step transition
            function showStepNotification(step) {
                switch(step) {
                    case 1:
                        const serviceName = document.querySelector('input[name="service_id"]:checked').value;
                        iziToast.success({
                            title: 'Service Selected',
                            message: `You've selected: ${serviceName}`,
                            icon: 'fas fa-check-circle'
                        });
                        break;
                    case 2:
                        const selectedDate = document.getElementById('appointment-date').value;
                        const selectedTime = document.querySelector('input[name="appointment-time"]:checked').value;
                        iziToast.success({
                            title: 'Date & Time Selected',
                            message: `${selectedDate} at ${selectedTime}`,
                            icon: 'fas fa-calendar-check'
                        });
                        break;
                    case 3:
                        iziToast.info({
                            title: 'Review Your Booking',
                            message: 'Please review your appointment details before confirming',
                            icon: 'fas fa-clipboard-check'
                        });
                        break;
                }
            }

            // Replace loadTimeSlots function with AJAX version
            function loadTimeSlots(date) {
                const timeSlotsContainer = document.getElementById('time-slots');
                timeSlotsContainer.innerHTML = '<div class="time-slots-loading"><i class="fas fa-spinner fa-pulse"></i><span>Loading available time slots...</span></div>';

                const selectedService = document.querySelector('input[name="service_id"]:checked');
                const serviceId = selectedService ? selectedService.value : '';
                const selectedBarber = document.getElementById('barber_id').value;

                // Fetch unavailable slots from the server
                fetch('unavailable_slots.php?date=' + encodeURIComponent(date) + '&service_id=' + encodeURIComponent(serviceId) + '&barber_id=' + encodeURIComponent(selectedBarber))
                    .then(response => response.json())
                    .then(data => {
                        const unavailable = data.unavailable || [];
                        const baseSlots = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00'];
                        let availableTimes = baseSlots.filter(slot => !unavailable.includes(slot));

                        if (availableTimes.length > 0) {
                            timeSlotsContainer.innerHTML = '';
                            availableTimes.forEach(time => {
                                const timeSlot = document.createElement('div');
                                timeSlot.className = 'time-slot';
                                timeSlot.innerHTML = `
                                    <input type="radio" name="appointment-time" id="time-${time.replace(':', '')}" value="${time}">
                                    <label for="time-${time.replace(':', '')}">
                                        <i class="far fa-clock"></i> ${time}
                                    </label>
                                `;
                                timeSlotsContainer.appendChild(timeSlot);
                            });
                            
                            // Add click event for time slots
                            const timeSlots = document.querySelectorAll('.time-slot input');
                            timeSlots.forEach(slot => {
                                slot.addEventListener('change', function() {
                                    document.querySelectorAll('.time-slot').forEach(el => {
                                        el.classList.remove('selected');
                                    });
                                    
                                    if (this.checked) {
                                        this.parentElement.classList.add('selected');
                                    }
                                });
                            });
                        } else {
                            timeSlotsContainer.innerHTML = '<div class="no-slots-message"><i class="fas fa-exclamation-circle"></i> No available time slots for this date. Please select another date.</div>';
                            iziToast.warning({
                                title: 'No Available Slots',
                                message: 'All slots are booked for this date. Please select another date.',
                                icon: 'fas fa-calendar-times'
                            });
                        }
                    })
                    .catch(() => {
                        timeSlotsContainer.innerHTML = '<div class="no-slots-message"><i class="fas fa-exclamation-circle"></i> Error loading time slots.</div>';
                    });
            }

            // Function to generate time slots (in a real app, this would come from the server)
            function generateTimeSlots(date, duration, barber) {
                // Simulate available time slots
                const baseSlots = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00'];
                
                // For demo purposes, remove some slots randomly to simulate availability
                // In a real app, this would come from your database based on existing appointments
                const selectedDate = new Date(date);
                const today = new Date();
                
                // If selected date is today, remove past times
                if (selectedDate.setHours(0,0,0,0) === today.setHours(0,0,0,0)) {
                    const currentHour = new Date().getHours();
                    const currentMinute = new Date().getMinutes();
                    
                    return baseSlots.filter(slot => {
                        const [hour, minute] = slot.split(':').map(Number);
                        return hour > currentHour || (hour === currentHour && minute > currentMinute + 30);
                    });
                }
                
                // For future dates, randomly remove some slots to simulate booked appointments
                return baseSlots.filter(() => Math.random() > 0.3);
            }

            // Add PHP service map as a JS object for lookup
            const serviceMap = <?php echo json_encode($serviceMap); ?>;

            // Update summary before confirmation
            function updateSummary() {
                const selectedService = document.querySelector('input[name="service_id"]:checked');
                let serviceName = '';
                let servicePrice = '';
                let serviceDuration = '';
                let serviceDesc = '';
                let serviceImage = '';
                if (selectedService && serviceMap[selectedService.value]) {
                    const svc = serviceMap[selectedService.value];
                    serviceName = svc.name;
                    servicePrice = svc.price;
                    serviceDuration = svc.duration;
                    serviceDesc = svc.description;
                    serviceImage = svc.image;
                }

                const appointmentDate = document.getElementById('appointment-date').value;
                const appointmentTime = document.querySelector('input[name="appointment-time"]:checked')?.value || '';
                const barberSelect = document.getElementById('barber_id');
                let barber = 'Any Available Barber';
                if (barberSelect && barberSelect.value) {
                    const selectedOption = barberSelect.options[barberSelect.selectedIndex];
                    if (selectedOption) {
                        barber = selectedOption.text;
                    }
                }
                const name = document.getElementById('name').value;
                const email = document.getElementById('email').value;
                const phone = document.getElementById('phone').value;
                const notes = document.getElementById('notes').value;

                document.getElementById('summary-service').textContent = serviceName;
                document.getElementById('summary-date').textContent = appointmentDate;
                document.getElementById('summary-time').textContent = appointmentTime;
                document.getElementById('summary-barber').textContent = barber;
                document.getElementById('summary-name').textContent = name;
                document.getElementById('summary-email').textContent = email;
                document.getElementById('summary-phone').textContent = phone;

                if (notes) {
                    document.getElementById('summary-notes').textContent = notes;
                    document.getElementById('summary-notes-container').style.display = 'flex';
                } else {
                    document.getElementById('summary-notes-container').style.display = 'none';
                }

                document.getElementById('summary-price').textContent = `$${parseFloat(servicePrice).toFixed(2)}`;
            }
            
            // Email validation function
            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(String(email).toLowerCase());
            }
            
            // Remove or comment out this block to allow normal form submission:
            /*
            // Handle form submission
            const appointmentForm = document.getElementById('appointment-form');
            appointmentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show processing notification
                iziToast.info({
                    title: 'Processing',
                    message: 'Confirming your appointment...',
                    icon: 'fas fa-spinner fa-spin',
                    timeout: false,
                    id: 'processing-toast'
                });
                
                // Disable the confirm button
                const confirmBtn = document.getElementById('confirm-booking');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Get form data
                const formData = new FormData(appointmentForm);
                
                // Simulate AJAX submission
                setTimeout(() => {
                    // Close the processing notification
                    iziToast.hide({}, document.querySelector('.iziToast#processing-toast'));
                    
                    // Show success notification
                    iziToast.success({
                        title: 'Appointment Booked!',
                        message: 'Your appointment has been confirmed successfully.',
                        icon: 'fas fa-check-circle',
                        timeout: 5000,
                        onClosing: function() {
                            // Generate a fake booking reference
                            const bookingRef = 'TIP' + Math.random().toString(36).substr(2, 8).toUpperCase();
                            
                            // Update confirmation details
                            document.getElementById('booking-reference').textContent = bookingRef;
                            document.getElementById('confirmation-service').textContent = document.querySelector('input[name="service_id"]:checked').value;
                            document.getElementById('confirmation-datetime').textContent = 
                                document.getElementById('appointment-date').value + ' at ' + 
                                document.querySelector('input[name="appointment-time"]:checked').value;
                            
                            // Show confirmation step
                            formSteps.forEach(step => step.classList.remove('active'));
                            document.getElementById('step-confirmation').classList.add('active');
                            
                            // Update progress bar to complete
                            progressBar.style.width = '100%';
                            
                            // In a real implementation, you would submit the form to the server
                            // and handle the response
                            // appointmentForm.submit();
                        }
                    });
                }, 2000);
            });
            */
            
            // Handle input validation on change
            document.querySelectorAll('input, select, textarea').forEach(element => {
                element.addEventListener('input', function() {
                    this.classList.remove('error');
                });
            });
            
            // Service selection handler with toast notification
            document.querySelectorAll('input[name="service_id"]').forEach(service => {
                service.addEventListener('change', function() {
                    if (this.checked) {
                        const serviceName = this.value;
                        const servicePrice = this.dataset.price;
                        
                        iziToast.info({
                            title: 'Service Selected',
                            message: `${serviceName} - $${servicePrice}`,
                            icon: 'fas fa-cut',
                            iconColor: '#2a9d8f'
                        });
                    }
                });
            });
            
            // Handle barber selection change
            document.getElementById('barber_id').addEventListener('change', function() {
                const selectedDate = document.getElementById('appointment-date').value;
                if (selectedDate) {
                    iziToast.info({
                        title: 'Refreshing Slots',
                        message: 'Updating available time slots for selected barber...',
                        icon: 'fas fa-user-tie'
                    });
                    loadTimeSlots(selectedDate);
                }
            });
        });
    </script>
    <?php
    // Show server-side error if set
    if (isset($_SESSION['appointment_error'])) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                iziToast.error({
                    title: "Booking Error",
                    message: "' . addslashes($_SESSION['appointment_error']) . '",
                    icon: "fas fa-times-circle"
                });
            });
        </script>';
        unset($_SESSION['appointment_error']);
    }
    ?>
</body>
</html>
</html>

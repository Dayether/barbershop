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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/appointment.css">
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_gold.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner appointment-banner">
        <div class="container">
            <h1>Book Your Appointment</h1>
            <p>Welcome back, <?= htmlspecialchars($_SESSION['user']['name']) ?>! Schedule your next grooming session.</p>
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
                                <div class="service-option">
                                    <input type="radio" name="service" id="service-1" value="Classic Haircut" data-duration="45" data-price="30" checked>
                                    <label for="service-1">
                                        <div class="service-image">
                                            <img src="images/services/haircut.jpg" alt="Classic Haircut">
                                        </div>
                                        <div class="service-icon"><i class="fas fa-cut"></i></div>
                                        <div class="service-details">
                                            <h4>Classic Haircut</h4>
                                            <div class="service-meta">
                                                <span class="duration"><i class="far fa-clock"></i> 45 min</span>
                                                <span class="price">$30</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="service-option">
                                    <input type="radio" name="service" id="service-2" value="Beard Trim" data-duration="30" data-price="25">
                                    <label for="service-2">
                                        <div class="service-image">
                                            <img src="images/services/beard.jpg" alt="Beard Trim">
                                        </div>
                                        <div class="service-icon"><i class="fas fa-beard"></i></div>
                                        <div class="service-details">
                                            <h4>Beard Trim</h4>
                                            <div class="service-meta">
                                                <span class="duration"><i class="far fa-clock"></i> 30 min</span>
                                                <span class="price">$25</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="service-option">
                                    <input type="radio" name="service" id="service-3" value="Hot Towel Shave" data-duration="45" data-price="35">
                                    <label for="service-3">
                                        <div class="service-image">
                                            <img src="images/services/shave.jpg" alt="Hot Towel Shave">
                                        </div>
                                        <div class="service-icon"><i class="fas fa-spa"></i></div>
                                        <div class="service-details">
                                            <h4>Hot Towel Shave</h4>
                                            <div class="service-meta">
                                                <span class="duration"><i class="far fa-clock"></i> 45 min</span>
                                                <span class="price">$35</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="service-option">
                                    <input type="radio" name="service" id="service-4" value="Complete Package" data-duration="90" data-price="75">
                                    <label for="service-4">
                                        <div class="service-image">
                                            <img src="images/services/package.jpg" alt="Complete Package">
                                        </div>
                                        <div class="service-icon"><i class="fas fa-gem"></i></div>
                                        <div class="service-details">
                                            <h4>Complete Package</h4>
                                            <div class="service-meta">
                                                <span class="duration"><i class="far fa-clock"></i> 90 min</span>
                                                <span class="price">$75</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
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
                                    <label for="barber">Choose Barber (Optional)</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-user-tie input-icon"></i>
                                        <select id="barber" name="barber">
                                            <option value="">Any Available Barber</option>
                                            <option value="John">John</option>
                                            <option value="Michael">Michael</option>
                                            <option value="David">David</option>
                                            <option value="Robert">Robert</option>
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
                                        value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['name']) : '' ?>">
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

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="js/common.js"></script>
    <script src="js/appointment.js"></script>
</body>
</html>

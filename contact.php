<?php
session_start();
// Include database connection
require_once 'includes/db_connection.php';

// Initialize variables
$name = $email = $phone = $subject = $message = "";
$nameErr = $emailErr = $phoneErr = $subjectErr = $messageErr = "";
$formSubmitted = false;
$formError = false;

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $formSubmitted = true;
    
    // Validate name
    if (empty($_POST["name"])) {
        $nameErr = "Name is required";
        $formError = true;
    } else {
        $name = test_input($_POST["name"]);
        // Check if name only contains letters and whitespace
        if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
            $nameErr = "Only letters and white space allowed";
            $formError = true;
        }
    }
    
    // Validate email
    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
        $formError = true;
    } else {
        $email = test_input($_POST["email"]);
        // Check if email is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
            $formError = true;
        }
    }
    
    // Validate phone
    if (empty($_POST["phone"])) {
        $phoneErr = "Phone number is required";
        $formError = true;
    } else {
        $phone = test_input($_POST["phone"]);
        // Check if phone number format is valid
        if (!preg_match("/^[0-9\-\(\)\/\+\s]*$/",$phone)) {
            $phoneErr = "Invalid phone number format";
            $formError = true;
        }
    }
    
    // Validate subject
    if (empty($_POST["subject"])) {
        $subjectErr = "Subject is required";
        $formError = true;
    } else {
        $subject = test_input($_POST["subject"]);
    }
    
    // Validate message
    if (empty($_POST["message"])) {
        $messageErr = "Message is required";
        $formError = true;
    } else {
        $message = test_input($_POST["message"]);
    }
    
    // If no errors, insert into database
    if (!$formError) {
        $status = "new"; // Default status for new messages
        
        $sql = "INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $name, $email, $phone, $subject, $message, $status);
        
        if ($stmt->execute()) {
            // Clear form fields after successful submission
            $name = $email = $phone = $subject = $message = "";
            $successMessage = "Your message has been sent successfully! We will contact you soon.";
        } else {
            $errorMessage = "Error: " . $stmt->error;
            $formError = true;
        }
        
        $stmt->close();
    }
}

// Function to sanitize input data
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/footer.css">
    <meta name="description" content="Contact Tipuno Barbershop for appointments, questions, or feedback. Our professional team is ready to assist you with all your grooming needs.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/hamburger.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/contact.css">
    <link rel="stylesheet" href="css/banner-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add iziToast CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner contact-banner">
        <div class="container">
            <h1 data-aos="fade-up">Get in Touch With Us</h1>
            <p data-aos="fade-up" data-aos-delay="100">Have questions or want to book an appointment? Reach out to us and we'll get back to you as soon as possible.</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-container">
                <!-- Contact Information -->
                <div class="contact-info" data-aos="fade-up" data-aos-delay="100">
                    <h2 class="section-title">Contact Information</h2>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <h4>Our Location</h4>
                            <p>The Courtyard at Big Ben Complex, Lipa City, Philippines, 4217</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="info-content">
                            <h4>Call Us</h4>
                            <p>(123) 456-7890<br>(123) 987-6543</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <h4>Email Us</h4>
                            <p>info@tipunobarbershop.com<br>appointments@tipunobarbershop.com</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <h4>Business Hours</h4>
                            <table class="hours-table">
                                <tr>
                                    <td>Monday - Friday</td>
                                    <td>9:00 AM - 8:00 PM</td>
                                </tr>
                                <tr>
                                    <td>Saturday</td>
                                    <td>9:00 AM - 6:00 PM</td>
                                </tr>
                                <tr>
                                    <td>Sunday</td>
                                    <td>10:00 AM - 4:00 PM</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-yelp"></i></a>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="contact-form-container" data-aos="fade-up" data-aos-delay="200">
                    <div class="section-subtitle">Send a Message</div>
                    <h2 class="section-title">Get in Touch</h2>
                    
                    <div class="contact-form">
                        <form id="contact-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="styled-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name"><i class="fas fa-user"></i> Your Name</label>
                                    <input type="text" id="name" name="name" value="<?php echo $name; ?>" placeholder="John Doe" class="form-control">
                                    <span class="error"><?php echo $nameErr; ?></span>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope"></i> Your Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo $email; ?>" placeholder="john@example.com" class="form-control">
                                    <span class="error"><?php echo $emailErr; ?></span>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone"><i class="fas fa-phone"></i> Your Phone</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo $phone; ?>" placeholder="(555) 123-4567" class="form-control">
                                    <span class="error"><?php echo $phoneErr; ?></span>
                                </div>
                                
                                <div class="form-group">
                                    <label for="subject"><i class="fas fa-tag"></i> Subject</label>
                                    <select id="subject" name="subject" class="form-control">
                                        <option value="" <?php echo empty($subject) ? 'selected' : ''; ?>>Select a subject</option>
                                        <option value="Appointment Request" <?php echo ($subject === "Appointment Request") ? 'selected' : ''; ?>>Appointment Request</option>
                                        <option value="Service Inquiry" <?php echo ($subject === "Service Inquiry") ? 'selected' : ''; ?>>Service Inquiry</option>
                                        <option value="Product Information" <?php echo ($subject === "Product Information") ? 'selected' : ''; ?>>Product Information</option>
                                        <option value="Pricing Question" <?php echo ($subject === "Pricing Question") ? 'selected' : ''; ?>>Pricing Question</option>
                                        <option value="Feedback" <?php echo ($subject === "Feedback") ? 'selected' : ''; ?>>Feedback/Review</option>
                                        <option value="Career Opportunity" <?php echo ($subject === "Career Opportunity") ? 'selected' : ''; ?>>Career Opportunity</option>
                                        <option value="Other" <?php echo ($subject === "Other") ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <span class="error"><?php echo $subjectErr; ?></span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="message"><i class="fas fa-comment-alt"></i> Your Message</label>
                                <textarea id="message" name="message" placeholder="Tell us about your inquiry..." class="form-control"><?php echo $message; ?></textarea>
                                <span class="error"><?php echo $messageErr; ?></span>
                            </div>
                            
                            <button type="submit" id="submit-btn" class="btn btn-primary">Send Message <i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Google Map -->
            <div class="map-container" data-aos="fade-up" data-aos-delay="300">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3876.091677274235!2d121.1503051!3d13.9420165!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd6d89c640b801%3A0xeea76a75184b91c1!2sTipuno%20X%20Lipa!5e0!3m2!1sen!2sph!4v1717920000000!5m2!1sen!2sph" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>
    
    <!-- Call-to-Action Section -->
    <section class="mini-cta" data-aos="fade-up">
        <div class="container">
            <div class="cta-content">
                <h2>Ready for a Premium Grooming Experience?</h2>
                <p>Book your appointment today and experience the Tipuno difference.</p>
                <a href="appointment.php" class="btn btn-primary">Book Appointment</a>
            </div>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <!-- Add iziToast JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script src="js/common.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS animation library
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                mirror: false
            });

            // Initialize iziToast
            iziToast.settings({
                timeout: 5000,
                resetOnHover: true,
                position: 'topRight',
                transitionIn: 'flipInX',
                transitionOut: 'flipOutX',
            });

            <?php if ($formSubmitted && !$formError && isset($successMessage)): ?>
                // Show success message
                iziToast.success({
                    title: 'Success!',
                    message: '<?php echo $successMessage; ?>',
                    icon: 'fas fa-check-circle'
                });
                
                // Send a follow-up toast after success
                setTimeout(function() {
                    iziToast.info({
                        title: 'Thank You',
                        message: 'Our team will review your message and reach out to you shortly.',
                        icon: 'fas fa-info-circle',
                        iconColor: '#2a9d8f'
                    });
                }, 2000);
            <?php endif; ?>

            <?php if ($formError && isset($errorMessage)): ?>
                // Show error message
                iziToast.error({
                    title: 'Error',
                    message: '<?php echo $errorMessage; ?>',
                    icon: 'fas fa-exclamation-circle'
                });
            <?php endif; ?>

            <?php if ($formError): ?>
                // Show validation errors with iziToast
                <?php if (!empty($nameErr)): ?>
                    iziToast.warning({
                        title: 'Name Error',
                        message: '<?php echo $nameErr; ?>',
                        icon: 'fas fa-user'
                    });
                <?php endif; ?>

                <?php if (!empty($emailErr)): ?>
                    iziToast.warning({
                        title: 'Email Error',
                        message: '<?php echo $emailErr; ?>',
                        icon: 'fas fa-envelope'
                    });
                <?php endif; ?>

                <?php if (!empty($phoneErr)): ?>
                    iziToast.warning({
                        title: 'Phone Error',
                        message: '<?php echo $phoneErr; ?>',
                        icon: 'fas fa-phone'
                    });
                <?php endif; ?>

                <?php if (!empty($subjectErr)): ?>
                    iziToast.warning({
                        title: 'Subject Error',
                        message: '<?php echo $subjectErr; ?>',
                        icon: 'fas fa-tag'
                    });
                <?php endif; ?>

                <?php if (!empty($messageErr)): ?>
                    iziToast.warning({
                        title: 'Message Error',
                        message: '<?php echo $messageErr; ?>',
                        icon: 'fas fa-comment-alt'
                    });
                <?php endif; ?>
            <?php endif; ?>

            // Add client-side validation with iziToast feedback
            const contactForm = document.getElementById('contact-form');
            
            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    let hasErrors = false;
                    const name = document.getElementById('name').value.trim();
                    const email = document.getElementById('email').value.trim();
                    const phone = document.getElementById('phone').value.trim();
                    const subject = document.getElementById('subject').value;
                    const message = document.getElementById('message').value.trim();
                    
                    // Clear previous errors
                    document.querySelectorAll('.form-control').forEach(input => {
                        input.classList.remove('error-input');
                    });
                    
                    // Validate all fields
                    if (!name) {
                        document.getElementById('name').classList.add('error-input');
                        iziToast.warning({
                            title: 'Name Required',
                            message: 'Please enter your name',
                            icon: 'fas fa-user'
                        });
                        hasErrors = true;
                    }
                    
                    if (!email) {
                        document.getElementById('email').classList.add('error-input');
                        iziToast.warning({
                            title: 'Email Required',
                            message: 'Please enter your email address',
                            icon: 'fas fa-envelope'
                        });
                        hasErrors = true;
                    } else if (!validateEmail(email)) {
                        document.getElementById('email').classList.add('error-input');
                        iziToast.warning({
                            title: 'Invalid Email',
                            message: 'Please enter a valid email address',
                            icon: 'fas fa-envelope'
                        });
                        hasErrors = true;
                    }
                    
                    if (!phone) {
                        document.getElementById('phone').classList.add('error-input');
                        iziToast.warning({
                            title: 'Phone Required',
                            message: 'Please enter your phone number',
                            icon: 'fas fa-phone'
                        });
                        hasErrors = true;
                    }
                    
                    if (!subject) {
                        document.getElementById('subject').classList.add('error-input');
                        iziToast.warning({
                            title: 'Subject Required',
                            message: 'Please select a subject',
                            icon: 'fas fa-tag'
                        });
                        hasErrors = true;
                    }
                    
                    if (!message) {
                        document.getElementById('message').classList.add('error-input');
                        iziToast.warning({
                            title: 'Message Required',
                            message: 'Please enter your message',
                            icon: 'fas fa-comment-alt'
                        });
                        hasErrors = true;
                    }
                    
                    if (hasErrors) {
                        e.preventDefault();
                        return false;
                    } else {
                        // Show sending message
                        const submitBtn = document.getElementById('submit-btn');
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                        
                        iziToast.info({
                            title: 'Sending',
                            message: 'Submitting your message...',
                            icon: 'fas fa-paper-plane',
                            timeout: false,
                            id: 'sending-toast'
                        });
                        
                        // Allow form submission
                        return true;
                    }
                });
                
                // Add input event listeners to remove error styling when user types
                document.querySelectorAll('.form-control').forEach(input => {
                    input.addEventListener('input', function() {
                        this.classList.remove('error-input');
                    });
                });
            }
            
            // Email validation helper function
            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            
            // Subject select enhancement - show toast on change
            const subjectSelect = document.getElementById('subject');
            if (subjectSelect) {
                subjectSelect.addEventListener('change', function() {
                    if (this.value) {
                        let icon = 'fas fa-tag';
                        
                        // Custom icons based on subject
                        switch(this.value) {
                            case 'Appointment Request':
                                icon = 'fas fa-calendar-check';
                                break;
                            case 'Service Inquiry':
                                icon = 'fas fa-cut';
                                break;
                            case 'Product Information':
                                icon = 'fas fa-shopping-bag';
                                break;
                            case 'Pricing Question':
                                icon = 'fas fa-dollar-sign';
                                break;
                            case 'Feedback':
                                icon = 'fas fa-star';
                                break;
                            case 'Career Opportunity':
                                icon = 'fas fa-briefcase';
                                break;
                        }
                        
                        iziToast.info({
                            title: 'Subject Selected',
                            message: `${this.value}`,
                            icon: icon,
                            iconColor: '#2a9d8f',
                            timeout: 2000
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>

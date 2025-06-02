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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                            <p>123 Barber Street, Downtown<br>New York, NY 10001</p>
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
                    
                    <?php if ($formSubmitted && !$formError && isset($successMessage)): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $successMessage; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($formError && isset($errorMessage)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $errorMessage; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="contact-form">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="styled-form">
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
                            
                            <button type="submit" class="btn btn-primary">Send Message <i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Google Map -->
            <div class="map-container" data-aos="fade-up" data-aos-delay="300">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.9663095343008!2d-74.00425882352196!3d40.74076351388875!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259a9b30eac9f%3A0xaca05ca48ab5ac2c!2sEmpire%20State%20Building!5e0!3m2!1sen!2sus!4v1652209498553!5m2!1sen!2sus" allowfullscreen="" loading="lazy"></iframe>
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
    <script src="js/common.js"></script>
    <script src="js/contact.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS animation library
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                mirror: false
            });
        });
    </script>
</body>
</html>

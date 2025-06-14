<!-- Footer -->
<footer class="footer">
    <!-- Premium footer top pattern -->
    <div class="footer-pattern"></div>
    
    <div class="footer-top">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="logo-container">
                        <img src="images/logo2.jpg" alt="Tipuno Barbershop" class="footer-logo-img">
                    </div>
                    <p class="brand-description">Premium grooming services for the distinguished gentleman since 1995. Excellence in every detail.</p>
                    <div class="gold-divider"></div>
                    <div class="social-icons-wrapper">
                        <h5 class="follow-title">Follow Us</h5>
                        <div class="social-icons">
                            <a href="#" class="social-icon-gold" aria-label="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-icon-gold" aria-label="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-icon-gold" aria-label="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-icon-gold" aria-label="YouTube">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="footer-navigation">
                    <h4>Navigation</h4>
                    <div class="gold-divider"></div>
                    <div class="footer-links-columns">
                        <ul class="footer-links-column">
                            <li><a href="index.php" class="footer-link"><i class="fas fa-chevron-right"></i> Home</a></li>
                            <li><a href="services.php" class="footer-link"><i class="fas fa-chevron-right"></i> Services</a></li>
                            <li><a href="shop.php" class="footer-link"><i class="fas fa-chevron-right"></i> Shop</a></li>
                        </ul>
                        <ul class="footer-links-column">
                            <li><a href="appointment.php" class="footer-link"><i class="fas fa-chevron-right"></i> Book Now</a></li>
                            <li><a href="about.php" class="footer-link"><i class="fas fa-chevron-right"></i> About Us</a></li>
                            <li><a href="contact.php" class="footer-link"><i class="fas fa-chevron-right"></i> Contact</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="footer-contact">
                    <h4>Contact Us</h4>
                    <div class="gold-divider"></div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt contact-icon"></i>
                        <div>
                            <h5>Visit Our Shop</h5>
                            <address>123 Barber Street<br>New York, NY 10001</address>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone-alt contact-icon"></i>
                        <div>
                            <h5>Call Us</h5>
                            <a href="tel:+11234567890" class="contact-link">(123) 456-7890</a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope contact-icon"></i>
                        <div>
                            <h5>Email Us</h5>
                            <a href="mailto:info@tipuno.com" class="contact-link">info@tipuno.com</a>
                        </div>
                    </div>
                </div>
                
                <div class="footer-hours">
                    <h4>Business Hours</h4>
                    <div class="gold-divider"></div>
                    <div class="hours-grid">
                        <div class="hours-item">
                            <span class="day">Monday - Friday</span>
                            <span class="time">9:00 AM - 6:00 PM</span>
                        </div>
                        <div class="hours-item">
                            <span class="day">Saturday</span>
                            <span class="time">10:00 AM - 4:00 PM</span>
                        </div>
                        <div class="hours-item">
                            <span class="day">Sunday</span>
                            <span class="time">Closed</span>
                        </div>
                        <div class="book-cta">
                            <a href="appointment.php" class="btn btn-sm btn-primary">Book Appointment</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> Tipuno Barbershop. All rights reserved.</p>
                </div>
                <div class="footer-legal">
                    <a href="privacy-policy.php">Privacy Policy</a>
                    <a href="terms-of-service.php">Terms of Service</a>
                    <a href="sitemap.php">Sitemap</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to top button -->
<a href="#" class="back-to-top" id="back-to-top" aria-label="Back to top">
    <i class="fas fa-chevron-up"></i>
</a>

<!-- Scripts -->
<script>
    // Back to top button functionality
    const backToTopButton = document.getElementById('back-to-top');
    
    if (backToTopButton) {
        // Show/hide button based on scroll position
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopButton.classList.add('active');
            } else {
                backToTopButton.classList.remove('active');
            }
        });
        
        // Smooth scroll to top when clicked
        backToTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Newsletter form handling
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const responseEl = document.getElementById('newsletter-response');
            
            // Simulate form submission
            responseEl.textContent = "Thank you for subscribing!";
            responseEl.className = "form-response success";
            this.reset();
            
            // Hide success message after 3 seconds
            setTimeout(() => {
                responseEl.textContent = "";
                responseEl.className = "form-response";
            }, 3000);
        });
    }
</script>

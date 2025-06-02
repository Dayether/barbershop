<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipuno Barbershop - Premium Grooming Experience</title>
    <link rel="stylesheet" href="css/footer.css">
    <meta name="description" content="Experience premium men's grooming at Tipuno Barbershop. Expert haircuts, beard trims, and hot towel shaves in a relaxed atmosphere.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/hamburger.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/animations.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <script>
        // Ensure all assets are loaded correctly
        window.addEventListener('error', function(event) {
            console.error('Asset failed to load:', event.target.src || event.target.href);
        }, true);
    </script>
</head>
<body>
    <?php
    // Ensure header.php exists and is included correctly
    $headerPath = 'includes/header.php';
    if (file_exists($headerPath)) {
        include $headerPath;
    } else {
        echo '<p style="color: red;">Error: Header file not found.</p>';
    }
    ?>

    <!-- Enhanced Hero Section -->
    <section class="hero">
        <div class="video-background">
            <div class="video-foreground">
                <video autoplay muted loop playsinline id="hero-video">
                    <source src="uploads/videoplayback.mp4" type="video/mp4">
                </video>
            </div>
            <div class="video-overlay"></div>
        </div>
        <div class="container">
            <div class="hero-content" data-aos="fade-up" data-aos-duration="1000">
                <div class="hero-tagline">Since 1995</div>
                <h1>Precision. Style.<br><span class="text-highlight">Confidence.</span></h1>
                <p>Experience premium grooming tailored to your unique style.</p>
                <div class="hero-buttons">
                    <a href="appointment.php" class="btn btn-primary pulse-button">Book Appointment</a>
                    <a href="services.php" class="btn btn-outline">Explore Services</a>
                </div>
                <div class="hero-features">
                    <div class="feature">
                        <i class="fas fa-medal"></i>
                        <span>Expert Barbers</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-star"></i>
                        <span>Premium Products</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-smile"></i>
                        <span>100% Satisfaction</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-scroll-indicator">
            <a href="#services">
                <span>Scroll</span>
                <i class="fas fa-chevron-down"></i>
            </a>
        </div>
    </section>

    <!-- Enhanced About Section -->
    <section class="about-section" data-aos="fade-up">
        <div class="container">
            <div class="about-grid">
                <div class="about-image">
                    <img src="images/about-barbershop.jpg" alt="Tipuno Barbershop Interior" class="parallax-image">
                    <div class="experience-badge">
                        <span class="years">28</span>
                        <span class="text">Years of<br>Experience</span>
                    </div>
                </div>
                <div class="about-content">
                    <div class="section-subtitle">About Us</div>
                    <h2 class="section-title">A Cut Above The Rest</h2>
                    <p class="about-description">Tipuno Barbershop has been providing premium grooming services since 1995. Our master barbers combine traditional techniques with modern styles to create the perfect look for each client.</p>
                    
                    <div class="about-features">
                        <div class="about-feature">
                            <div class="feature-icon">
                                <i class="fas fa-cut"></i>
                            </div>
                            <div class="feature-content">
                                <h3>Skilled Professionals</h3>
                                <p>Our barbers undergo rigorous training and stay updated with the latest trends.</p>
                            </div>
                        </div>
                        <div class="about-feature">
                            <div class="feature-icon">
                                <i class="fas fa-thumbs-up"></i>
                            </div>
                            <div class="feature-content">
                                <h3>Quality Products</h3>
                                <p>We use only premium products to ensure the best results for your hair and skin.</p>
                            </div>
                        </div>
                    </div>
                    
                    <a href="about.php" class="btn btn-secondary">Learn Our Story</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Services Section -->
    <section id="services" class="services" data-aos="fade-up">
        <div class="container">
            <div class="section-header text-center">
                <div class="section-subtitle">Our Expertise</div>
                <h2 class="section-title">Premium Services</h2>
                <p class="section-description">Discover our range of professional services tailored to enhance your style</p>
            </div>
            
            <div class="services-grid">
                <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-image">
                        <img src="images/haircut-service.jpg" alt="Professional Haircut">
                        <div class="service-price">from $30</div>
                    </div>
                    <div class="service-content">
                        <div class="service-icon">
                            <i class="fas fa-cut"></i>
                        </div>
                        <h3>Precision Haircut</h3>
                        <p>Expert cuts tailored to enhance your features and style preferences.</p>
                        <a href="services.php#haircuts" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="service-image">
                        <img src="images/beard-service.jpg" alt="Beard Trim">
                        <div class="service-price">from $25</div>
                    </div>
                    <div class="service-content">
                        <div class="service-icon">
                            <i class="fas fa-beard"></i>
                        </div>
                        <h3>Beard Sculpting</h3>
                        <p>Meticulous beard trimming and shaping for a well-groomed appearance.</p>
                        <a href="services.php#beards" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="service-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="service-image">
                        <img src="images/shave-service.jpg" alt="Hot Towel Shave">
                        <div class="service-price">from $35</div>
                    </div>
                    <div class="service-content">
                        <div class="service-icon">
                            <i class="fas fa-spa"></i>
                        </div>
                        <h3>Luxury Shave</h3>
                        <p>Traditional hot towel shave for the ultimate refreshing experience.</p>
                        <a href="services.php#shaves" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-40">
                <a href="services.php" class="btn btn-secondary">View All Services</a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" data-aos="fade-up">
        <div class="container">
            <div class="section-header text-center">
                <div class="section-subtitle">Client Feedback</div>
                <h2 class="section-title">What Our Clients Say</h2>
            </div>
            
            <div class="testimonials-slider">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"Absolutely the best barbershop experience! Michael understood exactly what style I wanted and executed it perfectly. The hot towel treatment was amazing."</p>
                    <div class="testimonial-author">
                        <img src="images/testimonials/client1.jpg" alt="James Wilson" class="author-image">
                        <div class="author-info">
                            <h4>James Wilson</h4>
                            <span>Loyal Customer</span>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"I've been going to Tipuno for over a year now. The attention to detail and consistent quality keeps me coming back. Their beard oil recommendations changed my grooming routine."</p>
                    <div class="testimonial-author">
                        <img src="images/testimonials/client2.jpg" alt="Robert Davis" class="author-image">
                        <div class="author-info">
                            <h4>Robert Davis</h4>
                            <span>Regular Client</span>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="testimonial-text">"First-time visitor and definitely won't be my last. The ambiance, professionalism, and quality of service were all exceptional. My new go-to barbershop."</p>
                    <div class="testimonial-author">
                        <img src="images/testimonials/client3.jpg" alt="Thomas Brown" class="author-image">
                        <div class="author-info">
                            <h4>Thomas Brown</h4>
                            <span>New Customer</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Featured Products Section -->
    <section id="shop" class="shop" data-aos="fade-up">
        <div class="container">
            <div class="section-header text-center">
                <div class="section-subtitle">Our Products</div>
                <h2 class="section-title">Premium Grooming Essentials</h2>
                <p class="section-description">Quality products for the modern gentleman, handpicked by our expert barbers</p>
            </div>
            
            <div class="product-grid">
                <div class="product-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="product-badge bestseller">Bestseller</div>
                    <div class="product-image-wrapper">
                        <img src="images/product1.jpg" alt="Premium Pomade">
                        <div class="product-actions">
                            <button class="quick-view-btn" data-product="1"><i class="fas fa-eye"></i> Quick View</button>
                            <button class="btn-add-to-cart" data-id="1" data-name="Premium Pomade" data-price="15" data-image="images/product1.jpg">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                    <div class="product-details">
                        <h3>Premium Pomade</h3>
                        <div class="product-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span>(42)</span>
                        </div>
                        <p class="product-price">$15.00</p>
                    </div>
                </div>

                <div class="product-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="product-badge new">New</div>
                    <div class="product-image-wrapper">
                        <img src="images/product2.jpg" alt="Luxury Beard Oil">
                        <div class="product-actions">
                            <button class="quick-view-btn" data-product="2"><i class="fas fa-eye"></i> Quick View</button>
                            <button class="btn-add-to-cart" data-id="2" data-name="Luxury Beard Oil" data-price="20" data-image="images/product2.jpg">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                    <div class="product-details">
                        <h3>Luxury Beard Oil</h3>
                        <div class="product-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span>(27)</span>
                        </div>
                        <p class="product-price">$20.00</p>
                    </div>
                </div>

                <div class="product-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="product-image-wrapper">
                        <img src="images/product3.jpg" alt="Premium Shaving Cream">
                        <div class="product-actions">
                            <button class="quick-view-btn" data-product="3"><i class="fas fa-eye"></i> Quick View</button>
                            <button class="btn-add-to-cart" data-id="3" data-name="Premium Shaving Cream" data-price="10" data-image="images/product3.jpg">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                    <div class="product-details">
                        <h3>Premium Shaving Cream</h3>
                        <div class="product-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                            <span>(18)</span>
                        </div>
                        <p class="product-price">$10.00</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-40">
                <a href="shop.php" class="btn btn-secondary">View All Products</a>
            </div>
        </div>
    </section>

    <!-- Enhanced Call-to-Action Section -->
    <section id="appointment" class="cta" data-aos="fade-up">
        <div class="cta-overlay"></div>
        <div class="container">
            <div class="cta-content">
                <div class="section-subtitle">Book Now</div>
                <h2>Ready For A Fresh New Look?</h2>
                <p>Schedule your appointment today and experience the Tipuno difference. Our expert barbers are ready to transform your style.</p>
                <div class="cta-buttons">
                    <a href="appointment.php" class="btn btn-primary">Book Appointment</a>
                    <a href="contact.php" class="btn btn-outline">Contact Us</a>
                </div>
                
                <div class="cta-contact-info">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>(123) 456-7890</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Barber Street, City</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Instagram Feed Section -->
    <section class="instagram-feed" data-aos="fade-up">
        <div class="container">
            <div class="section-header text-center">
                <div class="section-subtitle">Follow Us</div>
                <h2 class="section-title">@TipunoBarbershop</h2>
            </div>
            
            <div class="instagram-grid">
                <a href="https://instagram.com" class="instagram-item">
                    <img src="images/instagram/insta1.jpg" alt="Instagram Post">
                    <div class="instagram-overlay">
                        <i class="fab fa-instagram"></i>
                    </div>
                </a>
                <a href="https://instagram.com" class="instagram-item">
                    <img src="images/instagram/insta2.jpg" alt="Instagram Post">
                    <div class="instagram-overlay">
                        <i class="fab fa-instagram"></i>
                    </div>
                </a>
                <a href="https://instagram.com" class="instagram-item">
                    <img src="images/instagram/insta3.jpg" alt="Instagram Post">
                    <div class="instagram-overlay">
                        <i class="fab fa-instagram"></i>
                    </div>
                </a>
                <a href="https://instagram.com" class="instagram-item">
                    <img src="images/instagram/insta4.jpg" alt="Instagram Post">
                    <div class="instagram-overlay">
                        <i class="fab fa-instagram"></i>
                    </div>
                </a>
                <a href="https://instagram.com" class="instagram-item">
                    <img src="images/instagram/insta5.jpg" alt="Instagram Post">
                    <div class="instagram-overlay">
                        <i class="fab fa-instagram"></i>
                    </div>
                </a>
                <a href="https://instagram.com" class="instagram-item">
                    <img src="images/instagram/insta6.jpg" alt="Instagram Post">
                    <div class="instagram-overlay">
                        <i class="fab fa-instagram"></i>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <?php
    // Ensure footer.php exists and is included correctly
    $footerPath = 'includes/footer.php';
    if (file_exists($footerPath)) {
        include $footerPath;
    } else {
        echo '<p style="color: red;">Error: Footer file not found.</p>';
    }
    ?>

    <!-- Ensure all scripts are loaded -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('All scripts loaded successfully.');
        });
    </script>
    <!-- Scripts -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="js/common.js"></script>
    <script src="js/index.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS animation library
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                mirror: false
            });
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const target = document.querySelector(targetId);
                    if (target) {
                        window.scrollTo({
                            top: target.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>

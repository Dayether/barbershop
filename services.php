<?php
session_start();
require_once 'database.php';

// Fetch all unique active services using OOP
try {
    $db = new Database();
    $allServices = $db->getUniqueActiveServices();
} catch (Exception $e) {
    $allServices = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Tipuno Barbershop</title>
    <meta name="description" content="Experience premium grooming services at Tipuno Barbershop - from classic haircuts to luxury shaves and complete grooming packages.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/effects.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/services.css">
    <link rel="stylesheet" href="css/color-themes.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body class="gold-grain">
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner services-banner parallax-background">
        <div class="banner-overlay gradient-bg"></div>
        <div class="container">
            <h1 data-aos="fade-up" class="text-gradient">Our Premium Services</h1>
            <p data-aos="fade-up" data-aos-delay="200">Craftsmanship and style for the modern gentleman</p>
            
            <div class="service-interactive-menu" data-aos="fade-up" data-aos-delay="400">
                <div class="menu-item active" data-target="haircuts">
                    <div class="icon"><i class="fas fa-cut"></i></div>
                    <span>Haircuts</span>
                </div>
                <div class="menu-item" data-target="beards">
                    <div class="icon"><i class="fas fa-face-grin"></i></div>
                    <span>Beard Trims</span>
                </div>
                <div class="menu-item" data-target="shaves">
                    <div class="icon"><i class="fas fa-user-tie"></i></div>
                    <span>Shaves</span>
                </div>
                <div class="menu-item" data-target="packages">
                    <div class="icon"><i class="fas fa-gem"></i></div>
                    <span>Packages</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Detail Section -->
    <section class="services-detail">
        <div class="container">
            <div class="services-category active" id="haircuts">
                <div class="service-item" data-aos="fade-right">
                    <div class="service-image video-container">
                        <video class="service-video" controls poster="images/haircut-thumb.jpg">
                            <source src="uploads/haircuts-video.mp4" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                    <div class="service-info" data-aos="fade-left">
                        <div class="service-badge popular">Most Popular</div>
                        <h2 class="text-gradient">Classic Haircut</h2>
                        <div class="price-tag">
                            <span class="currency">$</span>
                            <span class="amount">30</span>
                        </div>
                        <p>Our signature haircut service includes a consultation, shampoo, precision cut, styling, and a hot towel finish. Our expert barbers will work with you to achieve the perfect look that suits your style and face shape.</p>
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> 45 minute session</li>
                            <li><i class="fas fa-check"></i> Consultation included</li>
                            <li><i class="fas fa-check"></i> Styling products included</li>
                        </ul>
                        <a href="appointment.php" class="btn btn-primary pulse-button">Book This Service</a>
                    </div>
                </div>
                
                <!-- ...additional service items... -->
            </div>
            
            <!-- Beard Trims Section -->
            <div class="services-category" id="beards">
                <div class="service-item" data-aos="fade-right">
                    <div class="service-image video-container">
                        <img src="uploads/beard-trims2.jpg" alt="Beard Shape & Trim">
                    </div>
                    <div class="service-info" data-aos="fade-left">
                        <div class="service-badge popular">Most Popular</div>
                        <h2 class="text-gradient">Beard Shape & Trim</h2>
                        <div class="price-tag">
                            <span class="currency">$</span>
                            <span class="amount">25</span>
                        </div>
                        <p>Expert beard shaping and trimming to enhance your facial features. Includes beard wash, conditioning, precision trimming, and styling with premium beard products.</p>
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> 30 minute session</li>
                            <li><i class="fas fa-check"></i> Hot towel treatment</li>
                            <li><i class="fas fa-check"></i> Beard oil application</li>
                        </ul>
                        <a href="appointment.php" class="btn btn-primary pulse-button">Book This Service</a>
                    </div>
                </div>

                <div class="service-item" data-aos="fade-left">
                    <div class="service-info" data-aos="fade-right">
                        <div class="service-badge premium">Premium</div>
                        <h2 class="text-gradient">Luxury Beard Sculpting</h2>
                        <div class="price-tag">
                            <span class="currency">$</span>
                            <span class="amount">35</span>
                        </div>
                        <p>Our premium beard service includes a detailed consultation, custom beard design, hot towel treatment, and a complete beard makeover using artisanal techniques and premium products.</p>
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> 45 minute session</li>
                            <li><i class="fas fa-check"></i> Multiple hot towels</li>
                            <li><i class="fas fa-check"></i> Beard balm & oil included</li>
                            <li><i class="fas fa-check"></i> Beard-softening treatment</li>
                        </ul>
                        <a href="appointment.php" class="btn btn-primary pulse-button">Book This Service</a>
                    </div>
                    <div class="service-image video-container">
                        <img src="uploads/beard-trims1.jpg" alt="Luxury Beard Sculpting">
                    </div>
                </div>
            </div>
            
            <!-- Shaves Section -->
            <div class="services-category" id="shaves">
                <div class="service-item" data-aos="fade-right">
                    <div class="service-image video-container">
                        <img src="uploads/shaves1.png" alt="Classic Straight Razor Shave">
                    </div>
                    <div class="service-info" data-aos="fade-left">
                        <h2 class="text-gradient">Classic Straight Razor Shave</h2>
                        <div class="price-tag">
                            <span class="currency">$</span>
                            <span class="amount">30</span>
                        </div>
                        <p>Experience our traditional straight razor shave with hot towel preparation, lathering with premium shaving cream, and a precise clean shave followed by cold towel and aftershave application.</p>
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> 35 minute session</li>
                            <li><i class="fas fa-check"></i> Hot & cold towels</li>
                            <li><i class="fas fa-check"></i> Premium aftershave</li>
                        </ul>
                        <a href="appointment.php" class="btn btn-primary pulse-button">Book This Service</a>
                    </div>
                </div>

                <div class="service-item" data-aos="fade-left">
                    <div class="service-info" data-aos="fade-right">
                        <div class="service-badge exclusive">Exclusive</div>
                        <h2 class="text-gradient">Executive Royal Shave</h2>
                        <div class="price-tag">
                            <span class="currency">$</span>
                            <span class="amount">45</span>
                        </div>
                        <p>Our signature luxury shave experience includes a facial cleanse, multiple hot towel treatments, premium lather, two-pass straight razor shave, facial massage, cold towel finish, and premium skincare application.</p>
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> 50 minute session</li>
                            <li><i class="fas fa-check"></i> Facial cleansing</li>
                            <li><i class="fas fa-check"></i> Face massage included</li>
                            <li><i class="fas fa-check"></i> Premium skincare products</li>
                        </ul>
                        <a href="appointment.php" class="btn btn-primary pulse-button">Book This Service</a>
                    </div>
                    <div class="service-image before-after-container" data-aos="fade-left">
                        <div class="service-image video-container">
                            <img src="uploads/shaves2.jpg" alt="Executive Royal Shave">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Packages Section -->
            <div class="services-category" id="packages">
                <div class="service-item" data-aos="fade-right">
                    <div class="service-image video-container">
                        <img src="uploads/packages2.jpg" alt="Executive Royal Shave">
                    </div>
                    <div class="service-info" data-aos="fade-left">
                        <div class="service-badge popular">Most Popular</div>
                        <h2 class="text-gradient">The Gentleman's Package</h2>
                        <div class="price-tag">
                            <span class="currency">$</span>
                            <span class="amount">65</span>
                        </div>
                        <p>Our signature package includes a premium haircut, beard trim, and hot towel facial in one session. Complimentary beverage included with your service.</p>
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> 75 minute session</li>
                            <li><i class="fas fa-check"></i> Haircut & styling</li>
                            <li><i class="fas fa-check"></i> Beard shape & trim</li>
                            <li><i class="fas fa-check"></i> Hot towel treatment</li>
                            <li><i class="fas fa-check"></i> Complimentary drink</li>
                        </ul>
                        <a href="appointment.php" class="btn btn-primary pulse-button">Book This Service</a>
                    </div>
                </div>

                <div class="service-item" data-aos="fade-left">
                    <div class="service-info" data-aos="fade-right">
                        <div class="service-badge premium">Premium</div>
                        <h2 class="text-gradient">VIP Complete Experience</h2>
                        <div class="price-tag">
                            <span class="currency">$</span>
                            <span class="amount">95</span>
                        </div>
                        <p>Our ultimate grooming experience includes a luxury haircut, premium beard sculpting, straight razor shave, scalp massage, facial treatment, and complimentary grooming product to take home.</p>
                        <ul class="service-features">
                            <li><i class="fas fa-check"></i> 120 minute session</li>
                            <li><i class="fas fa-check"></i> Deluxe haircut</li>
                            <li><i class="fas fa-check"></i> Premium beard service</li>
                            <li><i class="fas fa-check"></i> Facial treatment</li>
                            <li><i class="fas fa-check"></i> Complimentary product</li>
                            <li><i class="fas fa-check"></i> Priority booking</li>
                        </ul>
                        <a href="appointment.php" class="btn btn-primary pulse-button">Book This Service</a>
                    </div>
                    <div class="service-image before-after-container" data-aos="fade-left">
                        <div class="service-image video-container">
                            <img src="uploads/package.jpg" alt="Executive Royal Shave">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Use the proper footer include -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="js/common.js"></script>
    <script src="js/effects.js"></script>
    <script src="js/services.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                mirror: false
            });
            
            // Service category switching
            const menuItems = document.querySelectorAll('.service-interactive-menu .menu-item');
            const serviceCategories = document.querySelectorAll('.services-category');
            
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    const target = this.getAttribute('data-target');
                    
                    // Toggle active class on menu items
                    menuItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Toggle active class on service categories
                    serviceCategories.forEach(category => {
                        category.classList.remove('active');
                        if (category.id === target) {
                            category.classList.add('active');
                            
                            // Add entrance animation
                            category.style.opacity = 0;
                            category.style.transform = 'translateY(20px)';
                            
                            setTimeout(() => {
                                category.style.transition = 'all 0.5s ease';
                                category.style.opacity = 1;
                                category.style.transform = 'translateY(0)';
                            }, 50);
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>

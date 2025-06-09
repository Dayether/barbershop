<?php 
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- Header -->
<header class="header">
    <div class="container">
        <div class="navbar">
            <a href="index.php" class="logo">
                <img src="uploads/tipuno.jpg" alt="Tipuno Barbershop" class="logo-img">
            </a>
            
            <!-- Mobile menu toggle -->
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="services.php" class="<?= basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : '' ?>">Services</a></li>
                <li><a href="shop.php" class="<?= basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'active' : '' ?>">Shop</a></li>
                <li><a href="appointment.php" class="<?= basename($_SERVER['PHP_SELF']) == 'appointment.php' ? 'active' : '' ?>">Book</a></li>
                <li><a href="contact.php" class="<?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>">Contact</a></li>
            </ul>
            
            <div class="header-actions">
                <?php if(isset($_SESSION['user'])): ?>
                    <div class="profile-dropdown">
                        <button id="profile-toggle" class="profile-toggle" aria-expanded="false">
                            <img src="<?= htmlspecialchars($_SESSION['user']['profile_pic']) ?>" alt="Profile" class="profile-img-small">
                            <span class="profile-name"><?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div id="profile-panel" class="profile-panel">
                            <div class="profile-header">
                                <img src="<?= htmlspecialchars($_SESSION['user']['profile_pic']) ?>" alt="Profile" class="profile-img">
                                <div class="profile-info">
                                    <span class="profile-username"><?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                                    <span class="profile-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></span>
                                </div>
                            </div>
                            <div class="profile-links">
                                <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                                <a href="my_appointments.php"><i class="fas fa-calendar"></i> My Appointments</a>
                                <a href="orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
                                <?php if(isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                                    <a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
                                <?php endif; ?>
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="auth-link"><i class="fas fa-user"></i> Login</a>
                <?php endif; ?>
                
                <?php 
                // Determine if this is a shop-related page
                $currentPage = basename($_SERVER['PHP_SELF']);
                $shopPages = ['shop.php', 'product.php', 'checkout.php', 'cart.php'];
                $isShopPage = in_array($currentPage, $shopPages);
                ?>
                
                <!-- Replace cart icon with direct link to checkout.php -->
                <a href="payment.php" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<script>
document.body.setAttribute('data-page-type', '<?= $isShopPage ? "shop" : "other" ?>');
</script>

<body <?php if(isset($_SESSION['user'])) echo 'data-user-id="' . $_SESSION['user']['id'] . '"'; ?> <?php if(isset($page_type)) echo 'data-page-type="' . $page_type . '"'; ?>>

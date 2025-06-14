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
            
            <ul class="nav-menu">
                <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="services.php" class="<?= basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : '' ?>">Services</a></li>
                <li><a href="shop.php" class="<?= basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'active' : '' ?>">Shop</a></li>
                <li><a href="appointment.php" class="<?= basename($_SERVER['PHP_SELF']) == 'appointment.php' ? 'active' : '' ?>">Book</a></li>
                <li><a href="contact.php" class="<?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>">Contact</a></li>
            </ul>
            
            <div class="header-actions">
                <?php if(isset($_SESSION['user'])): ?>
                    <!-- Example user/admin dropdown in header -->
                    <div class="user-dropdown">
                        <button class="user-dropdown-toggle" id="userDropdownBtn">
                            <img src="<?= htmlspecialchars($_SESSION['user']['profile_pic'] ?? 'images/default-profile.png') ?>" alt="Profile" class="profile-pic">
                            <span>
                                <?php 
                                // Display full name or default to 'Guest'
                                if (isset($_SESSION['user']['first_name']) && isset($_SESSION['user']['last_name'])) {
                                    echo htmlspecialchars($_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name']);
                                } else {
                                    echo 'Guest';
                                }
                                ?>
                            </span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="user-dropdown-menu" id="userDropdownMenu">
                            <a href="profile.php" class="dropdown-item<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? ' active' : '' ?>">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                            <a href="my_appointments.php" class="dropdown-item<?= basename($_SERVER['PHP_SELF']) === 'my_appointments.php' ? ' active' : '' ?>">
                                <i class="fas fa-calendar-alt"></i> My Appointments
                            </a>
                            <a href="orders.php" class="dropdown-item<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? ' active' : '' ?>">
                                <i class="fas fa-shopping-bag"></i> My Orders
                            </a>
                            <a href="my_messages.php" class="dropdown-item<?= basename($_SERVER['PHP_SELF']) === 'my_messages.php' ? ' active' : '' ?>">
                                <i class="fas fa-envelope"></i> My Messages
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
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

<body <?php if(isset($_SESSION['user'])) echo 'data-user-id="' . $_SESSION['user']['user_id'] . '"'; ?> <?php if(isset($page_type)) echo 'data-page-type="' . $page_type . '"'; ?>>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('userDropdownBtn');
    const menu = document.getElementById('userDropdownMenu');
    if (btn && menu) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('show');
        });
        document.addEventListener('click', function() {
            menu.classList.remove('show');
        });
    }
});
</script>
<style>
.user-dropdown { position: relative; display: inline-block; }
.user-dropdown-toggle { background: none; border: none; cursor: pointer; display: flex; align-items: center; }
.user-dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    min-width: 180px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    z-index: 1000;
    padding: 10px 0;
}
.user-dropdown-menu.show { display: block; }
.user-dropdown-menu .dropdown-item {
    display: flex;
    align-items: center;
    padding: 10px 18px;
    color: #333;
    text-decoration: none;
    transition: background 0.2s;
}
.user-dropdown-menu .dropdown-item.active,
.user-dropdown-menu .dropdown-item:hover {
    background: #f5f5f5;
    color: var(--primary-color, #c8a656);
}
.user-dropdown-menu .dropdown-divider {
    height: 1px;
    background: #eee;
    margin: 8px 0;
}
.profile-pic {
    width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; object-fit: cover;
}
</style>

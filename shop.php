<?php 
session_start();
require_once 'database.php';

// --- AJAX endpoint for cart actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $productId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $db = new Database();
    $result = $db->cartAddProduct($productId, $quantity);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Redirect to login if user is not logged in
if (!isset($_SESSION['user'])) {
    // Store the intended destination for redirect after login
    $_SESSION['redirect_after_login'] = 'shop.php';
    header('Location: login.php');
    exit;
}

// Reset cart if it's a new login session
if (isset($_SESSION['new_login']) && $_SESSION['new_login'] === true) {
    // Clear cart in session if we're using PHP session storage
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
    
    // Mark that we've handled this new login
    $_SESSION['new_login'] = false;
}

// Get products from database using OOP
$products = [];
try {
    $db = new Database();
    $products = $db->getActiveProductsWithCategory();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// If no products found, show message to admin
$admin_message = '';
if (count($products) == 0) {
    $admin_message = 'No products found in the database. Please add products or check your database connection.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/footer.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/shop.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/cart-styles.css">
    <link rel="stylesheet" href="css/cart-sidebar-pro.css">
    <link rel="stylesheet" href="css/shop-checkout-enhancements.css">
    <link rel="stylesheet" href="css/cart-notification.css">
    <link rel="stylesheet" href="css/banner-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-page-type="shop">
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner shop-banner">
        <div class="container">
            <h1>Premium Grooming Products</h1>
            <p>The same professional products we use in our barbershop, now available for home use</p>
        </div>
    </section>

    <!-- Shop Section -->
    <section class="shop-section">
        <div class="container">
            <?php if(!empty($admin_message) && isset($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                <div class="admin-alert">
                    <i class="fas fa-exclamation-circle"></i> <?= $admin_message ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['user'])): ?>
                <div class="order-status-bar">
                    <div class="order-links">
                        <a href="orders.php" class="order-link">
                            <i class="fas fa-clipboard-list"></i> My Orders
                        </a>
                        <a href="profile.php" class="order-link">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Add filter bar -->
            <div class="filter-bar">
                <div class="filter-categories">
                    <button class="filter-btn active" data-filter="all">All Products</button>
                    <button class="filter-btn" data-filter="hair">Hair Care</button>
                    <button class="filter-btn" data-filter="beard">Beard Care</button>
                    <button class="filter-btn" data-filter="tools">Tools</button>
                </div>
                <div class="product-count">Showing <?= count($products) ?> of <?= count($products) ?> products</div>
                <div class="sort-dropdown">
                    <select id="sort-products">
                        <option value="default">Default Sorting</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="name">Name: A to Z</option>
                    </select>
                </div>
            </div>
            
            <div class="shop-grid">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $index => $product): ?>
                        <div class="product-card" 
                             data-category="<?= $product['category'] ?>" 
                             data-price="<?= $product['price'] ?>"
                             data-original-order="<?= $index ?>">
                             
                             <?php 
                            // Randomly assign some badges for visual interest
                            $badges = ['bestseller', 'new', 'sale'];
                            $showRandomBadge = (mt_rand(0, 10) > 7) && $product['stock'] > 5;
                            ?>
                            
                            <?php if ($showRandomBadge): ?>
                                <div class="product-badge <?= $badges[array_rand($badges)] ?>">
                                    <?= ucfirst($badges[array_rand($badges)]) ?>
                                </div>
                            <?php elseif ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                                <div class="product-badge low-stock">Low Stock</div>
                            <?php elseif ($product['stock'] <= 0): ?>
                                <div class="product-badge sold-out">Sold Out</div>
                            <?php endif; ?>
                            
                            <div class="product-image-wrapper">
                                <img src="<?= !empty($product['image']) ? $product['image'] : 'images/product-placeholder.jpg' ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <button class="quick-view-btn">Quick View</button>
                            </div>
                            
                            <div class="product-details">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                
                                <div class="product-rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                    <span>(<?= rand(10, 50) ?>)</span>
                                </div>
                                
                                <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                                <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
                                
                                <button class="btn-add-to-cart" 
                                        data-id="<?= $product['product_id'] ?>" 
                                        data-name="<?= htmlspecialchars($product['name']) ?>" 
                                        data-price="<?= $product['price'] ?>"
                                        data-image="<?= !empty($product['image']) ? $product['image'] : 'images/product-placeholder.jpg' ?>"
                                        <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <i class="fas fa-cart-plus"></i> <?= $product['stock'] <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <p>No products available at the moment. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Floating View Cart Button -->
    <?php
    $cart_count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cart_count += (int)$item['quantity'];
        }
    }
    ?>
    <?php if ($cart_count > 0): ?>
    <a href="payment.php" class="floating-cart-btn" id="floating-cart-btn">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count"><?= $cart_count ?></span>
        View Cart / Checkout
    </a>
    <style>
    .floating-cart-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #2a9d8f;
        color: #fff;
        padding: 14px 28px 14px 18px;
        border-radius: 30px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        font-size: 1.1rem;
        font-weight: 600;
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: background 0.2s;
        text-decoration: none;
    }
    .floating-cart-btn:hover {
        background: #21867a;
        color: #fff;
        text-decoration: none;
    }
    .floating-cart-btn .cart-count {
        background: #fff;
        color: #2a9d8f;
        border-radius: 50%;
        padding: 2px 8px;
        font-size: 1rem;
        font-weight: bold;
        margin-right: 4px;
        margin-left: 2px;
    }
    @media (max-width: 600px) {
        .floating-cart-btn {
            right: 10px;
            bottom: 10px;
            padding: 10px 18px 10px 12px;
            font-size: 1rem;
        }
    }
    </style>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <script src="js/common.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Product add to cart functionality
        const addToCartButtons = document.querySelectorAll('.btn-add-to-cart');
        
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (!this.disabled) {
                    const item = {
                        id: this.dataset.id,
                        name: this.dataset.name,
                        price: this.dataset.price,
                        image: this.dataset.image
                    };
                    
                    // Visual feedback on button only
                    this.classList.add('added');
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i> Added';

                    // --- AJAX call to add to cart on server ---
                    fetch('shop.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=add_to_cart&id=${encodeURIComponent(item.id)}&quantity=1`
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Optionally update cart count in floating button
                        if (data.success && data.itemsCount !== undefined) {
                            let cartBtn = document.getElementById('floating-cart-btn');
                            if (cartBtn) {
                                let countElem = cartBtn.querySelector('.cart-count');
                                if (countElem) countElem.textContent = data.itemsCount;
                            } else if (data.itemsCount > 0) {
                                // Optionally reload to show floating cart button if it was hidden
                                location.reload();
                            }
                        }
                    });

                    // Reset button after delay
                    setTimeout(() => {
                        this.classList.remove('added');
                        this.innerHTML = originalText;
                    }, 1500);
                }
            });
        });
        
        // Filter functionality handled by shop.js
    });
    </script>
</body>
</html>
            });
        });
        
        // Filter functionality handled by shop.js
    });
    </script>
    <script src="js/cart.js"></script>
    <script src="js/shop.js"></script>
</body>
</html>


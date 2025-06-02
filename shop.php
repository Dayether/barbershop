<?php 
session_start();
require_once 'includes/db_connection.php';

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

// Get products from database
$products = [];
try {
    // Get all active products
    $stmt = $conn->prepare("SELECT id, name, description, price, image, stock FROM products WHERE active = 1");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Determine category based on product name or description
            $row['category'] = determineCategory($row['name'], $row['description']);
            $products[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// Helper function to determine a product's category
function determineCategory($name, $description) {
    $name_lower = strtolower($name);
    $desc_lower = strtolower($description);
    
    if (strpos($name_lower, 'pomade') !== false || 
        strpos($name_lower, 'clay') !== false || 
        strpos($name_lower, 'spray') !== false ||
        strpos($desc_lower, 'hair') !== false) {
        return 'hair';
    } elseif (strpos($name_lower, 'beard') !== false || 
             strpos($name_lower, 'shav') !== false) {
        return 'beard';
    } elseif (strpos($name_lower, 'razor') !== false || 
             strpos($name_lower, 'comb') !== false) {
        return 'tools';
    }
    
    return 'all'; // Default category
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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-page-type="shop">
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner">
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
                                        data-id="<?= $product['id'] ?>" 
                                        data-name="<?= htmlspecialchars($product['name']) ?>" 
                                        data-price="<?= $product['price'] ?>"
                                        data-image="<?= !empty($product['image']) ? $product['image'] : 'images/product-placeholder.jpg' ?>"
                                        <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <?= $product['stock'] <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
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

    <?php include 'includes/footer.php'; ?>

    <script src="js/common.js"></script>
    <script src="js/cart.js"></script>
    <script src="js/shop.js"></script>
</body>
</html>

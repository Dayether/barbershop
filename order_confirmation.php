<?php
session_start();
require_once 'database.php';

// Get order ID from URL parameter
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// If no order ID, redirect to shop
if ($order_id <= 0) {
    header('Location: shop.php');
    exit;
}

$order = null;
$order_items = [];

try {
    $db = new Database();
    $order = $db->getOrderDetails($order_id);

    if ($order) {
        // Verify the order belongs to the logged-in user if user is logged in
        if (isset($_SESSION['user']) && $order['user_id'] != $_SESSION['user']['user_id']) {
            if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
                header('Location: orders.php');
                exit;
            }
        }
        $order_items = $db->getOrderItems($order_id);

        // Calculate totals from items if not available in order
        $subtotal = 0;
        foreach ($order_items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        if (!isset($order['subtotal']) || $order['subtotal'] === null) {
            $order['subtotal'] = $subtotal;
        }
        if (!isset($order['shipping_cost']) || $order['shipping_cost'] === null) {
            $order['shipping_cost'] = 0;
        }
        if (!isset($order['tax_amount']) || $order['tax_amount'] === null) {
            $order['tax_amount'] = $subtotal * 0.1;
        }
        if (!isset($order['total_amount']) || $order['total_amount'] === null) {
            $order['total_amount'] = $order['subtotal'] + $order['shipping_cost'] + $order['tax_amount'];
        }
    } else {
        header('Location: shop.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error getting order details: " . $e->getMessage());
    header('Location: shop.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/color-themes.css">
    <link rel="stylesheet" href="css/order.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="order-confirmation-page">
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>Order Confirmation</h1>
            <p>Thank you for your purchase</p>
        </div>
    </section>

    <!-- Order Confirmation Content -->
    <section class="confirmation-section">
        <div class="container">
            <div class="order-confirmation">
                <div class="confirmation-header">
                    <i class="fas fa-check-circle"></i>
                    <h2>Order Confirmed!</h2>
                    <p>Thank you for your purchase. Your order has been received.</p>
                </div>

                <div class="order-details">
                    <div class="order-info">
                        <h3>Order Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="label">Order Reference:</span>
                                <span class="value"><?= htmlspecialchars($order['order_reference']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Order Date:</span>
                                <span class="value"><?= htmlspecialchars($order['order_date']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Total Amount:</span>
                                <span class="value">$<?= number_format($order['total_amount'], 2) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Status:</span>
                                <span class="value status-badge status-<?= strtolower($order['status']) ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="shipping-info">
                        <h3>Shipping Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="label">Name:</span>
                                <span class="value"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Email:</span>
                                <span class="value"><?= htmlspecialchars($order['email']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Phone:</span>
                                <span class="value"><?= htmlspecialchars($order['phone']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Address:</span>
                                <span class="value">
                                    <?= htmlspecialchars($order['address']) ?><br>
                                    <?= htmlspecialchars($order['city'] . ', ' . $order['zip']) ?><br>
                                    <?= htmlspecialchars($order['country']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="order-items">
                        <h3>Order Items</h3>
                        <div class="items-grid">
                            <?php foreach ($order_items as $item): ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                    </div>
                                    <div class="item-details">
                                        <h4><?= htmlspecialchars($item['name']) ?></h4>
                                        <div class="item-meta">
                                            <span class="quantity">Qty: <?= $item['quantity'] ?></span>
                                            <span class="price">$<?= number_format($item['price'], 2) ?></span>
                                        </div>
                                    </div>
                                    <div class="item-total">
                                        $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-summary">
                            <div class="summary-row">
                                <span class="summary-label">Subtotal:</span>
                                <span class="summary-value">$<?= number_format($order['subtotal'] ?? 0, 2) ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Shipping:</span>
                                <span class="summary-value">$<?= number_format($order['shipping_cost'] ?? 0, 2) ?></span>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Tax:</span>
                                <span class="summary-value">$<?= number_format($order['tax_amount'] ?? 0, 2) ?></span>
                            </div>
                            <div class="summary-row total">
                                <span class="summary-label">Total:</span>
                                <span class="summary-value">$<?= number_format($order['total_amount'] ?? 0, 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="confirmation-actions">
                    <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="orders.php" class="btn btn-primary">View All Orders</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="js/common.js"></script>
</body>
</html>
                            </div>
                            <div class="summary-row">
                                <span class="summary-label">Tax:</span>
                                <span class="summary-value">$<?= number_format($order['tax_amount'] ?? 0, 2) ?></span>
                            </div>
                            <div class="summary-row total">
                                <span class="summary-label">Total:</span>
                                <span class="summary-value">$<?= number_format($order['total_amount'] ?? 0, 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="confirmation-actions">
                    <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="orders.php" class="btn btn-primary">View All Orders</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="js/common.js"></script>
</body>
</html>

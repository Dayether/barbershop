<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connection.php';

// Get user's orders
$orders = [];
$user_id = $_SESSION['user']['user_id'];

// --- FIX: Handle address update for pending orders ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_address_order_id'])) {
    $order_id = intval($_POST['edit_address_order_id']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $zip = trim($_POST['zip']);
    $country = trim($_POST['country']);

    // Only allow update if order belongs to user and is pending
    $stmt = $conn->prepare("UPDATE orders SET address = ?, city = ?, zip = ?, country = ? WHERE order_id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ssssii", $address, $city, $zip, $country, $order_id, $user_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['order_message'] = "Shipping address updated successfully!";
        $_SESSION['order_message_type'] = "success";
    } else {
        $_SESSION['order_message'] = "Failed to update address. Please try again or check if the order is still pending.";
        $_SESSION['order_message_type'] = "error";
    }
    $stmt->close();
    // Add a session flag for JS toast
    $_SESSION['address_updated'] = ($_SESSION['order_message_type'] === 'success') ? 'success' : 'error';
    header("Location: orders.php");
    exit;
}
// --- END FIX ---

// Handle order cancellation (user can cancel their own pending orders)
if (isset($_GET['cancel_order_id']) && is_numeric($_GET['cancel_order_id'])) {
    $cancel_order_id = intval($_GET['cancel_order_id']);
    // Only allow cancelling user's own pending orders
    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $cancel_order_id, $user_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['order_message'] = "Order cancelled successfully!";
        $_SESSION['order_message_type'] = "success";
    } else {
        $_SESSION['order_message'] = "Failed to cancel order. It may have already been processed or cancelled.";
        $_SESSION['order_message_type'] = "error";
    }
    $stmt->close();
    $_SESSION['address_updated'] = $_SESSION['order_message_type'];
    header("Location: orders.php");
    exit;
}

// --- FIX: Handle order deletion for cancelled orders ---
if (isset($_GET['delete_order_id']) && is_numeric($_GET['delete_order_id'])) {
    $delete_order_id = intval($_GET['delete_order_id']);
    // Only allow deleting user's own cancelled orders
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ? AND user_id = ? AND status = 'cancelled'");
    $stmt->bind_param("ii", $delete_order_id, $user_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['order_message'] = "Order deleted successfully!";
        $_SESSION['order_message_type'] = "success";
    } else {
        $_SESSION['order_message'] = "Failed to delete order. Only cancelled orders can be deleted.";
        $_SESSION['order_message_type'] = "error";
    }
    $stmt->close();
    $_SESSION['address_updated'] = $_SESSION['order_message_type'];
    header("Location: orders.php");
    exit;
}
// --- END FIX ---

try {
    // Get orders
    $stmt = $conn->prepare("
        SELECT o.*, DATE_FORMAT(o.created_at, '%M %d, %Y') as order_date 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $order_items = [];
        
        $stmt = $conn->prepare("
            SELECT oi.*, p.name, p.image 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.product_id 
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $order['order_id']);
        $stmt->execute();
        $items_result = $stmt->get_result();
        
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
        }
        
        $order['items'] = $order_items;
    }
    
} catch (Exception $e) {
    error_log("Error retrieving orders: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add iziToast CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <style>
        .orders-section {
            padding: var(--space-xxl) 0;
            background-color: var(--background-light);
        }
        
        .orders-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .order-card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .order-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #fcfcfc;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .order-id {
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--dark-color);
        }
        
        .order-id i {
            color: var(--secondary-color);
        }
        
        .order-date {
            color: var(--text-light);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .order-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .order-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .order-total {
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 1.1rem;
        }
        
        .order-items {
            padding: 20px 25px;
            background-image: linear-gradient(to bottom, rgba(250,250,250,0.5), rgba(255,255,255,1));
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-light);
            gap: 15px;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-image {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border: 1px solid var(--border-light);
        }
        
        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .order-item:hover .order-item-image img {
            transform: scale(1.1);
        }
        
        .order-item-details {
            flex-grow: 1;
        }
        
        .order-item-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-color);
            font-size: 1rem;
        }
        
        .order-item-price {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .order-item-quantity {
            background-color: #f0f0f0;
            padding: 2px 10px;
            border-radius: 20px;
            margin-left: 10px;
            font-size: 0.8rem;
            color: var(--dark-color);
        }
        
        .order-item-subtotal {
            color: var(--secondary-color);
            font-weight: 600;
            font-size: 1.05rem;
            white-space: nowrap;
        }
        
        .order-footer {
            padding: 15px 25px;
            border-top: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #fcfcfc;
        }
        
        .view-details-btn {
            font-size: 0.9rem;
            color: var(--secondary-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .view-details-btn:hover {
            color: var(--dark-color);
        }
        
        .order-actions a {
            margin-left: 15px;
        }
        
        .empty-orders {
            text-align: center;
            padding: 80px 20px;
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .empty-orders i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); opacity: 0.8; }
        }
        
        .empty-orders h3 {
            margin-bottom: 15px;
            font-size: 1.5rem;
            color: var(--dark-color);
        }
        
        .empty-orders p {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto 25px;
            font-size: 1.05rem;
        }
        
        @media (max-width: 768px) {
            .order-header, .order-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .order-meta {
                width: 100%;
            }
            
            .order-item {
                flex-wrap: wrap;
            }
            
            .order-item-image {
                width: 60px;
                height: 60px;
            }
            
            .order-item-subtotal {
                margin-top: 10px;
                width: 100%;
                text-align: right;
            }
            
            .order-footer {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .order-actions {
                display: flex;
                width: 100%;
            }
            
            .order-actions a {
                margin-left: 0;
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>My Orders</h1>
            <p>View and track your recent orders</p>
        </div>
    </section>

    <!-- Orders Section -->
    <section class="orders-section">
        <div class="container">
            <!-- Add profile tabs navigation -->
            <div class="profile-tabs">
                <a href="profile.php#profile-info" class="tab">
                    <i class="fas fa-user"></i> <span>Profile Information</span>
                </a>
                <a href="profile.php#security" class="tab">
                    <i class="fas fa-lock"></i> <span>Security</span>
                </a>
                <a href="my_appointments.php" class="tab">
                    <i class="fas fa-calendar-alt"></i> <span>My Appointments</span>
                </a>
                <a href="orders.php" class="tab active">
                    <i class="fas fa-shopping-bag"></i> <span>My Orders</span>
                </a>
            </div>
            
            <div class="orders-container">
                <?php if (count($orders) > 0): ?>
                    <?php foreach ($orders as $order): ?>
                        <?php
                            // Calculate order subtotal from items
                            $order_subtotal = 0;
                            foreach ($order['items'] as $item) {
                                $order_subtotal += $item['price'] * $item['quantity'];
                            }
                            $shipping = 5.00;
                            $tax = $order_subtotal * 0.08;
                            $order_total = $order_subtotal + $shipping + $tax;
                            $is_pending = strtolower($order['status']) === 'pending';
                        ?>
                        <div class="order-card" data-status="<?= strtolower($order['status']) ?>">
                            <div class="order-header">
                                <div class="order-id">
                                    <i class="fas fa-receipt"></i>
                                    Order #<?= htmlspecialchars(substr($order['order_reference'], 0, 12)) ?>
                                </div>
                                <div class="order-meta">
                                    <div class="order-date">
                                        <i class="far fa-calendar-alt"></i> <?= htmlspecialchars($order['order_date']) ?>
                                    </div>
                                    <div class="order-status status-<?= strtolower($order['status']) ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </div>
                                    <div class="order-total">
                                        $<?= number_format($order_total, 2) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <div class="order-item-image">
                                            <img src="<?= htmlspecialchars($item['image'] ?: 'images/product-placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                        </div>
                                        <div class="order-item-details">
                                            <div class="order-item-name">
                                                <?= htmlspecialchars($item['name']) ?>
                                                <span class="order-item-quantity">x<?= $item['quantity'] ?></span>
                                            </div>
                                            <div class="order-item-price">
                                                $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                            </div>
                                        </div>
                                        <div class="order-item-subtotal">
                                            $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="order-footer">
                                <div class="view-details-btn" onclick="toggleOrderDetails(this)">
                                    <i class="fas fa-chevron-down"></i> View Order Details
                                </div>
                                <div class="order-actions">
                                    <a href="shop.php" class="btn btn-sm btn-secondary">Buy Again</a>
                                    <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                    <a href="orders.php?cancel_order_id=<?= $order['order_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this order?');">
                                        <i class="fas fa-times-circle"></i> Cancel Order
                                    </a>
                                    <?php elseif ($order['status'] === 'cancelled'): ?>
                                    <a href="orders.php?delete_order_id=<?= $order['order_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to permanently delete this cancelled order?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Editable address section for pending orders -->
                            <?php if ($is_pending): ?>
                            <div class="order-edit-address" style="padding: 20px 25px; border-top: 1px solid var(--border-light); background: #fffbe7;">
                                <form method="post" action="orders.php" class="edit-address-form">
                                    <input type="hidden" name="edit_address_order_id" value="<?= $order['order_id'] ?>">
                                    <div style="margin-bottom:10px;">
                                        <strong>Edit Shipping Address:</strong>
                                    </div>
                                    <div class="form-row" style="display:flex;gap:10px;flex-wrap:wrap;">
                                        <input type="text" name="address" value="<?= htmlspecialchars($order['address']) ?>" placeholder="Address" required style="flex:2;">
                                        <input type="text" name="city" value="<?= htmlspecialchars($order['city']) ?>" placeholder="City" required style="flex:1;">
                                        <input type="text" name="zip" value="<?= htmlspecialchars($order['zip']) ?>" placeholder="ZIP" required style="flex:1;">
                                        <input type="text" name="country" value="<?= htmlspecialchars($order['country']) ?>" placeholder="Country" required style="flex:1;">
                                    </div>
                                    <div style="margin-top:10px;">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Address</button>
                                    </div>
                                </form>
                            </div>
                            <?php else: ?>
                            <div class="order-edit-address" style="padding: 20px 25px; border-top: 1px solid var(--border-light); background: #f9f9f9;">
                                <strong>Shipping Address:</strong>
                                <div><?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['zip']) ?>, <?= htmlspecialchars($order['country']) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No Orders Yet</h3>
                        <p>Looks like you haven't placed any orders yet. Start shopping to see your orders here.</p>
                        <a href="shop.php" class="btn btn-primary">Shop Now</a>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['order_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['order_message_type'] ?>">
                        <?= htmlspecialchars($_SESSION['order_message']) ?>
                    </div>
                    <?php 
                    // Clear the message after displaying
                    unset($_SESSION['order_message']);
                    unset($_SESSION['order_message_type']);
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Add iziToast JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize iziToast
            iziToast.settings({
                timeout: 5000,
                resetOnHover: true,
                position: 'topRight',
                transitionIn: 'flipInX',
                transitionOut: 'flipOutX',
            });

            // Check for URL parameters to show appropriate notifications
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('order_placed') && urlParams.get('order_placed') === 'success') {
                iziToast.success({
                    title: 'Order Placed',
                    message: 'Your order has been placed successfully!',
                    icon: 'fas fa-check-circle'
                });
            }
            
            if (urlParams.has('order_cancelled') && urlParams.get('order_cancelled') === 'success') {
                iziToast.success({
                    title: 'Order Cancelled',
                    message: 'Your order has been cancelled successfully',
                    icon: 'fas fa-times-circle'
                });
            }
            
            if (urlParams.has('error')) {
                const errorType = urlParams.get('error');
                let errorMsg = 'There was an error processing your request';
                
                switch(errorType) {
                    case 'not_found':
                        errorMsg = 'The order you requested could not be found';
                        break;
                    case 'permission':
                        errorMsg = 'You do not have permission to perform this action';
                        break;
                    case 'status':
                        errorMsg = 'This order cannot be modified in its current status';
                        break;
                }
                
                iziToast.error({
                    title: 'Error',
                    message: errorMsg,
                    icon: 'fas fa-exclamation-circle'
                });
            }
            
            // Show toast for address update (after redirect)
            <?php if (isset($_SESSION['address_updated'])): ?>
                <?php if ($_SESSION['address_updated'] === 'success'): ?>
                    iziToast.success({
                        title: 'Success',
                        message: 'Shipping address updated successfully!',
                        icon: 'fas fa-check-circle'
                    });
                <?php else: ?>
                    iziToast.error({
                        title: 'Error',
                        message: 'Failed to update address. Please try again or check if the order is still pending.',
                        icon: 'fas fa-exclamation-circle'
                    });
                <?php endif; ?>
                <?php unset($_SESSION['address_updated']); ?>
            <?php endif; ?>

            // Sweet alert for address update
            <?php if (isset($_GET['address_updated'])): ?>
                <?php if (isset($_SESSION['order_message']) && $_SESSION['order_message_type'] === 'success'): ?>
                    iziToast.success({
                        title: 'Success',
                        message: '<?php echo addslashes($_SESSION['order_message']); ?>',
                        icon: 'fas fa-check-circle'
                    });
                <?php elseif (isset($_SESSION['order_message'])): ?>
                    iziToast.error({
                        title: 'Error',
                        message: '<?php echo addslashes($_SESSION['order_message']); ?>',
                        icon: 'fas fa-exclamation-circle'
                    });
                <?php endif; ?>
                <?php
                unset($_SESSION['order_message']);
                unset($_SESSION['order_message_type']);
                ?>
            <?php endif; ?>

            // IMPORTANT: Execute this code immediately when page loads
            // Set Orders tab as active
            const ordersTabs = document.querySelectorAll('.profile-tabs .tab');
            ordersTabs.forEach(tab => {
                if (tab.getAttribute('href') === 'orders.php' || 
                    tab.getAttribute('href') && tab.getAttribute('href').endsWith('orders.php')) {
                    // Make sure to remove active class from other tabs first
                    ordersTabs.forEach(t => t.classList.remove('active'));
                    // Then add active class to orders tab
                    tab.classList.add('active');
                }
            });
            
            // Check if we came from profile page with orders tab clicked
            if (localStorage.getItem('activeTab') === 'orders') {
                // Clear it after we've used it
                localStorage.removeItem('activeTab');
                
                iziToast.info({
                    title: 'My Orders',
                    message: 'Viewing your order history',
                    icon: 'fas fa-shopping-bag',
                    iconColor: '#2a9d8f'
                });
            }
            
            // Order cancellation confirmation
            window.confirmCancelOrder = function(orderId) {
                iziToast.question({
                    timeout: 20000,
                    close: false,
                    overlay: true,
                    displayMode: 'once',
                    id: 'question',
                    zindex: 999,
                    title: 'Cancel Order',
                    message: 'Are you sure you want to cancel this order? This action cannot be undone.',
                    position: 'center',
                    buttons: [
                        ['<button><b>Yes, Cancel Order</b></button>', function (instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            
                            // Show processing toast
                            iziToast.info({
                                title: 'Processing',
                                message: 'Cancelling your order...',
                                icon: 'fas fa-spinner fa-spin',
                                timeout: false,
                                id: 'cancel-order-toast'
                            });
                            
                            // Redirect to cancel order page
                            window.location.href = 'cancel_order.php?id=' + orderId;
                        }, true],
                        ['<button>No, Keep Order</button>', function (instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        }]
                    ]
                });
            };
            
            // Toggle order details functionality
            window.toggleOrderDetails = function(element) {
                const orderCard = element.closest('.order-card');
                const orderItems = orderCard.querySelector('.order-items');
                
                // Toggle items visibility with smooth animation
                if (orderItems.style.display === 'none' || !orderItems.style.display) {
                    orderItems.style.display = 'block';
                    orderItems.style.maxHeight = '0';
                    orderItems.style.opacity = '0';
                    
                    // Trigger reflow
                    void orderItems.offsetWidth;
                    
                    // Add transition
                    orderItems.style.transition = 'max-height 0.5s ease, opacity 0.4s ease';
                    orderItems.style.maxHeight = orderItems.scrollHeight + 'px';
                    orderItems.style.opacity = '1';
                    
                    element.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Order Details';
                    
                    // Show toast notification when expanding details
                    const orderNumber = orderCard.querySelector('.order-id').textContent.trim();
                    iziToast.info({
                        title: 'Order Details',
                        message: `Viewing details for ${orderNumber}`,
                        icon: 'fas fa-info-circle',
                        iconColor: '#2a9d8f',
                        timeout: 2000
                    });
                } else {
                    orderItems.style.maxHeight = '0';
                    orderItems.style.opacity = '0';
                    
                    setTimeout(() => {
                        orderItems.style.display = 'none';
                    }, 500);
                    
                    element.innerHTML = '<i class="fas fa-chevron-down"></i> View Order Details';
                }
            };
            
            // Initialize - hide all order details by default but with smooth animation
            const orderItems = document.querySelectorAll('.order-items');
            orderItems.forEach(item => {
                item.style.display = 'none';
            });
            
            // Add toast notification for "Buy Again" button
            const buyAgainButtons = document.querySelectorAll('.order-actions .btn-secondary');
            buyAgainButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    iziToast.info({
                        title: 'Shop',
                        message: 'Redirecting to shop page...',
                        icon: 'fas fa-shopping-cart',
                        iconColor: '#2a9d8f'
                    });
                });
            });
            
            // Show welcome toast when first visiting the orders page
            if (!sessionStorage.getItem('ordersPageVisited')) {
                setTimeout(() => {
                    iziToast.info({
                        title: 'My Orders',
                        message: 'View and track your order history',
                        icon: 'fas fa-shopping-bag',
                        iconColor: '#2a9d8f',
                        position: 'bottomRight',
                        timeout: 4000
                    });
                    sessionStorage.setItem('ordersPageVisited', 'true');
                }, 1000);
            }
            
            <?php if (count($orders) === 0): ?>
            // Show suggestions toast for empty orders
            setTimeout(() => {
                iziToast.info({
                    title: 'Start Shopping',
                    message: 'Explore our premium grooming products!',
                    icon: 'fas fa-tags',
                    iconColor: '#2a9d8f',
                    position: 'bottomRight',
                    timeout: 8000,
                    buttons: [
                        ['<button>Shop Now</button>', function (instance, toast) {
                            window.location.href = 'shop.php';
                        }]
                    ]
                });
            }, 3000);
            <?php endif; ?>
        });
    </script>
</body>
</html>

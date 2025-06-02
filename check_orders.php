<?php
session_start();
require_once 'includes/db_connection.php';

// Check if user is admin (for security)
$is_admin = isset($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';

// Get the last 5 orders
try {
    $stmt = $conn->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                           FROM orders o 
                           LEFT JOIN order_items oi ON o.id = oi.order_id 
                           GROUP BY o.id 
                           ORDER BY o.created_at DESC 
                           LIMIT 5");
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/footer.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Check - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .order-check-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
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
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .order-row {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .order-row:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .access-denied {
            text-align: center;
            padding: 50px 20px;
            color: #721c24;
            background-color: #f8d7da;
            border-radius: 10px;
        }
        .login-link {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="order-check-container">
        <h1>Recent Orders Check</h1>
        <p>This utility checks if orders are being properly saved to the database.</p>
        
        <?php if ($is_admin): ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php else: ?>
                <?php if (count($orders) > 0): ?>
                    <h2>Last <?= count($orders) ?> Orders</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Items</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= $order['id'] ?></td>
                                    <td><?= $order['order_reference'] ?></td>
                                    <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                                    <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                    <td><?= $order['item_count'] ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <p>✅ Orders are being correctly saved to your database!</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <p>No orders found in the database. Once orders are placed, they will appear here.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="access-denied">
                <h2><i class="fas fa-exclamation-triangle"></i> Access Denied</h2>
                <p>You must be logged in as an admin to view this page.</p>
                <a href="login.php" class="login-link">Login</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connection.php';

$user_id = $_SESSION['user']['user_id'];
$success = false;
$error_message = '';

// Check if order ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $order_id = $_GET['id'];
    
    // Verify the order belongs to the logged-in user
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        // Check if order is in a cancellable state (not delivered or already cancelled)
        if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled') {
            // Update order status to cancelled (without updated_at field)
            $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            
            if ($stmt->execute()) {
                $success = true;
                
                // Add cancellation record to order history - handle missing table gracefully
                try {
                    $comment = "Order cancelled by customer";
                    $history_stmt = $conn->prepare("INSERT INTO order_history (order_id, status, comment, created_at) VALUES (?, 'cancelled', ?, NOW())");
                    $history_stmt->bind_param("is", $order_id, $comment);
                    $history_stmt->execute();
                } catch (mysqli_sql_exception $e) {
                    // Log the error but continue - history logging is secondary
                    error_log("Order history table error: " . $e->getMessage());
                    // We'll still consider the cancellation successful even if history logging fails
                }
                
                // Redirect to orders page with success message
                $_SESSION['order_message'] = "Your order has been successfully cancelled.";
                $_SESSION['order_message_type'] = "success";
            } else {
                $error_message = "Failed to cancel order. Please try again.";
            }
        } else {
            $error_message = "This order cannot be cancelled.";
        }
    } else {
        $error_message = "Order not found or you don't have permission to cancel it.";
    }
} else {
    $error_message = "Invalid order ID.";
}

// Handle error case
if (!$success) {
    $_SESSION['order_message'] = $error_message;
    $_SESSION['order_message_type'] = "error";
}

// Redirect back to orders page
header('Location: orders.php');
exit;
?>

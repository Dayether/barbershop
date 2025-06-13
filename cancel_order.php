<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$success = false;
$error_message = '';

// Check if order ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $order_id = intval($_GET['id']);
    try {
        $db = new Database();
        $result = $db->cancelOrder($order_id, $user_id);
        $success = $result['success'];
        $error_message = $result['error_message'];

        if ($success) {
            $_SESSION['order_message'] = "Your order has been successfully cancelled.";
            $_SESSION['order_message_type'] = "success";
        } else {
            $_SESSION['order_message'] = $error_message;
            $_SESSION['order_message_type'] = "error";
        }
    } catch (Exception $e) {
        $_SESSION['order_message'] = "Failed to cancel order. Please try again.";
        $_SESSION['order_message_type'] = "error";
    }
} else {
    $_SESSION['order_message'] = "Invalid order ID.";
    $_SESSION['order_message_type'] = "error";
}

// Redirect back to orders page
header('Location: orders.php');
exit;
?>
                   

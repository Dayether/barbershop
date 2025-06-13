<?php
session_start();
require_once 'database.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: payment.php');
    exit;
}

$db = new Database();

// Prepare order data
$order_data = [
    'user_id' => isset($_SESSION['user']['user_id']) ? $_SESSION['user']['user_id'] : null,
    'first_name' => trim($_POST['first_name'] ?? ''),
    'last_name' => trim($_POST['last_name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'address' => trim($_POST['address'] ?? ''),
    'city' => trim($_POST['city'] ?? ''),
    'zip' => trim($_POST['zip'] ?? ''),
    'country' => trim($_POST['country'] ?? ''),
    'payment_method' => $_POST['payment_method'] ?? '',
    'card_number' => $_POST['card_number'] ?? '',
    'card_name' => $_POST['card_name'] ?? '',
    'expiry_date' => $_POST['expiry_date'] ?? '',
    'cvv' => $_POST['cvv'] ?? '',
    'order_total' => isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0,
    'order_items' => isset($_POST['cart_data']) ? json_decode($_POST['cart_data'], true) : (isset($_SESSION['cart']) ? array_values($_SESSION['cart']) : [])
];

// Map order_total to total_amount for DB compatibility
$order_data['total_amount'] = $order_data['order_total'];

// Validate order data using a method in Database
$errors = $db->validateOrderData($order_data);

if (!empty($errors)) {
    $_SESSION['payment_errors'] = $errors;
    header('Location: payment.php');
    exit;
}

if (empty($order_data['order_items']) || $order_data['order_total'] <= 0) {
    $_SESSION['payment_errors'] = ['Your cart is empty. Please add products before checkout.'];
    header('Location: payment.php');
    exit;
}

try {
    // Pass total_amount to createOrder
    $result = $db->createOrder($order_data, $order_data['order_items']);

    if ($result['success']) {
        unset($_SESSION['cart']);
        $_SESSION['order_success'] = [
            'reference' => isset($result['order_reference']) ? $result['order_reference'] : (isset($result['order_id']) ? $result['order_id'] : ''),
            'total' => $order_data['order_total']
        ];
        header('Location: payment_success.php?order_ref=' . urlencode($_SESSION['order_success']['reference']));
        exit;
    } else {
        $_SESSION['payment_errors'] = [$result['error_message']];
        header('Location: payment.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['payment_errors'] = ['System error: ' . $e->getMessage()];
    header('Location: payment.php');
    exit;
}

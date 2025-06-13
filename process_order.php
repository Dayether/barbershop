<?php
session_start();
require_once 'database.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: shop.php');
    exit;
}

// Get form data
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_SESSION['user']['email']) ? $_SESSION['user']['email'] : 'customer@example.com';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$city = isset($_POST['city']) ? trim($_POST['city']) : '';
$zip = isset($_POST['zip']) ? trim($_POST['zip']) : '';
$country = isset($_POST['country']) ? trim($_POST['country']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$order_items_json = isset($_POST['order_items']) ? $_POST['order_items'] : '';
$order_total = isset($_POST['order_total']) ? (float)$_POST['order_total'] : 0;

// Decode order items
$order_items = [];
if (!empty($order_items_json)) {
    $order_items = json_decode($order_items_json, true) ?: [];
} else {
    $session_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : '';
    if (!empty($session_cart)) {
        $order_items = json_decode($session_cart, true) ?: [];
    }
}

// Validate data
if (empty($first_name) || empty($last_name) || empty($address) || 
    empty($city) || empty($zip) || empty($country) || empty($phone)) {
    $_SESSION['error'] = 'Please fill in all required fields';
    header('Location: payment.php');
    exit;
}

if (empty($order_items) || $order_total <= 0) {
    $_SESSION['error'] = 'Your cart is empty';
    header('Location: shop.php');
    exit;
}

try {
    $db = new Database();
    $user_id = isset($_SESSION['user']['user_id']) ? $_SESSION['user']['user_id'] : null;
    $result = $db->createOrder([
        'user_id' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'address' => $address,
        'city' => $city,
        'zip' => $zip,
        'country' => $country,
        'phone' => $phone,
        'order_total' => $order_total,
        'order_items' => $order_items
    ]);

    if ($result['success']) {
        unset($_SESSION['cart']);
        $_SESSION['last_order'] = [
            'id' => $result['order_id'],
            'reference' => $result['order_reference'],
            'total' => $order_total
        ];
        header("Location: order_confirmation.php?order_id=" . $result['order_id']);
        exit;
    } else {
        $_SESSION['error'] = $result['error_message'];
        header('Location: payment.php?error=1');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'There was a problem processing your order: ' . $e->getMessage();
    header('Location: payment.php?error=1');
    exit;
}
?>

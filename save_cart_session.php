<?php
session_start();

// Get the cart data from the POST request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($data['cart']) && is_array($data['cart'])) {
    // Store the cart in the session
    $_SESSION['cart'] = $data['cart'];
    $response = ['success' => true, 'message' => 'Cart saved successfully'];
} else {
    // Error handling
    $response = ['success' => false, 'message' => 'Invalid cart data'];
}

// Send the response
header('Content-Type: application/json');
echo json_encode($response);
?>

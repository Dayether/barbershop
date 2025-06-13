<?php
session_start();
require_once 'database.php';

// Get the cart data from the POST request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Ensure cart is always an array
if (!isset($data['cart']) || !is_array($data['cart'])) {
    $data['cart'] = [];
}

$db = new Database();
$response = $db->saveCartSession($data);

// Send the response
header('Content-Type: application/json');
echo json_encode($response);
?>

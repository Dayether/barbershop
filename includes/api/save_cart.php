<?php
session_start();

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Initialize response
$response = ['success' => false];

// Process cart data if valid
if (isset($data['cart']) && is_array($data['cart'])) {
    // Store cart in session
    $_SESSION['cart'] = $data['cart'];
    
    // Count items
    $itemCount = 0;
    foreach ($data['cart'] as $item) {
        $itemCount += $item['quantity'];
    }
    
    // Success response
    $response = [
        'success' => true,
        'itemCount' => $itemCount
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

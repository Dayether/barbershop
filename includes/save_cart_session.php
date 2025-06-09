<?php
/**
 * Save cart data to PHP session
 */
session_start();

// Get JSON from request body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verify the cart data
$response = [
    'success' => false,
    'message' => 'Invalid cart data'
];

if (isset($data['cart']) && is_array($data['cart'])) {
    // Save to PHP session
    $_SESSION['cart'] = $data['cart'];
    
    // Calculate cart totals
    $item_count = 0;
    $subtotal = 0;
    
    foreach ($data['cart'] as $item) {
        $item_count += $item['quantity'];
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $response = [
        'success' => true,
        'message' => 'Cart updated successfully',
        'cart_count' => $item_count,
        'cart_subtotal' => number_format($subtotal, 2)
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;

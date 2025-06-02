<?php
session_start();

// Log for debugging
function debug_log($message) {
    $log_file = 'cart_session.log';
    $formatted_message = date('[Y-m-d H:i:s] ') . (is_string($message) ? $message : print_r($message, true)) . "\n";
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

// Get the raw POST data
$raw_data = file_get_contents("php://input");
debug_log("Received data: " . $raw_data);

// Decode the JSON data
$data = json_decode($raw_data, true);
debug_log("Decoded data: " . print_r($data, true));

if (isset($data['cart']) && is_array($data['cart'])) {
    // Store cart in session
    $_SESSION['cart'] = json_encode($data['cart']);
    debug_log("Saved cart to session: " . count($data['cart']) . " items");
    
    // Return success
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Cart saved to session', 'items' => count($data['cart'])]);
} else {
    // Return error
    debug_log("Invalid cart data received");
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid cart data']);
}
?>

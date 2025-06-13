<?php
// Start output buffering to prevent accidental output before JSON
ob_start();
header('X-Debug-Output-Buffer: ' . ob_get_length()); // Debug header for AJAX troubleshooting
session_start();
require_once 'database.php';

header('Content-Type: application/json');

$db = new Database();

// Support both JSON and form-encoded requests
$input = $_POST;
if (empty($input) && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $input = $json;
    }
}

function build_cart_response($base = []) {
    $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    $subtotal = 0;
    $itemsCount = 0;
    // Ensure each cart item has an 'id' property and build a numerically indexed array
    $cart_items = [];
    foreach ($cart as $key => $item) {
        $item['id'] = isset($item['id']) ? $item['id'] : $key;
        $cart_items[] = $item;
        $subtotal += $item['price'] * $item['quantity'];
        $itemsCount += $item['quantity'];
    }
    $shipping = 5.00;
    $tax = $subtotal * 0.08;
    $total = $subtotal + $shipping + $tax;
    if (isset($_SESSION['promo_code']) && isset($_SESSION['promo_code']['discount'])) {
        $total -= floatval($_SESSION['promo_code']['discount']);
    }
    $base['itemsCount'] = $itemsCount;
    $base['subtotal'] = $subtotal;
    $base['tax'] = $tax;
    $base['total'] = $total;
    $base['shipping'] = $shipping;
    $base['cart'] = $cart_items;
    return $base;
}

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['action'])) {
    $action = $input['action'];
    if ($action === 'update' && isset($input['product_id'], $input['quantity'])) {
        $productId = intval($input['product_id']);
        $quantity = intval($input['quantity']);
        $result = $db->cartUpdateQuantity($productId, $quantity);
        // Always set itemSubtotal for the updated product (0 if removed)
        if (isset($_SESSION['cart'][$productId])) {
            $result['itemSubtotal'] = $_SESSION['cart'][$productId]['price'] * $_SESSION['cart'][$productId]['quantity'];
        } else {
            $result['itemSubtotal'] = 0;
        }
        $result['product_id'] = $productId; // <-- Add this line
        $response = build_cart_response($result);
    } elseif ($action === 'remove' && isset($input['product_id'])) {
        $productId = intval($input['product_id']);
        $result = $db->cartRemoveProduct($productId);
        // Always set itemSubtotal to 0 for removed item
        $result['itemSubtotal'] = 0;
        $result['product_id'] = $productId; // <-- Add this line
        $response = build_cart_response($result);
    } elseif ($action === 'apply_promo' && isset($input['code'], $input['discount'])) {
        $code = $input['code'];
        $discount = floatval($input['discount']);
        $result = $db->cartApplyPromo($code, $discount);
        $response = build_cart_response($result);
    } elseif ($action === 'add' && isset($input['product_id'], $input['quantity'])) {
        $productId = intval($input['product_id']);
        $quantity = intval($input['quantity']);
        $result = $db->cartAddProduct($productId, $quantity);
        $response = build_cart_response($result);
    } elseif ($action === 'save_cart' && isset($input['cart']) && is_array($input['cart'])) {
        $result = $db->saveCartSession(['cart' => $input['cart']]);
        $response = build_cart_response($result);
    }
}

// Clean (discard) any accidental output before JSON
ob_end_clean();
echo json_encode($response);
exit;

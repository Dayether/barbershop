<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/classes/Order.php';

// For debugging - writes to file for easier troubleshooting
function debug_log($message) {
    // Log to both file and PHP error log
    $log_file = 'order_debug.log';
    $formatted_message = date('[Y-m-d H:i:s] ') . (is_string($message) ? $message : print_r($message, true)) . "\n";
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
    error_log('[ORDER DEBUG] ' . (is_string($message) ? $message : print_r($message, true)));
}

// Test database connection
try {
    if ($conn->connect_error) {
        debug_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Database connection failed: " . $conn->connect_error);
    } else {
        debug_log("Database connection successful");
    }
} catch (Exception $e) {
    debug_log("Database connection test exception: " . $e->getMessage());
}

// Check if orders table has required columns
try {
    $missingColumns = [];
    $requiredColumns = ['order_reference', 'first_name', 'last_name', 'email', 'address', 'city', 'zip', 'country', 'phone'];
    
    foreach ($requiredColumns as $column) {
        $result = $conn->query("SHOW COLUMNS FROM orders LIKE '$column'");
        if ($result->num_rows == 0) {
            $missingColumns[] = $column;
        }
    }
    
    if (!empty($missingColumns)) {
        debug_log("Missing required columns in orders table: " . implode(", ", $missingColumns));
        $_SESSION['error'] = "Database setup issue. Please run update_orders_table.php first.";
        header('Location: payment.php?error=db_setup');
        exit;
    }
    
    debug_log("All required columns present in orders table");
} catch (Exception $e) {
    debug_log("Error checking table structure: " . $e->getMessage());
    $_SESSION['error'] = "Database error. Please try again or contact support.";
    header('Location: payment.php?error=db_error');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_log("Not a POST request. Redirecting to shop.php");
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

// Get order data
$order_items_json = isset($_POST['order_items']) ? $_POST['order_items'] : '';
$order_total = isset($_POST['order_total']) ? (float)$_POST['order_total'] : 0;

// Debug incoming data
debug_log("Order form submitted with:");
debug_log("Name: $first_name $last_name");
debug_log("Email: $email");
debug_log("Address: $address, $city, $zip, $country");
debug_log("Order total: $order_total");
debug_log("Order items JSON: $order_items_json");

// Decode order items
$order_items = [];
if (!empty($order_items_json)) {
    $order_items = json_decode($order_items_json, true) ?: [];
    debug_log("Successfully decoded order items: " . count($order_items) . " items");
} else {
    debug_log("No order items in the request. Checking session storage...");
    // Try to get cart from session storage
    $session_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : '';
    if (!empty($session_cart)) {
        $order_items = json_decode($session_cart, true) ?: [];
        debug_log("Retrieved cart from session: " . count($order_items) . " items");
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

// Variable to track if transaction has started
$transaction_started = false;

try {
    // Check if orders table has the required columns
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'order_reference'");
    if ($result->num_rows == 0) {
        throw new Exception("Database schema issue: 'order_reference' column is missing. Please run db_update.php to fix this.");
    }
    
    // Check if order_items table has the required 'name' column
    $result = $conn->query("SHOW COLUMNS FROM order_items LIKE 'name'");
    if ($result->num_rows == 0) {
        throw new Exception("Database schema issue: 'name' column is missing in order_items table. Please run update_order_items.php to fix this.");
    }
    
    // Start transaction
    $conn->begin_transaction();
    $transaction_started = true;
    debug_log("Transaction started successfully");
    
    // Get user ID if logged in
    $user_id = null;
    if (isset($_SESSION['user']['user_id'])) {
        $user_id = $_SESSION['user']['user_id'];
        debug_log("Order associated with user ID: " . $user_id);
    }
    
    // Create a unique order reference
    $order_reference = 'ORD-' . strtoupper(substr(uniqid() . bin2hex(random_bytes(4)), 0, 12));
    debug_log("Generated order reference: " . $order_reference);
    
    // Status for new orders
    $status = 'pending';

    // Insert order into database using Order class
    $order = new Order($conn);
    $order->order_reference = $order_reference;
    $order->user_id = $user_id;
    $order->first_name = $first_name;
    $order->last_name = $last_name;
    $order->email = $email;
    $order->address = $address;
    $order->city = $city;
    $order->zip = $zip;
    $order->country = $country;
    $order->phone = $phone;
    $order->total_amount = $order_total;
    $order->status = $status;
    
    if (!$order->create()) {
        throw new Exception("Failed to create order: " . $order->getLastError());
    }
    
    $order_id = $order->id;
    debug_log("Order created with ID: $order_id and reference: $order_reference");
    
    // Add items to order
    foreach ($order_items as $item) {
        if (!$order->addItem($item['product_id'], $item['name'], $item['quantity'], $item['price'])) {
            throw new Exception("Failed to add order item: " . $order->getLastError());
        }
    }
    
    // Recalculate total
    $order->recalculateTotal();
    
    // Commit transaction
    $conn->commit();
    $transaction_started = false;
    debug_log("Order transaction committed successfully");
    
    // Store order reference in session for confirmation page
    $_SESSION['last_order'] = [
        'id' => $order_id,
        'reference' => $order_reference,
        'total' => $order_total
    ];
    
    // Clear cart data
    echo "<script>
        if (typeof localStorage !== 'undefined') localStorage.removeItem('cart');
        if (typeof sessionStorage !== 'undefined') sessionStorage.removeItem('cart');
    </script>";
    unset($_SESSION['cart']);
    
    // Redirect to order confirmation page
    header("Location: order_confirmation.php?order_id=$order_id");
    exit;
    
} catch (Exception $e) {
    // Rollback transaction if it was started
    if ($transaction_started) {
        try {
            $conn->rollback();
            debug_log("Transaction rolled back due to error");
        } catch (Exception $rollback_error) {
            debug_log("Failed to rollback transaction: " . $rollback_error->getMessage());
        }
    }
    
    // Log error
    error_log("Order processing error: " . $e->getMessage());
    debug_log("ERROR: " . $e->getMessage());
    
    // Set error message
    $_SESSION['error'] = 'There was a problem processing your order: ' . $e->getMessage();
    
    // Redirect back to payment page
    header('Location: payment.php?error=1');
    exit;
}
?>

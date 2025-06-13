<?php
require_once 'database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Update - Order Items Table</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .success { color: #28a745; background-color: #d4edda; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .error { color: #721c24; background-color: #f8d7da; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .info { color: #0c5460; background-color: #d1ecf1; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .btn { display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; 
               text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Order Items Table Update</h1>";

try {
    $db = new Database();
    $result = $db->updateOrderItemsTable();

    foreach ($result['messages'] as $msg) {
        echo "<div class='{$msg['type']}'>{$msg['text']}</div>";
    }

    echo "<a href='payment.php' class='btn'>Return to Checkout</a>";

} catch (Exception $e) {
    echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<a href='payment.php' class='btn'>Return to Checkout</a>";
}

echo "</body></html>";
?>
          
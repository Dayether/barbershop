<?php
// Database update script to fix orders table structure

// Include database connection
require_once 'includes/db_connection.php';
require_once 'database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        .success {
            color: green;
            background-color: #e8f5e9;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #0c5460;
            background-color: #d1ecf1;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Orders Table Update</h1>";

try {
    $db = new Database();
    $result = $db->updateOrdersTable();

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
         

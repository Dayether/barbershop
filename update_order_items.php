<?php
// Database update script to fix order_items table structure
require_once 'includes/db_connection.php';

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
    // Check if order_items table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'order_items'");
    
    if ($table_check->num_rows == 0) {
        echo "<div class='info'>The order_items table doesn't exist. Creating it now...</div>";
        
        // Create the order_items table
        $sql = "CREATE TABLE `order_items` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `price` decimal(10,2) NOT NULL,
            `quantity` int(11) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`),
            CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            echo "<div class='success'>order_items table created successfully with all required columns.</div>";
        } else {
            throw new Exception("Error creating order_items table: " . $conn->error);
        }
    } else {
        echo "<div class='info'>order_items table exists. Checking for missing 'name' column...</div>";
        
        // Check if the name column exists
        $name_column_check = $conn->query("SHOW COLUMNS FROM order_items LIKE 'name'");
        
        if ($name_column_check->num_rows == 0) {
            echo "<div class='info'>Adding missing 'name' column to order_items table...</div>";
            
            // Add the missing name column
            $sql = "ALTER TABLE order_items ADD COLUMN name VARCHAR(255) NOT NULL AFTER product_id";
            
            if ($conn->query($sql)) {
                echo "<div class='success'>Successfully added 'name' column to order_items table.</div>";
            } else {
                throw new Exception("Error adding 'name' column: " . $conn->error);
            }
        } else {
            echo "<div class='success'>The 'name' column already exists in the order_items table.</div>";
        }
    }
    
    echo "<div class='success'>Database update completed successfully!</div>";
    echo "<a href='payment.php' class='btn'>Return to Checkout</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
    echo "<a href='payment.php' class='btn'>Return to Checkout</a>";
}

echo "</body></html>";
?>

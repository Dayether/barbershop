<?php
// Database update script to fix orders table structure

// Include database connection
require_once 'includes/db_connection.php';

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
    // Check if the orders table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'orders'")->num_rows > 0;
    
    if (!$tableExists) {
        // Create the orders table from scratch
        echo "<div class='info'>Orders table does not exist. Creating it now...</div>";
        
        $sql = "CREATE TABLE orders (
            id INT(11) NOT NULL AUTO_INCREMENT,
            order_reference VARCHAR(50) NOT NULL,
            user_id INT(11) DEFAULT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL,
            address VARCHAR(255) NOT NULL,
            city VARCHAR(100) NOT NULL,
            zip VARCHAR(20) NOT NULL,
            country VARCHAR(50) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            CONSTRAINT orders_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            echo "<div class='success'>Orders table created successfully with all required columns.</div>";
        } else {
            throw new Exception("Error creating orders table: " . $conn->error);
        }
    } else {
        // Table exists, check for missing columns
        echo "<div class='info'>Orders table exists. Checking for missing columns...</div>";
        
        // Columns that should be in the orders table
        $requiredColumns = [
            'id' => 'INT(11) NOT NULL AUTO_INCREMENT',
            'order_reference' => 'VARCHAR(50) NOT NULL',
            'user_id' => 'INT(11) DEFAULT NULL',
            'first_name' => 'VARCHAR(50) NOT NULL',
            'last_name' => 'VARCHAR(50) NOT NULL',
            'email' => 'VARCHAR(100) NOT NULL',
            'address' => 'VARCHAR(255) NOT NULL',
            'city' => 'VARCHAR(100) NOT NULL',
            'zip' => 'VARCHAR(20) NOT NULL',
            'country' => 'VARCHAR(50) NOT NULL',
            'phone' => 'VARCHAR(20) NOT NULL',
            'total_amount' => 'DECIMAL(10,2) NOT NULL',
            'status' => "ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending'",
            'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
        ];
        
        // Get existing columns
        $result = $conn->query("SHOW COLUMNS FROM orders");
        $existingColumns = [];
        
        while ($row = $result->fetch_assoc()) {
            $existingColumns[$row['Field']] = true;
        }
        
        $columnsAdded = false;
        
        // Add missing columns
        foreach ($requiredColumns as $column => $definition) {
            if (!isset($existingColumns[$column])) {
                echo "<div class='info'>Adding missing column: $column</div>";
                
                $sql = "ALTER TABLE orders ADD COLUMN $column $definition";
                
                if ($conn->query($sql)) {
                    echo "<div class='success'>Column '$column' added successfully.</div>";
                    $columnsAdded = true;
                } else {
                    throw new Exception("Error adding column '$column': " . $conn->error);
                }
            }
        }
        
        if (!$columnsAdded) {
            echo "<div class='success'>All required columns already exist in the orders table.</div>";
        }
    }
    
    // Check if order_items table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'order_items'");
    if ($table_check->num_rows == 0) {
        debug_log("Order_items table doesn't exist - creating it now");
        
        // Create order_items table if it doesn't exist
        $create_order_items_table = "CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )";
        
        if (!$conn->query($create_order_items_table)) {
            throw new Exception("Failed to create order_items table: " . $conn->error);
        }
        
        debug_log("Order_items table created successfully");
    } else {
        // Table exists, verify that required columns are present
        $columns_result = $conn->query("SHOW COLUMNS FROM order_items");
        $columns = [];
        while ($column = $columns_result->fetch_assoc()) {
            $columns[$column['Field']] = true;
        }
        
        // Verify product_id column exists
        if (!isset($columns['product_id'])) {
            $conn->query("ALTER TABLE order_items ADD COLUMN product_id INT NOT NULL AFTER order_id");
            debug_log("Added missing product_id column to order_items table");
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

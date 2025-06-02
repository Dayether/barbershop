<?php
/**
 * Database Update Script
 * Run this script to add the account_type column to the users table
 */

require_once 'db_connection.php';

// Check if the column already exists
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'account_type'");
if ($checkColumn->num_rows == 0) {
    // Add the column if it doesn't exist
    $sql = "ALTER TABLE users ADD COLUMN account_type TINYINT(1) NOT NULL DEFAULT 0 AFTER email";
    
    if ($conn->query($sql) === TRUE) {
        echo "Column account_type added successfully to users table.<br>";
    } else {
        echo "Error adding column account_type: " . $conn->error . "<br>";
    }
} else {
    echo "Column account_type already exists in the users table.<br>";
}

// Set up initial admin account for testing (if needed)
// This is optional - you might want to create admin accounts through the database directly
$checkAdmin = $conn->prepare("SELECT id FROM users WHERE email = 'admin@example.com'");
$checkAdmin->execute();
$adminResult = $checkAdmin->get_result();

if ($adminResult->num_rows == 0) {
    // Create admin account if it doesn't exist
    $adminName = "Admin User";
    $adminEmail = "admin@example.com";
    $adminPassword = password_hash("admin123", PASSWORD_DEFAULT); // Change this in production!
    $adminType = 1; // Admin account
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, account_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $adminName, $adminEmail, $adminPassword, $adminType);
    
    if ($stmt->execute()) {
        echo "Admin account created successfully.<br>";
    } else {
        echo "Error creating admin account: " . $stmt->error . "<br>";
    }
    
    $stmt->close();
} else {
    // Update existing admin account to have admin privileges
    $adminId = $adminResult->fetch_assoc()['id'];
    $adminType = 1;
    
    $updateStmt = $conn->prepare("UPDATE users SET account_type = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $adminType, $adminId);
    
    if ($updateStmt->execute()) {
        echo "Existing admin account updated with admin privileges.<br>";
    } else {
        echo "Error updating admin account: " . $updateStmt->error . "<br>";
    }
    
    $updateStmt->close();
}

$conn->close();

echo "<p>Database update completed. You can now <a href='../login.php'>login</a> to your account.</p>";
?>

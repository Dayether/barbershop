<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = "localhost";
$db_user = "root";    // Default XAMPP username
$db_pass = "";        // Default XAMPP password (empty)
$db_name = "barbershop"; // Your database name

// Error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable exception throwing

try {
    // Connect to database
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        error_log("Failed to connect to database: " . $conn->connect_error);
        throw new Exception("Database connection failed");
    }
    
    // Set charset for proper UTF-8 handling
    $conn->set_charset("utf8mb4");
    
    error_log("Database connection successful");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    // Don't output error to screen, but make $conn null
    $conn = null;
}
?>

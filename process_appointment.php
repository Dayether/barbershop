<?php
session_start();

// Include database connection
require_once 'includes/db_connection.php';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $service = $_POST['service'] ?? '';
    $date = $_POST['appointment-date'] ?? '';
    $time = $_POST['appointment-time'] ?? '';
    $barber = $_POST['barber'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Generate a booking reference
    $booking_reference = 'TIP' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    
    // User ID (if logged in)
    $user_id = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
    
    // Check if date is valid
    if (!strtotime($date)) {
        $response = [
            'success' => false,
            'message' => 'Invalid appointment date'
        ];
        echo json_encode($response);
        exit;
    }
    
    // Format date properly for MySQL
    $formatted_date = date('Y-m-d', strtotime($date));
    
    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO appointments (booking_reference, user_id, service, appointment_date, appointment_time, barber, client_name, client_email, client_phone, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    
    $stmt->bind_param("sissssssss", $booking_reference, $user_id, $service, $formatted_date, $time, $barber, $name, $email, $phone, $notes);
    
    // Execute query
    if ($stmt->execute()) {
        // Return success response for AJAX request
        $response = [
            'success' => true,
            'booking_reference' => $booking_reference,
            'message' => 'Your appointment has been successfully booked!'
        ];
        echo json_encode($response);
    } else {
        // Return error response
        $response = [
            'success' => false,
            'message' => 'Error: ' . $stmt->error
        ];
        echo json_encode($response);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}
?>

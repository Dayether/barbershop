<?php
session_start();

// Include database connection
require_once 'includes/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    $response = [
        'status' => 'error',
        'message' => 'You must be logged in to book an appointment'
    ];
    echo json_encode($response);
    exit;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $service = $_POST['service'] ?? '';
    $appointmentDate = $_POST['appointment-date'] ?? '';
    $appointmentTime = $_POST['appointment-time'] ?? '';
    $barber = $_POST['barber'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Validate required fields
    if (empty($service) || empty($appointmentDate) || empty($appointmentTime) || empty($name) || empty($email) || empty($phone)) {
        $response = [
            'status' => 'error',
            'message' => 'All required fields must be filled out.'
        ];
        echo json_encode($response);
        exit;
    }
    
    // Generate a unique booking reference
    $bookingReference = 'TIP' . strtoupper(bin2hex(random_bytes(4)));
    
    // Get user ID from session
    $userId = $_SESSION['user']['id'];
    
    try {
        // Insert appointment into the database
        $stmt = $conn->prepare("INSERT INTO appointments 
            (booking_reference, user_id, service, appointment_date, appointment_time, barber, client_name, client_email, client_phone, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("sissssssss", 
            $bookingReference,
            $userId,
            $service,
            $appointmentDate,
            $appointmentTime,
            $barber,
            $name,
            $email,
            $phone,
            $notes
        );
        
        if ($stmt->execute()) {
            // Insert into appointment history for tracking
            $appointmentId = $conn->insert_id;
            $conn->query("INSERT INTO appointment_history (appointment_id, action, notes, user_id) 
                VALUES ($appointmentId, 'create', 'Appointment created by customer', $userId)");
            
            // Send email notification (in a real app)
            // sendEmailConfirmation($email, $bookingReference, $service, $appointmentDate, $appointmentTime);
            
            $response = [
                'status' => 'success',
                'message' => 'Your appointment has been booked successfully!',
                'booking_reference' => $bookingReference,
                'redirect' => 'appointment_confirmation.php?ref=' . $bookingReference
            ];
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $response = [
            'status' => 'error',
            'message' => 'There was a problem booking your appointment: ' . $e->getMessage()
        ];
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
    
} else {
    // If accessed directly without POST data
    header('Location: appointment.php');
    exit;
}
?>

<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['account_type']) || $_SESSION['user']['account_type'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

require_once '../../includes/db_connection.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get appointment ID and new status from POST data
$appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$new_status = isset($_POST['status']) ? $_POST['status'] : '';

// Validate inputs
if (!$appointment_id || !in_array($new_status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid appointment ID or status']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Update appointment status
    $update_sql = "UPDATE appointments SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $appointment_id);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception("Failed to update appointment status");
    }
    
    // Get admin user ID for the history record
    $admin_id = $_SESSION['user']['id'];
    
    // Map status to action for history
    $action_map = [
        'confirmed' => 'create',  // Using 'create' as the initial confirmation
        'completed' => 'complete',
        'cancelled' => 'cancel',
        'pending' => 'reschedule', // If somehow setting back to pending
    ];
    
    $action = $action_map[$new_status];
    $notes = "Appointment " . $new_status . " by administrator";
    
    // Record in appointment history
    $history_sql = "INSERT INTO appointment_history (appointment_id, action, notes, staff_id) 
                    VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($history_sql);
    $stmt->bind_param("issi", $appointment_id, $action, $notes, $admin_id);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception("Failed to record appointment history");
    }
    
    // Get the updated appointment details
    $fetch_sql = "SELECT 
                    a.*,
                    u.profile_pic
                  FROM 
                    appointments a
                  LEFT JOIN 
                    users u ON a.user_id = u.id
                  WHERE 
                    a.id = ?";
    $stmt = $conn->prepare($fetch_sql);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    
    // Format date for displaying
    $date = new DateTime($appointment['appointment_date']);
    $today = new DateTime();
    $tomorrow = new DateTime('tomorrow');
    
    if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
        $formatted_date = 'Today';
    } else if ($date->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
        $formatted_date = 'Tomorrow';
    } else {
        $formatted_date = $date->format('M j, Y');
    }
    
    // Use default profile image if none exists
    $profile_pic = $appointment['profile_pic'] ?: '../images/default-profile.png';
    
    // Format appointment for response
    $formatted_appointment = [
        'id' => (int)$appointment['id'],
        'booking_reference' => $appointment['booking_reference'],
        'client_name' => $appointment['client_name'],
        'client_email' => $appointment['client_email'],
        'client_phone' => $appointment['client_phone'],
        'service' => $appointment['service'],
        'appointment_date' => $appointment['appointment_date'],
        'appointment_time' => $appointment['appointment_time'],
        'barber' => $appointment['barber'],
        'status' => $appointment['status'],
        'notes' => $appointment['notes'],
        'profile_pic' => $profile_pic,
        'formatted_date' => $formatted_date
    ];
    
    // Commit transaction
    $conn->commit();
    
    // Return success response with updated appointment
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Appointment status updated successfully',
        'appointment' => $formatted_appointment
    ]);
    
} catch (Exception $e) {
    // Roll back transaction on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

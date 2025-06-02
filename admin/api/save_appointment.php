<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

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

// Get form data
try {
    $action = isset($_POST['action']) ? trim($_POST['action']) : 'add';
    $appointment_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $client_name = isset($_POST['client_name']) ? trim($_POST['client_name']) : '';
    $client_email = isset($_POST['client_email']) ? trim($_POST['client_email']) : '';
    $client_phone = isset($_POST['client_phone']) ? trim($_POST['client_phone']) : '';
    $service = isset($_POST['service']) ? trim($_POST['service']) : '';
    $appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
    $appointment_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';
    $barber = isset($_POST['barber']) ? trim($_POST['barber']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'pending';

    // Basic validation
    if (empty($client_name) || empty($client_email) || empty($service) || 
        empty($appointment_date) || empty($appointment_time) || empty($barber)) {
        throw new Exception('All required fields must be filled');
    }

    // Start transaction
    $conn->begin_transaction();

    // Find user_id if available
    $user_id = null;
    $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $user_stmt->bind_param("s", $client_email);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if ($user_result->num_rows > 0) {
        $user_id = $user_result->fetch_assoc()['id'];
    }

    if ($action === 'add') {
        // Generate booking reference
        $booking_reference = 'TIP' . strtoupper(bin2hex(random_bytes(4)));

        // Insert new appointment
        $sql = "INSERT INTO appointments (booking_reference, user_id, service, appointment_date, 
                appointment_time, barber, client_name, client_email, client_phone, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisssssssss", $booking_reference, $user_id, $service, $appointment_date, 
                         $appointment_time, $barber, $client_name, $client_email, $client_phone, $notes, $status);
        
        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }
        
        $appointment_id = $stmt->insert_id;
        
        // Record in appointment history
        $admin_id = $_SESSION['user']['id'];
        $action_type = 'create';
        $history_notes = 'Appointment created by admin';
        
        $history_sql = "INSERT INTO appointment_history (appointment_id, action, notes, staff_id) 
                       VALUES (?, ?, ?, ?)";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->bind_param("issi", $appointment_id, $action_type, $history_notes, $admin_id);
        
        if (!$history_stmt->execute()) {
            throw new Exception("Failed to record appointment history");
        }
    } else {
        // Update existing appointment
        $sql = "UPDATE appointments 
                SET service = ?, appointment_date = ?, appointment_time = ?, 
                    barber = ?, client_name = ?, client_email = ?, 
                    client_phone = ?, notes = ?, status = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", $service, $appointment_date, $appointment_time, 
                         $barber, $client_name, $client_email, $client_phone, $notes, $status, $appointment_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }
        
        if ($stmt->affected_rows == 0) {
            throw new Exception("No appointment found with ID: $appointment_id");
        }
        
        // Record in appointment history
        $admin_id = $_SESSION['user']['id'];
        $action_type = 'reschedule';
        $history_notes = 'Appointment updated by admin';
        
        $history_sql = "INSERT INTO appointment_history (appointment_id, action, notes, staff_id) 
                       VALUES (?, ?, ?, ?)";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->bind_param("issi", $appointment_id, $action_type, $history_notes, $admin_id);
        
        if (!$history_stmt->execute()) {
            throw new Exception("Failed to record appointment history");
        }
    }

    // Get the updated appointment data
    $get_appointment = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
    $get_appointment->bind_param("i", $appointment_id);
    $get_appointment->execute();
    $result = $get_appointment->get_result();
    $appointment = $result->fetch_assoc();

    // Format date for display
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

    // Commit transaction
    $conn->commit();
    
    // Success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => ($action === 'add') ? 'Appointment created successfully' : 'Appointment updated successfully',
        'appointment' => [
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
            'formatted_date' => $formatted_date,
            'profile_pic' => '../images/default-profile.png'
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Log error for debugging
    error_log("Appointment save error: " . $e->getMessage());
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['account_type']) || $_SESSION['user']['account_type'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once '../../includes/db_connection.php';

// Get appointments
$query = "SELECT 
            a.*,
            u.profile_pic
          FROM 
            appointments a
          LEFT JOIN 
            users u ON a.user_id = u.id
          ORDER BY 
            a.appointment_date ASC, 
            a.appointment_time ASC";

$result = $conn->query($query);

if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$appointments = [];

while ($row = $result->fetch_assoc()) {
    // Use default profile image if none exists
    $profile_pic = $row['profile_pic'] ?: '../images/default-profile.png';
    
    $appointments[] = [
        'id' => (int)$row['id'],
        'booking_reference' => $row['booking_reference'],
        'client_name' => $row['client_name'],
        'client_email' => $row['client_email'],
        'client_phone' => $row['client_phone'],
        'service' => $row['service'],
        'appointment_date' => $row['appointment_date'],
        'appointment_time' => $row['appointment_time'],
        'barber' => $row['barber'],
        'status' => $row['status'],
        'notes' => $row['notes'],
        'profile_pic' => $profile_pic
    ];
}

header('Content-Type: application/json');
echo json_encode($appointments);

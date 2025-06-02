<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['account_type']) || $_SESSION['user']['account_type'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once '../includes/db_connection.php';

// Get recent appointments (default limit is 5)
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

$query = "SELECT a.*, u.profile_pic 
          FROM appointments a 
          LEFT JOIN users u ON a.user_id = u.id 
          ORDER BY a.appointment_date ASC, a.appointment_time ASC 
          LIMIT ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $limit);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    // Format appointment date and time nicely
    $date = new DateTime($row['appointment_date']);
    $today = new DateTime();
    $tomorrow = new DateTime('tomorrow');
    
    if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
        $formatted_date = 'Today';
    } else if ($date->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
        $formatted_date = 'Tomorrow';
    } else {
        $formatted_date = $date->format('M j, Y'); // e.g., Jan 1, 2025
    }
    
    // Use default profile image if none exists
    $profile_pic = $row['profile_pic'] ?: '../images/default-profile.png';
    
    $appointments[] = [
        'id' => $row['id'],
        'booking_reference' => $row['booking_reference'],
        'client_name' => $row['client_name'],
        'service' => $row['service'],
        'date' => $formatted_date,
        'time' => $row['appointment_time'],
        'barber' => $row['barber'],
        'status' => $row['status'],
        'profile_pic' => $profile_pic,
        'client_email' => $row['client_email'],
        'client_phone' => $row['client_phone'],
        'notes' => $row['notes']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($appointments);
?>

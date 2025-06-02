<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['account_type']) || $_SESSION['user']['account_type'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once '../../includes/db_connection.php';

// Get today's date
$today = date('Y-m-d');

// Query to get appointment statistics
$stats_query = "SELECT 
                  SUM(CASE WHEN appointment_date = ? THEN 1 ELSE 0 END) as today_count,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                  SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
                  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                  SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
                FROM appointments";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

// Return the statistics
$response = [
    'today_count' => (int)$stats['today_count'],
    'pending_count' => (int)$stats['pending_count'],
    'confirmed_count' => (int)$stats['confirmed_count'],
    'completed_count' => (int)$stats['completed_count'],
    'cancelled_count' => (int)$stats['cancelled_count']
];

header('Content-Type: application/json');
echo json_encode($response);

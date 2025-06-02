<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['account_type']) || $_SESSION['user']['account_type'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once '../../includes/db_connection.php';

// Get services from database
$query = "SELECT * FROM services ORDER BY name ASC";
$result = $conn->query($query);

if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = [
        'id' => intval($row['id']),
        'name' => $row['name'],
        'description' => $row['description'],
        'duration' => intval($row['duration']),
        'price' => floatval($row['price']),
        'image' => $row['image'] ?: 'uploads/services/default.jpg',
        'active' => intval($row['active'])
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($services);

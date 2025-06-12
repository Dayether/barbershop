<?php
require_once 'includes/db_connection.php';

$date = $_GET['date'] ?? '';
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$barber_id = isset($_GET['barber_id']) && $_GET['barber_id'] !== '' ? intval($_GET['barber_id']) : null;

$unavailable = [];

if ($date) {
    if ($barber_id === null) {
        // If no barber selected, get all slots booked for this service
        $sql = "SELECT appointment_time FROM appointments WHERE appointment_date = ? AND service_id = ? AND status IN ('pending', 'confirmed')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $date, $service_id);
    } else {
        // If barber selected, get all slots booked for this barber
        $sql = "SELECT appointment_time FROM appointments WHERE appointment_date = ? AND barber_id = ? AND status IN ('pending', 'confirmed')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $date, $barber_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $unavailable[] = $row['appointment_time'];
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode(['unavailable' => $unavailable]);

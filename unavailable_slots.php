<?php
require_once 'database.php';

$date = $_GET['date'] ?? '';
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$barber_id = isset($_GET['barber_id']) && $_GET['barber_id'] !== '' ? intval($_GET['barber_id']) : null;

$db = new Database();
$unavailable = $db->getUnavailableSlots($date, $service_id, $barber_id);

header('Content-Type: application/json');
echo json_encode(['unavailable' => $unavailable]);
        

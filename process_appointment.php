<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Only accept POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: appointment.php');
    exit;
}

// Get form data
$service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
$appointment_date = isset($_POST['appointment-date']) ? trim($_POST['appointment-date']) : '';
$appointment_time = isset($_POST['appointment-time']) ? trim($_POST['appointment-time']) : '';
$barber_id = (isset($_POST['barber_id']) && $_POST['barber_id'] !== '') ? intval($_POST['barber_id']) : null;
$client_name = isset($_POST['name']) ? trim($_POST['name']) : '';
$client_email = isset($_POST['email']) ? trim($_POST['email']) : '';
$client_phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$user_id = $_SESSION['user']['user_id'];

// Validate required fields
if (
    !$service_id || !$appointment_date || !$appointment_time ||
    !$client_name || !$client_email || !$client_phone
) {
    $_SESSION['appointment_error'] = "All required fields must be filled out.";
    header('Location: appointment.php');
    exit;
}

try {
    $db = new Database();
    $result = $db->createAppointment([
        'service_id' => $service_id,
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time,
        'barber_id' => $barber_id,
        'client_name' => $client_name,
        'client_email' => $client_email,
        'client_phone' => $client_phone,
        'notes' => $notes,
        'user_id' => $user_id
    ]);

    if ($result['success']) {
        // Redirect to confirmation page with booking reference
        header('Location: appointment_confirmation.php?ref=' . urlencode($result['booking_reference']));
        exit;
    } else {
        $_SESSION['appointment_error'] = $result['error_message'] ?? "There was a problem booking your appointment.";
        header('Location: appointment.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['appointment_error'] = "There was a problem booking your appointment: " . $e->getMessage();
    header('Location: appointment.php');
    exit;
}
?>

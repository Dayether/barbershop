<?php
session_start();

// Include database connection
require_once 'includes/db_connection.php';

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

// Check for double booking
if ($barber_id === null) {
    // Check if any barber is already booked at this date/time for this service
    $check_sql = "SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND service_id = ? AND status IN ('pending', 'confirmed')";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ssi", $appointment_date, $appointment_time, $service_id);
} else {
    // Check if this barber is already booked at this date/time
    $check_sql = "SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND barber_id = ? AND status IN ('pending', 'confirmed')";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ssi", $appointment_date, $appointment_time, $barber_id);
}
$check_stmt->execute();
$check_stmt->bind_result($count);
$check_stmt->fetch();
$check_stmt->close();

if ($count > 0) {
    $_SESSION['appointment_error'] = "The selected date and time is already booked. Please choose another slot.";
    header('Location: appointment.php');
    exit;
}

// Generate unique booking reference
$booking_reference = 'TIP' . strtoupper(bin2hex(random_bytes(4)));

// Prepare SQL with correct types
if ($barber_id === null) {
    $sql = "INSERT INTO appointments 
        (booking_reference, user_id, service_id, appointment_date, appointment_time, barber_id, client_name, client_email, client_phone, notes, status) 
        VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $_SESSION['appointment_error'] = "There was a problem booking your appointment: " . $conn->error;
        header('Location: appointment.php');
        exit;
    }
    $stmt->bind_param(
        "siissssss",
        $booking_reference,
        $user_id,
        $service_id,
        $appointment_date,
        $appointment_time,
        $client_name,
        $client_email,
        $client_phone,
        $notes
    );
} else {
    $sql = "INSERT INTO appointments 
        (booking_reference, user_id, service_id, appointment_date, appointment_time, barber_id, client_name, client_email, client_phone, notes, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $_SESSION['appointment_error'] = "There was a problem booking your appointment: " . $conn->error;
        header('Location: appointment.php');
        exit;
    }
    $stmt->bind_param(
        "siississss",
        $booking_reference,
        $user_id,
        $service_id,
        $appointment_date,
        $appointment_time,
        $barber_id,
        $client_name,
        $client_email,
        $client_phone,
        $notes
    );
}

if ($stmt->execute()) {
    $appointment_id = $conn->insert_id;
    // Add to appointment_history
    $history_stmt = $conn->prepare("INSERT INTO appointment_history (appointment_id, action, notes, user_id) VALUES (?, 'create', 'Appointment created by customer', ?)");
    $history_stmt->bind_param("ii", $appointment_id, $user_id);
    $history_stmt->execute();
    $history_stmt->close();

    // Redirect with success flag
    header("Location: appointment_confirmation.php?ref=" . urlencode($booking_reference) . "&success=1");
    exit;
} else {
    // Log error for debugging
    error_log("Appointment insert error: " . $stmt->error);
    $_SESSION['appointment_error'] = "There was a problem booking your appointment: " . $stmt->error;
    header('Location: appointment.php');
    exit;
}
?>

<?php
// Include database file
require_once '../database.php';

// Get email from request
$email = filter_var($_GET['email'] ?? '', FILTER_SANITIZE_EMAIL);

// Validate request
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['valid' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

// Initialize database connection
$db = new Database();

// Check if email exists
$emailExists = $db->emailExists($email);

// Return JSON response (only once)
echo json_encode([
    'valid' => !$emailExists,
    'message' => $emailExists ? 'Email address already registered' : 'Email address is available'
]);

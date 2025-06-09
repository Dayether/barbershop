<?php
// Include autoloader
require_once '../includes/autoload.php';

// Get email from request
$email = filter_var($_GET['email'] ?? '', FILTER_SANITIZE_EMAIL);

// Validate request
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['valid' => false, 'message' => 'Invalid email format']);
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->connect();

// Initialize user object
$user = new User($db);

// Check if email exists
$emailExists = $user->emailExists($email);

// Return JSON response
echo json_encode([
    'valid' => !$emailExists,
    'message' => $emailExists ? 'Email address already registered' : 'Email address is available'
]);

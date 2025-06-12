<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connection.php';

$user_id = $_SESSION['user']['user_id'];
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success = false;
$error_message = '';

// Verify that the appointment exists and belongs to this user
if ($appointment_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $appointment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $appointment = $result->fetch_assoc();
        
        // Check if the appointment can be cancelled (not in the past and not already cancelled)
        $current_date = date('Y-m-d H:i:s');
        $appointment_datetime = $appointment['appointment_date'] . ' ' . $appointment['appointment_time'];
        
        if ($appointment_datetime < $current_date) {
            $error_message = "Cannot cancel past appointments.";
        } elseif ($appointment['status'] === 'cancelled') {
            $error_message = "This appointment is already cancelled.";
        } elseif ($appointment['status'] === 'completed') {
            $error_message = "Cannot cancel completed appointments.";
        } else {
            // Update appointment status to cancelled
            $update = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?");
            $update->bind_param("i", $appointment_id);
            
            if ($update->execute()) {
                $success = true;
            } else {
                $error_message = "Error cancelling appointment: " . $conn->error;
            }
            $update->close();
        }
    } else {
        $error_message = "Invalid appointment or you don't have permission to cancel it.";
    }
    $stmt->close();
} else {
    $error_message = "Invalid appointment ID.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Appointment - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .result-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .icon-container {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .success-icon {
            color: #28a745;
        }
        
        .error-icon {
            color: #dc3545;
        }
        
        h2 {
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        p {
            margin-bottom: 30px;
            color: #555;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>Cancel Appointment</h1>
            <p>Managing your scheduled appointments</p>
        </div>
    </section>
    
    <section class="content-section">
        <div class="container">
            <div class="result-container">
                <?php if ($success): ?>
                    <div class="icon-container">
                        <i class="fas fa-check-circle success-icon"></i>
                    </div>
                    <h2>Appointment Cancelled</h2>
                    <p>Your appointment has been successfully cancelled.</p>
                <?php else: ?>
                    <div class="icon-container">
                        <i class="fas fa-exclamation-triangle error-icon"></i>
                    </div>
                    <h2>Unable to Cancel Appointment</h2>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <a href="profile.php" class="btn btn-outline">Back to Profile</a>
                    <a href="appointment.php" class="btn btn-primary">Book New Appointment</a>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>

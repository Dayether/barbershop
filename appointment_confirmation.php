<?php
session_start();
require_once 'includes/db_connection.php';

// Check if booking reference is provided
if (!isset($_GET['ref'])) {
    header('Location: index.php');
    exit;
}

$bookingReference = $_GET['ref'];
$appointmentDetails = null;

// Get appointment details from the database
try {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE booking_reference = ?");
    $stmt->bind_param("s", $bookingReference);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $appointmentDetails = $result->fetch_assoc();
    } else {
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    // Handle database error
    $_SESSION['error'] = "Could not retrieve appointment details.";
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmation - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/appointment.css">
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .confirmation-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .confirmation-icon {
            font-size: 70px;
            color: #2a9d8f;
            margin-bottom: 20px;
        }
        
        .confirmation-title {
            font-size: 32px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .booking-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        
        .booking-details p {
            margin: 10px 0;
            color: #555;
        }
        
        .booking-ref {
            font-weight: bold;
            color: #212529;
            font-size: 18px;
        }
        
        .action-buttons {
            margin-top: 30px;
        }
        
        .action-buttons .btn {
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Confirmation Section -->
    <section>
        <div class="container">
            <div class="confirmation-container">
                <i class="fas fa-calendar-check confirmation-icon"></i>
                <h1 class="confirmation-title">Appointment Confirmed!</h1>
                <p>Your appointment has been successfully scheduled. A confirmation email has been sent to your email address.</p>
                
                <div class="booking-details">
                    <p class="booking-ref">Booking Reference: <?= htmlspecialchars($bookingReference) ?></p>
                    <p><strong>Service:</strong> <?= htmlspecialchars($appointmentDetails['service']) ?></p>
                    <p><strong>Date:</strong> <?= htmlspecialchars($appointmentDetails['appointment_date']) ?></p>
                    <p><strong>Time:</strong> <?= htmlspecialchars($appointmentDetails['appointment_time']) ?></p>
                    <?php if (!empty($appointmentDetails['barber'])): ?>
                    <p><strong>Barber:</strong> <?= htmlspecialchars($appointmentDetails['barber']) ?></p>
                    <?php endif; ?>
                </div>
                
                <p>Please arrive 10 minutes before your scheduled time. If you need to reschedule or cancel, please contact us at least 24 hours in advance.</p>
                
                <div class="action-buttons">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                    <a href="my_appointments.php" class="btn btn-primary">
                        <i class="fas fa-calendar-alt"></i> View My Appointments
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize iziToast
            iziToast.settings({
                timeout: 6000,
                resetOnHover: true,
                position: 'topRight',
                transitionIn: 'flipInX',
                transitionOut: 'flipOutX',
            });
            
            // Show success notification
            iziToast.success({
                title: 'Appointment Confirmed!',
                message: 'Your booking reference is <?= htmlspecialchars($bookingReference) ?>',
                icon: 'fas fa-calendar-check',
                iconColor: '#2a9d8f'
            });
            
            // Show reminder notification after a delay
            setTimeout(() => {
                iziToast.info({
                    title: 'Reminder',
                    message: 'Please arrive 10 minutes before your appointment time',
                    icon: 'fas fa-bell',
                    iconColor: '#2a9d8f'
                });
            }, 3000);
            
            // Add to calendar notification
            setTimeout(() => {
                iziToast.info({
                    title: 'Add to Calendar',
                    message: 'Don\'t forget to add this appointment to your calendar!',
                    icon: 'fas fa-calendar-plus',
                    iconColor: '#2a9d8f',
                    buttons: [
                        ['<button>Add Now</button>', function (instance, toast) {
                            // In a real implementation, this would add to calendar
                            window.open('https://calendar.google.com/calendar/r/eventedit', '_blank');
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        }, true],
                        ['<button>Later</button>', function (instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        }]
                    ]
                });
            }, 6000);
        });
    </script>
</body>
</html>

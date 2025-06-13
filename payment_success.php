<?php
session_start();
require_once 'database.php';

// Get the order reference if provided
$orderRef = isset($_GET['order_ref']) ? htmlspecialchars($_GET['order_ref']) : 'Unknown';

// Clear the cart from localStorage and sessionStorage via JavaScript
$clearCartScript = true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <style>
        .success-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .success-icon {
            font-size: 70px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .success-title {
            font-size: 32px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .order-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }
        
        .order-info p {
            margin: 10px 0;
            color: #555;
        }
        
        .order-ref {
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
        
        .btn-outline {
            background-color: transparent;
            color: #2a9d8f;
            border: 2px solid #2a9d8f;
            transition: all 0.3s;
        }
        
        .btn-outline:hover {
            background-color: #2a9d8f;
            color: #fff;
        }
        
        .redirect-message {
            margin-top: 30px;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Success Message Section -->
    <section>
        <div class="container">
            <div class="success-container">
                <i class="fas fa-check-circle success-icon"></i>
                <h1 class="success-title">Thank You for Your Order!</h1>
                <p>Your order has been received and is being processed.</p>
                
                <div class="order-info">
                    <p class="order-ref">Order Reference: <?= $orderRef ?></p>
                    <p>A confirmation email with order details will be sent to your email address.</p>
                </div>
                
                <div class="action-buttons">
                    <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="orders.php" class="btn btn-outline">View My Orders</a>
                    <?php endif; ?>
                </div>
                
                <p class="redirect-message">You will be redirected to the homepage in <span id="countdown">10</span> seconds...</p>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script>
        // Clear cart data from localStorage and sessionStorage
        localStorage.removeItem('cart');
        sessionStorage.removeItem('cart');
        
        // Show success toast
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize iziToast
            iziToast.settings({
                timeout: 6000,
                resetOnHover: true,
                position: 'topRight',
                transitionIn: 'flipInX',
                transitionOut: 'flipOutX',
            });
            
            iziToast.success({
                title: 'Order Placed!',
                message: 'Your order has been successfully placed.',
                icon: 'fas fa-check-circle'
            });
            
            // Countdown for redirect
            let seconds = 10;
            const countdownElement = document.getElementById('countdown');
            
            const countdown = setInterval(function() {
                seconds--;
                
                if (countdownElement) {
                    countdownElement.textContent = seconds;
                }
                
                if (seconds <= 0) {
                    clearInterval(countdown);
                    window.location.href = 'index.php';
                }
            }, 1000);
        });
    </script>
</body>
</html>

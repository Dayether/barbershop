<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Tipuno Barbershop</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <!-- Add messages.css for the messages page -->
    <?php if (isset($_GET['page']) && $_GET['page'] === 'messages'): ?>
    <link rel="stylesheet" href="css/messages.css">
    <?php endif; ?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- IziToast -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <!-- Admin JS -->
    <script src="js/admin.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dropdown toggle for admin user menu
        const dropdown = document.querySelector('.admin-dropdown');
        if (dropdown) {
            const menu = dropdown.querySelector('.admin-dropdown-menu');
            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('show');
            });
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                menu.classList.remove('show');
            });
        }
    });
    </script>
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <div class="admin-top-bar">
                <div class="admin-logo">
                    <h2>Tipuno <span>Admin</span></h2>
                </div>
                <div class="admin-user-info">
                    <div class="admin-notifications">
                        <i class="fas fa-scissors"></i>
                        <?php
                        // Database connection
                        $db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
                        ?>
                    </div>
                    <div class="admin-user">
                        <img src="<?php echo !empty($_SESSION['user']['profile_pic']) ? '../' . $_SESSION['user']['profile_pic'] : '../uploads/default-profile.jpg'; ?>" alt="Admin" class="admin-avatar">
                        <div class="admin-user-details">
                            <span>Welcome</span>
                            <h4><?php echo $_SESSION['user']['name']; ?></h4>
                        </div>
                        <div class="admin-dropdown">
                            <i class="fas fa-chevron-down"></i>
                            <div class="admin-dropdown-menu">
                                <a href="admin_index.php?page=profile"><i class="fas fa-user-circle"></i> Profile</a>
                                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

<?php
session_start();
if (isset($_SESSION['user'])) {
    // Redirect admin users to admin dashboard, regular users to homepage
    if (isset($_SESSION['user']['account_type']) && $_SESSION['user']['account_type'] == 1) {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

require_once 'includes/db_connection.php';

$error = '';
$success = '';
$email = ''; // Initialize email variable
$password = ''; // Also initialize password for consistency

// Check if user just registered successfully
if (isset($_SESSION['registration_success'])) {
    $success = 'Registration successful! Please log in with your credentials.';
    $email = $_SESSION['registered_email'] ?? '';
    
    // Clear the session variables
    unset($_SESSION['registration_success']);
    unset($_SESSION['registered_email']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Check user in database - now retrieving account_type as well
    $stmt = $conn->prepare("SELECT id, name, email, password, profile_pic, account_type FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'profile_pic' => $user['profile_pic'] ?: 'images/default-profile.png',
                'account_type' => $user['account_type']
            ];
            
            // Mark as new login to trigger cart reset
            $_SESSION['new_login'] = true;
            
            // Redirect admin users to admin dashboard
            if ($user['account_type'] == 1) {
                header('Location: admin/index.php');
                exit;
            }
            
            // Redirect regular users to saved page or home page
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'User not found';
    }
    
    $stmt->close();
}

// For testing purposes - can be removed in production
if (!$error && $email === 'user@example.com' && $password === 'password') {
    $_SESSION['user'] = [
        'id' => 1,
        'email' => $email,
        'name' => 'John Doe',
        'profile_pic' => 'images/default-profile.png',
        'account_type' => 0 // Regular user
    ];
    header('Location: index.php');
    exit;
}

// For testing admin access - can be removed in production
if (!$error && $email === 'admin@example.com' && $password === 'admin123') {
    $_SESSION['user'] = [
        'id' => 2,
        'email' => $email,
        'name' => 'Admin User',
        'profile_pic' => 'images/default-profile.png',
        'account_type' => 1 // Admin user
    ];
    header('Location: admin/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <section class="auth-section">
        <div class="auth-bg">
            <div class="circle"></div>
            <div class="circle"></div>
            <div class="circle"></div>
            <div class="line"></div>
            <div class="line"></div>
            <div class="line"></div>
        </div>
        
        <div class="auth-container">
            <div class="auth-left">
                <div class="auth-image-wrapper">
                    <img src="images/login-bg.jpg" alt="Barbershop" class="auth-image">
                    <div class="auth-image-content">
                        <h2>Welcome Back</h2>
                        <p>Log in to access your account, book appointments, and view your order history.</p>
                    </div>
                </div>
            </div>
            
            <div class="auth-right">
                <div class="auth-form-container">
                    <div class="auth-form-header">
                        <h2>Login</h2>
                        <p>Enter your credentials to access your account</p>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="post" class="auth-form">
                        <div class="form-floating">
                            <input type="email" name="email" id="email" class="form-control" placeholder=" " value="<?= htmlspecialchars($registered_email ?? '') ?>" required>
                            <label for="email" class="form-label">Email Address</label>
                        </div>
                        
                        <div class="form-floating password-field">
                            <input type="password" name="password" id="password" class="form-control" placeholder=" " required>
                            <label for="password" class="form-label">Password</label>
                            <button type="button" class="toggle-password">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="remember" name="remember" class="form-check-input">
                            <label for="remember" class="form-check-label">Remember me</label>
                        </div>
                        
                        <a href="#" class="forgot-password">Forgot password?</a>
                        
                        <button type="submit" class="btn-auth">Login</button>
                        
                        <div class="auth-divider">
                            <span>or continue with</span>
                        </div>
                        
                        <div class="social-buttons">
                            <button type="button" class="btn-social btn-google">
                                <i class="fab fa-google"></i> Google
                            </button>
                            <button type="button" class="btn-social btn-facebook">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </button>
                        </div>
                        
                        <div class="auth-footer">
                            <p>Don't have an account? <a href="register.php">Register</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle
            const togglePassword = document.querySelector('.toggle-password');
            const password = document.querySelector('#password');
            
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    
                    // Toggle eye icon
                    const eyeIcon = this.querySelector('i');
                    eyeIcon.classList.toggle('fa-eye');
                    eyeIcon.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>

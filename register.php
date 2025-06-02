<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/db_connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = 'Email already registered';
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $default_profile_pic = 'images/default-profile.png';
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, profile_pic) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashed_password, $default_profile_pic);
        
        if ($stmt->execute()) {
            // Instead of logging in automatically, set a success message and redirect to login
            $_SESSION['registration_success'] = true;
            $_SESSION['registered_email'] = $email;
            
            header('Location: login.php');
            exit;
        } else {
            $error = 'Registration failed: ' . $conn->error;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Tipuno Barbershop</title>
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
                    <img src="images/register-bg.jpg" alt="Barbershop" class="auth-image">
                    <div class="auth-image-content">
                        <h2>Join Our Community</h2>
                        <p>Create an account to enjoy exclusive benefits, easy appointment booking, and special offers.</p>
                    </div>
                </div>
            </div>
            
            <div class="auth-right">
                <div class="auth-form-container">
                    <div class="auth-form-header">
                        <h2>Create Account</h2>
                        <p>Sign up to get started with Tipuno</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="post" class="auth-form">
                        <div class="form-floating">
                            <input type="text" name="name" id="name" class="form-control" placeholder=" " required>
                            <label for="name" class="form-label">Full Name</label>
                        </div>
                        
                        <div class="form-floating">
                            <input type="email" name="email" id="email" class="form-control" placeholder=" " required>
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
                            <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                            <label for="terms" class="form-check-label">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                        </div>
                        
                        <button type="submit" class="btn-auth">Create Account</button>
                        
                        <div class="auth-divider">
                            <span>or sign up with</span>
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
                            <p>Already have an account? <a href="login.php">Login</a></p>
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

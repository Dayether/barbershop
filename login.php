<?php
session_start();
if (isset($_SESSION['user'])) {
    // Redirect admin users to admin dashboard, regular users to homepage
    if (isset($_SESSION['user']['account_type']) && $_SESSION['user']['account_type'] == 1) {
        header('Location: admin/admin_index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// Include autoloader
require_once 'includes/autoload.php';

// Initialize database connection
$database = new Database();
$db = $database->connect();

// Initialize user object
$user = new User($db);

$error = '';
$success = '';
$email = ''; // Initialize email variable

// Check if user just registered successfully
if (isset($_SESSION['registration_success'])) {
    $success = 'Registration successful! Please log in with your credentials.';
    $email = $_SESSION['registered_email'] ?? '';
    
    // Clear the session variables
    unset($_SESSION['registration_success']);
    unset($_SESSION['registered_email']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Validation
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If validation passes, proceed with login
    if (empty($errors)) {
        // Try to login
        if ($user->login($email, $password)) {
            $_SESSION['user'] = [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_pic' => $user->profile_pic,
                'account_type' => $user->account_type
            ];
            
            // Mark as new login to trigger cart reset
            $_SESSION['new_login'] = true;
            
            // Redirect admin users to admin dashboard
            if ($user->account_type == 1) {
                header('Location: admin/admin_index.php');
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
            $error = 'Invalid email or password';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// For testing purposes - can be removed in production
if (!$error && $email === 'user@example.com' && $password === 'password') {
    $_SESSION['user'] = [
        'user_id' => 1,
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
        'user_id' => 2,
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
    <link rel="stylesheet" href="css/validation.css">
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
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="post" class="auth-form" id="login-form" novalidate>
                        <div class="form-floating">
                            <input type="email" name="email" id="email" class="form-control" 
                                placeholder=" " value="<?= htmlspecialchars($email) ?>" required>
                            <label for="email" class="form-label">Email Address</label>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                            <div class="valid-feedback">Looks good!</div>
                        </div>
                        
                        <div class="form-floating password-field">
                            <input type="password" name="password" id="password" class="form-control" placeholder=" " required>
                            <label for="password" class="form-label">Password</label>
                            <button type="button" class="toggle-password">
                                <i class="far fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">Please enter your password.</div>
                            <div class="valid-feedback">Looks good!</div>
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
            
            // Form validation
            const form = document.getElementById('login-form');
            
            // Email validation function
            function validateEmail() {
                const emailInput = document.getElementById('email');
                const value = emailInput.value.trim();
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (!value) {
                    setInvalid(emailInput, 'Email address is required');
                    return false;
                } else if (!emailPattern.test(value)) {
                    setInvalid(emailInput, 'Please enter a valid email address');
                    return false;
                } else {
                    setValid(emailInput);
                    return true;
                }
            }
            
            // Password validation function
            function validatePassword() {
                const passwordInput = document.getElementById('password');
                const value = passwordInput.value;
                
                if (!value) {
                    setInvalid(passwordInput, 'Password is required');
                    return false;
                } else {
                    setValid(passwordInput);
                    return true;
                }
            }
            
            // Helper functions for validation UI
            function setInvalid(input, message) {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
                
                // Update feedback message if provided
                const feedback = input.nextElementSibling.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback') && message) {
                    feedback.textContent = message;
                }
            }
            
            function setValid(input) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            }
            
            // Real-time validation on input
            document.getElementById('email').addEventListener('input', validateEmail);
            document.getElementById('password').addEventListener('input', validatePassword);
            
            // Login button animation
            const loginButton = document.querySelector('.btn-auth');
            loginButton.addEventListener('click', function() {
                if (validateEmail() && validatePassword()) {
                    this.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Logging in...';
                    this.classList.add('btn-loading');
                }
            });
            
            // Form submission
            form.addEventListener('submit', function(event) {
                // Run all validations
                const isEmailValid = validateEmail();
                const isPasswordValid = validatePassword();
                
                // If any validation fails, prevent form submission
                if (!isEmailValid || !isPasswordValid) {
                    event.preventDefault();
                }
            });
            
            // Initial validation to show any pre-filled fields
            if (document.getElementById('email').value) validateEmail();
        });
    </script>
</body>
</html>

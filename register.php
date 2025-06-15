<?php
session_start();
require_once 'database.php';

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$db = new Database();

$error = '';
$success = '';
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $formData = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
    ];

    $errors = [];

    if (empty($first_name)) {
        $errors[] = 'First name is required';
    } elseif (strlen($first_name) < 2 || strlen($first_name) > 50) {
        $errors[] = 'First name must be between 2 and 50 characters';
    }

    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    } elseif (strlen($last_name) < 2 || strlen($last_name) > 50) {
        $errors[] = 'Last name must be between 2 and 50 characters';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }

    if (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
        $errors[] = 'You must agree to the terms and conditions';
    }

    if (empty($errors)) {
        // Remove redundant server-side emailExists check, since registerUser already checks
        $result = $db->registerUser($first_name, $last_name, $email, $password);
        if ($result['success']) {
            $_SESSION['registration_success'] = true;
            $_SESSION['registered_email'] = $email;
            $_SESSION['registered_first_name'] = $first_name;
            $_SESSION['registered_last_name'] = $last_name;
            header('Location: login.php');
            exit;
        } else {
            $error = !empty($result['error_message']) ? $result['error_message'] : 'Registration failed. Please try again.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
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
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="post" class="auth-form" id="registration-form" novalidate>
                        <div class="form-floating">
                            <input type="text" name="first_name" id="first_name" class="form-control" 
                                placeholder=" " value="<?= htmlspecialchars($formData['first_name']) ?>" required>
                            <label for="first_name" class="form-label">First Name</label>
                            <div class="invalid-feedback">Please enter your first name (2-50 characters).</div>
                            <div class="valid-feedback">Looks good!</div>
                        </div>
                        
                        <div class="form-floating">
                            <input type="text" name="last_name" id="last_name" class="form-control" 
                                placeholder=" " value="<?= htmlspecialchars($formData['last_name']) ?>" required>
                            <label for="last_name" class="form-label">Last Name</label>
                            <div class="invalid-feedback">Please enter your last name (2-50 characters).</div>
                            <div class="valid-feedback">Looks good!</div>
                        </div>
                        
                        <div class="form-floating">
                            <input type="email" name="email" id="email" class="form-control" 
                                placeholder=" " value="<?= htmlspecialchars($formData['email']) ?>" required>
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
                            <div class="invalid-feedback">Password must be at least 8 characters long.</div>
                            <div class="valid-feedback">Strong password!</div>
                            <div class="password-strength">
                                <div class="password-strength-meter">
                                    <div class="password-strength-meter-fill"></div>
                                </div>
                                <div class="password-strength-text"></div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                            <label for="terms" class="form-check-label">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                            <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                        </div>
                        
                        <button type="submit" class="btn-auth">Create Account</button>
                        
                        
                        
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
            
            // Form validation
            const form = document.getElementById('registration-form');
            
            // First Name validation function
            function validateFirstName() {
                const firstNameInput = document.getElementById('first_name');
                const value = firstNameInput.value.trim();
                if (!value) {
                    setInvalid(firstNameInput, 'First name is required');
                    return false;
                } else if (value.length < 2) {
                    setInvalid(firstNameInput, 'First name must be at least 2 characters');
                    return false;
                } else if (value.length > 50) {
                    setInvalid(firstNameInput, 'First name must be less than 50 characters');
                    return false;
                } else {
                    setValid(firstNameInput);
                    return true;
                }
            }
            
            // Last Name validation function
            function validateLastName() {
                const lastNameInput = document.getElementById('last_name');
                const value = lastNameInput.value.trim();
                if (!value) {
                    setInvalid(lastNameInput, 'Last name is required');
                    return false;
                } else if (value.length < 2) {
                    setInvalid(lastNameInput, 'Last name must be at least 2 characters');
                    return false;
                } else if (value.length > 50) {
                    setInvalid(lastNameInput, 'Last name must be less than 50 characters');
                    return false;
                } else {
                    setValid(lastNameInput);
                    return true;
                }
            }
            
            // Email validation function with AJAX check
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
                    // If basic validation passes, check if email exists via AJAX
                    checkEmailExists(value, emailInput);
                    return true;
                }
            }

            // AJAX function to check if email exists
            let emailCheckTimer;
            function checkEmailExists(email, inputElement) {
                clearTimeout(emailCheckTimer);
                inputElement.classList.remove('is-valid', 'is-invalid', 'is-validating');
                inputElement.classList.add('is-validating');
                const invalidFeedback = inputElement.nextElementSibling.nextElementSibling;
                const validFeedback = invalidFeedback.nextElementSibling;
                emailCheckTimer = setTimeout(() => {
                    fetch(`ajax/check_email.php?email=${encodeURIComponent(email)}`)
                        .then(response => response.json())
                        .then(data => {
                            inputElement.classList.remove('is-validating');
                            if (data.valid) {
                                setValid(inputElement);
                                if (validFeedback) {
                                    validFeedback.style.display = 'block';
                                    validFeedback.textContent = data.message;
                                }
                                if (invalidFeedback) invalidFeedback.style.display = 'none';
                            } else {
                                setInvalid(inputElement, data.message);
                                if (validFeedback) validFeedback.style.display = 'none';
                                if (invalidFeedback) invalidFeedback.style.display = 'block';
                            }
                        })
                        .catch(() => {
                            inputElement.classList.remove('is-validating');
                            inputElement.classList.remove('is-valid', 'is-invalid');
                        });
                }, 500);
            }
            
            // Password validation function with strength meter
            function validatePassword() {
                const passwordInput = document.getElementById('password');
                const value = passwordInput.value;
                const strengthMeter = document.querySelector('.password-strength');
                const strengthFill = document.querySelector('.password-strength-meter-fill');
                const strengthText = document.querySelector('.password-strength-text');
                
                // Reset previous classes
                strengthMeter.className = 'password-strength';
                
                if (!value) {
                    setInvalid(passwordInput, 'Password is required');
                    strengthText.textContent = '';
                    strengthFill.style.width = '0%';
                    return false;
                } 
                
                // Check password strength
                let strength = 0;
                
                // Length check
                if (value.length >= 8) strength += 1;
                if (value.length >= 12) strength += 1;
                
                // Complexity checks
                if (/[A-Z]/.test(value)) strength += 1;  // Has uppercase
                if (/[a-z]/.test(value)) strength += 1;  // Has lowercase
                if (/[0-9]/.test(value)) strength += 1;  // Has number
                if (/[^A-Za-z0-9]/.test(value)) strength += 1;  // Has special character
                
                // Set strength indicator
                if (value.length < 8) {
                    setInvalid(passwordInput, 'Password must be at least 8 characters');
                    strengthMeter.classList.add('strength-weak');
                    strengthText.textContent = 'Too short';
                    return false;
                } else if (strength < 3) {
                    setValid(passwordInput);
                    strengthMeter.classList.add('strength-weak');
                    strengthText.textContent = 'Weak';
                    return true;
                } else if (strength < 5) {
                    setValid(passwordInput);
                    strengthMeter.classList.add('strength-medium');
                    strengthText.textContent = 'Medium';
                    return true;
                } else if (strength < 6) {
                    setValid(passwordInput);
                    strengthMeter.classList.add('strength-good');
                    strengthText.textContent = 'Good';
                    return true;
                } else {
                    setValid(passwordInput);
                    strengthMeter.classList.add('strength-strong');
                    strengthText.textContent = 'Strong';
                    return true;
                }
            }
            
            // Terms validation function
            function validateTerms() {
                const termsCheckbox = document.getElementById('terms');
                
                if (!termsCheckbox.checked) {
                    setInvalid(termsCheckbox, 'You must agree to the terms and conditions');
                    return false;
                } else {
                    setValid(termsCheckbox);
                    return true;
                }
            }
            
            // Helper functions for validation UI
            function setInvalid(input, message) {
                input.classList.remove('is-valid', 'is-validating');
                input.classList.add('is-invalid');
                
                // Update feedback message if provided
                const feedback = input.nextElementSibling.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback') && message) {
                    feedback.textContent = message;
                }
            }
            
            function setValid(input) {
                input.classList.remove('is-invalid', 'is-validating');
                input.classList.add('is-valid');
            }
            
            // Real-time validation on input
            document.getElementById('first_name').addEventListener('input', validateFirstName);
            document.getElementById('last_name').addEventListener('input', validateLastName);
            
            // Email field: only trigger AJAX check if valid format
            const emailInput = document.getElementById('email');
            emailInput.addEventListener('input', function() {
                this.classList.remove('is-invalid', 'is-valid');
                const value = this.value.trim();
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!value) {
                    setInvalid(this, 'Email address is required');
                    const validFeedback = this.nextElementSibling.nextElementSibling.nextElementSibling;
                    if (validFeedback) validFeedback.style.display = 'none';
                } else if (!emailPattern.test(value)) {
                    setInvalid(this, 'Please enter a valid email address');
                    const validFeedback = this.nextElementSibling.nextElementSibling.nextElementSibling;
                    if (validFeedback) validFeedback.style.display = 'none';
                } else {
                    // Only trigger AJAX check if valid format
                    checkEmailExists(value, this);
                }
            });
            emailInput.addEventListener('blur', validateEmail);

            document.getElementById('password').addEventListener('input', validatePassword);
            document.getElementById('terms').addEventListener('change', validateTerms);

            // Form submission validation
            form.addEventListener('submit', function(event) {
                const isFirstNameValid = validateFirstName();
                const isLastNameValid = validateLastName();
                const isEmailValid = validateEmail();
                const isPasswordValid = validatePassword();
                const areTermsAccepted = validateTerms();
                if (emailInput.classList.contains('is-validating')) {
                    event.preventDefault();
                    setTimeout(() => {
                        if (!emailInput.classList.contains('is-invalid')) {
                            form.submit();
                        }
                    }, 800);
                }
                if (!isFirstNameValid || !isLastNameValid ||
                    emailInput.classList.contains('is-invalid') ||
                    !isPasswordValid || !areTermsAccepted) {
                    event.preventDefault();
                }
            });
            
            // Initial validation to show any pre-filled fields
            if (document.getElementById('first_name').value) validateFirstName();
            if (document.getElementById('last_name').value) validateLastName();
            if (document.getElementById('email').value) validateEmail();
        });
    </script>
</body>
</html>

<?php
session_start();
require_once 'includes/db_connection.php';

// Check if cart exists and has items
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: shop.php');
    exit;
}

// Calculate cart total
$cart_total = 0;
$cart_items = [];
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
    $cart_items[] = $item;
}

// Get user data if logged in
$user_data = [];
if (isset($_SESSION['user'])) {
    $stmt = $conn->prepare("SELECT first_name, last_name, email, phone, address, city, zip, country FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user']['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en"></html>
<head>
    <link rel="stylesheet" href="css/footer.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="checkout-container">
            <div class="checkout-steps">
                <div class="step active" data-step="1">
                    <i class="fas fa-user"></i>
                    <span>Account</span>
                </div>
                <div class="step" data-step="2">
                    <i class="fas fa-shipping-fast"></i>
                    <span>Shipping</span>
                </div>
                <div class="step" data-step="3">
                    <i class="fas fa-credit-card"></i>
                    <span>Payment</span>
                </div>
                <div class="step" data-step="4">
                    <i class="fas fa-check-circle"></i>
                    <span>Confirm</span>
                </div>
            </div>

            <form id="checkout-form" action="process_order.php" method="POST" class="checkout-form">
                <!-- Step 1: Account Information -->
                <div class="checkout-step active" id="step-1">
                    <h2>Account Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user_data['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user_data['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-primary next-step">Continue to Shipping</button>
                    </div>
                </div>

                <!-- Step 2: Shipping Information -->
                <div class="checkout-step" id="step-2">
                    <h2>Shipping Information</h2>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?= htmlspecialchars($user_data['address'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?= htmlspecialchars($user_data['city'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="zip">ZIP Code</label>
                            <input type="text" id="zip" name="zip" value="<?= htmlspecialchars($user_data['zip'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <select id="country" name="country" required>
                                <option value="">Select Country</option>
                                <option value="US" <?= ($user_data['country'] ?? '') === 'US' ? 'selected' : '' ?>>United States</option>
                                <option value="CA" <?= ($user_data['country'] ?? '') === 'CA' ? 'selected' : '' ?>>Canada</option>
                                <option value="UK" <?= ($user_data['country'] ?? '') === 'UK' ? 'selected' : '' ?>>United Kingdom</option>
                                <!-- Add more countries as needed -->
                            </select>
                        </div>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-secondary prev-step">Back</button>
                        <button type="button" class="btn btn-primary next-step">Continue to Payment</button>
                    </div>
                </div>

                <!-- Step 3: Payment Information -->
                <div class="checkout-step" id="step-3">
                    <h2>Payment Information</h2>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>
                        </div>
                        <div class="form-group">
                            <label for="expiry">Expiry Date</label>
                            <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required>
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" name="cvv" placeholder="123" required>
                        </div>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-secondary prev-step">Back</button>
                        <button type="button" class="btn btn-primary next-step">Review Order</button>
                    </div>
                </div>

                <!-- Step 4: Order Review -->
                <div class="checkout-step" id="step-4">
                    <h2>Review Your Order</h2>
                    <div class="order-summary">
                        <div class="order-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                    </div>
                                    <div class="item-details">
                                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                                        <div class="item-meta">
                                            <span class="quantity">Qty: <?= $item['quantity'] ?></span>
                                            <span class="price">$<?= number_format($item['price'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-total">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span>$<?= number_format($cart_total, 2) ?></span>
                            </div>
                            <div class="total-row">
                                <span>Shipping:</span>
                                <span>Free</span>
                            </div>
                            <div class="total-row grand-total">
                                <span>Total:</span>
                                <span>$<?= number_format($cart_total, 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn btn-secondary prev-step">Back</button>
                        <button type="submit" class="btn btn-primary">Place Order</button>
                    </div>
                </div>

                <!-- Hidden fields for order data -->
                <input type="hidden" name="order_items" value='<?= json_encode($cart_items) ?>'>
                <input type="hidden" name="order_total" value="<?= $cart_total ?>">
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <style>
        .checkout-container {
            padding: 40px 0;
        }

        .checkout-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }

        .checkout-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            background: #fff;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            transition: all 0.3s ease;
        }

        .step i {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .step span {
            font-size: 12px;
            font-weight: 500;
        }

        .step.active {
            background: var(--primary-color);
            color: #fff;
        }

        .checkout-step {
            display: none;
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .checkout-step.active {
            display: block;
        }

        .checkout-step h2 {
            margin-bottom: 30px;
            color: var(--dark-color);
            font-size: 1.8rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-light);
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .step-buttons {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .order-summary {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
        }

        .order-items {
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-details h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .item-meta {
            display: flex;
            justify-content: space-between;
            color: var(--text-light);
        }

        .order-total {
            border-top: 2px solid #e0e0e0;
            padding-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: var(--text-light);
        }

        .grand-total {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }

        @media (max-width: 768px) {
            .checkout-steps {
                flex-wrap: wrap;
                gap: 20px;
            }

            .step {
                width: 50px;
                height: 50px;
            }

            .step i {
                font-size: 20px;
            }

            .step span {
                display: none;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .step-buttons {
                flex-direction: column;
            }

            .step-buttons .btn {
                width: 100%;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const steps = document.querySelectorAll('.checkout-step');
            const stepIndicators = document.querySelectorAll('.step');
            const nextButtons = document.querySelectorAll('.next-step');
            const prevButtons = document.querySelectorAll('.prev-step');
            const form = document.getElementById('checkout-form');

            // Handle next step
            nextButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const currentStep = this.closest('.checkout-step');
                    const currentStepNumber = parseInt(currentStep.id.split('-')[1]);
                    const nextStepNumber = currentStepNumber + 1;

                    // Validate current step
                    const inputs = currentStep.querySelectorAll('input[required], select[required]');
                    let isValid = true;
                    inputs.forEach(input => {
                        if (!input.value) {
                            isValid = false;
                            input.classList.add('error');
                        } else {
                            input.classList.remove('error');
                        }
                    });

                    if (!isValid) {
                        alert('Please fill in all required fields');
                        return;
                    }

                    // Move to next step
                    currentStep.classList.remove('active');
                    document.getElementById(`step-${nextStepNumber}`).classList.add('active');
                    stepIndicators[currentStepNumber].classList.add('active');
                });
            });

            // Handle previous step
            prevButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const currentStep = this.closest('.checkout-step');
                    const currentStepNumber = parseInt(currentStep.id.split('-')[1]);
                    const prevStepNumber = currentStepNumber - 1;

                    currentStep.classList.remove('active');
                    document.getElementById(`step-${prevStepNumber}`).classList.add('active');
                    stepIndicators[currentStepNumber - 1].classList.remove('active');
                });
            });

            // Format card number
            const cardNumber = document.getElementById('card_number');
            cardNumber.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{4})/g, '$1 ').trim();
                e.target.value = value;
            });

            // Format expiry date
            const expiry = document.getElementById('expiry');
            expiry.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.slice(0,2) + '/' + value.slice(2,4);
                }
                e.target.value = value;
            });

            // Format CVV
            const cvv = document.getElementById('cvv');
            cvv.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '').slice(0,3);
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading state
                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                // Submit form
                this.submit();
            });
        });
    </script>
</body>
</html>
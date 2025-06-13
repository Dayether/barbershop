<?php
session_start();

// Load payment errors from session if any
$payment_errors = [];
if (isset($_SESSION['payment_errors'])) {
    $payment_errors = $_SESSION['payment_errors'];
    unset($_SESSION['payment_errors']);
}

// Initialize cart from session
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Only redirect if cart is truly empty
if (empty($cart)) {
    header('Location: shop.php');
    exit;
}

// Calculate totals for summary
$total = 0;
$items_count = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
    $items_count += $item['quantity'];
}
$shipping_cost = 5.00;
$tax_amount = $total * 0.08;
$final_total = $total + $shipping_cost + $tax_amount;

// For repopulating form fields after error
function old($key, $default = '') {
    if (isset($_POST[$key])) return htmlspecialchars($_POST[$key]);
    return $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/payment.css">  
        <link rel="stylesheet" href="css/footer.css">


    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <!-- Add SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>Checkout</h1>
            <div class="breadcrumb">
                <a href="index.php">Home</a> / 
                <a href="shop.php">Shop</a> / 
                <span>Checkout</span>
            </div>
        </div>
    </section>

    <!-- Checkout Section -->
    <section class="checkout-section">
        <div class="container">
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($payment_errors)): ?>
                <div class="alert alert-danger">
                    <strong>Please correct the following errors:</strong>
                    <ul style="margin: 5px 0 0 20px;">
                        <?php foreach($payment_errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="checkout-grid">
                <!-- Left Column: Billing Details -->
                <div class="checkout-form">
                    <h2>Billing Details</h2>
                    <form method="post" action="process_payment.php" id="checkout-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" required 
                                    value="<?php
                                        if (isset($_SESSION['user'])) {
                                            $user = $_SESSION['user'];
                                            if (!empty($user['first_name'])) {
                                                echo htmlspecialchars($user['first_name']);
                                            } elseif (!empty($user['name'])) {
                                                echo htmlspecialchars(explode(' ', $user['name'])[0]);
                                            } else {
                                                echo '';
                                            }
                                        }
                                        // Repopulate after error
                                        echo old('first_name');
                                    ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" required
                                    value="<?php
                                        if (isset($_SESSION['user'])) {
                                            $user = $_SESSION['user'];
                                            if (!empty($user['last_name'])) {
                                                echo htmlspecialchars($user['last_name']);
                                            } elseif (!empty($user['name']) && strpos($user['name'], ' ') !== false) {
                                                echo htmlspecialchars(substr($user['name'], strpos($user['name'], ' ') + 1));
                                            } else {
                                                echo '';
                                            }
                                        }
                                        // Repopulate after error
                                        echo old('last_name');
                                    ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required
                                value="<?php echo old('email', isset($_SESSION['user']) ? $_SESSION['user']['email'] : ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" required value="<?php echo old('phone'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Street Address <span class="required">*</span></label>
                            <input type="text" id="address" name="address" placeholder="House number and street name" required value="<?php echo old('address'); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">Town / City <span class="required">*</span></label>
                                <input type="text" id="city" name="city" required value="<?php echo old('city'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="zip">ZIP Code <span class="required">*</span></label>
                                <input type="text" id="zip" name="zip" required value="<?php echo old('zip'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Country <span class="required">*</span></label>
                            <select id="country" name="country" required>
                                <option value="">Select a country</option>
                                <option value="USA" <?php echo old('country')=='USA'?'selected':''; ?>>United States</option>
                                <option value="CA" <?php echo old('country')=='CA'?'selected':''; ?>>Canada</option>
                                <option value="PH" <?php echo old('country')=='PH'?'selected':''; ?>>Philippines</option>
                                <option value="DE" <?php echo old('country')=='DE'?'selected':''; ?>>Germany</option>
                                <option value="UK" <?php echo old('country')=='UK'?'selected':''; ?>>United Kingdom</option>
                                <option value="AU" <?php echo old('country')=='AU'?'selected':''; ?>>Australia</option>
                                <option value="IN" <?php echo old('country')=='IN'?'selected':''; ?>>India</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Order Notes (Optional)</label>
                            <textarea id="notes" name="notes" placeholder="Notes about your order, e.g. special delivery instructions"><?php echo old('notes'); ?></textarea>
                        </div>
                    
                        <h2 class="mt-40">Payment Method</h2>
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" id="payment_credit_card" name="payment_method" value="credit_card" checked>
                                <label for="payment_credit_card">Credit Card</label>
                                <div class="payment-box" id="credit-card-box">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="card_number">Card Number <span class="required">*</span></label>
                                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" autocomplete="cc-number">
                                            <span class="error-message">Please enter a valid card number</span>
                                        </div>
                                        <div class="form-group">
                                            <label for="card_name">Name on Card <span class="required">*</span></label>
                                            <input type="text" id="card_name" name="card_name" autocomplete="cc-name">
                                            <span class="error-message">Please enter the name on your card</span>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="expiry_date">Expiry Date <span class="required">*</span></label>
                                            <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" autocomplete="cc-exp">
                                            <span class="error-message">Please enter a valid expiry date (MM/YY)</span>
                                        </div>
                                        <div class="form-group">
                                            <label for="cvv">CVV <span class="required">*</span></label>
                                            <input type="text" id="cvv" name="cvv" placeholder="123" autocomplete="cc-csc">
                                            <span class="error-message">Please enter your card's security code</span>
                                        </div>
                                    </div>
                                    <div class="credit-cards">
                                        <i class="fab fa-cc-visa"></i>
                                        <i class="fab fa-cc-mastercard"></i>
                                        <i class="fab fa-cc-amex"></i>
                                        <i class="fab fa-cc-discover"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="payment_paypal" name="payment_method" value="paypal">
                                <label for="payment_paypal">PayPal</label>
                                <div class="payment-box" id="paypal-box">
                                    <p>Pay via PayPal; you can pay with your credit card if you don't have a PayPal account.</p>
                                    <i class="fab fa-paypal"></i>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="cart_data" id="cart-data" value="<?php echo htmlspecialchars(json_encode(array_values($_SESSION['cart']))); ?>">
                        <input type="hidden" name="total_amount" value="<?php echo $final_total; ?>">
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-place-order" id="place-order-btn">
                                <i class="fas fa-lock"></i> Place Order
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Right Column: Order Summary -->
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    
                    <div class="summary-item">
                        <span id="items-count">Items (<?php echo $items_count; ?>)</span>
                        <span id="items-subtotal">$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span>Shipping</span>
                        <span id="shipping-fee">$<?php echo number_format($shipping_cost, 2); ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span>Tax</span>
                        <span id="tax-fee">$<?php echo number_format($tax_amount, 2); ?></span>
                    </div>
                    
                    <div class="summary-divider"></div>
                    
                    <div class="summary-total">
                        <span>Order Total</span>
                        <span id="cart-total">$<?php echo number_format($final_total, 2); ?></span>
                    </div>
                    
                    <div class="order-items">
                        <h3>Your Items</h3>
                        <div class="items-container" id="items-container">
                            <?php if (count($_SESSION['cart']) > 0): ?>
                                <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                                    <div class="order-item" id="item-<?php echo $product_id; ?>">
                                        <div class="item-image">
                                            <img src="<?php echo !empty($item['image']) ? $item['image'] : 'images/product-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>
                                        <div class="item-info">
                                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                            <div class="item-price">
                                                $<?php echo number_format($item['price'], 2); ?> x 
                                                <div class="quantity-control">
                                                    <form method="post" class="quantity-form">
                                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                                        <button type="button" class="qty-btn decrease-qty" data-id="<?php echo $product_id; ?>">-</button>
                                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="qty-input" data-id="<?php echo $product_id; ?>">
                                                        <button type="button" class="qty-btn increase-qty" data-id="<?php echo $product_id; ?>">+</button>
                                                        <input type="hidden" name="update_quantity" value="1">
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="item-subtotal">
                                            $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                        </div>
                                        <form method="post" class="remove-item-form">
                                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                            <button type="button" class="remove-item" data-id="<?php echo $product_id; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-cart-message">
                                    <i class="fas fa-shopping-bag"></i>
                                    <p>Your cart is empty</p>
                                    <a href="shop.php" class="btn btn-outline">Shop Now</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="coupon-box">
                        <h3>Have a Promo Code?</h3>
                        <div class="coupon-form">
                            <input type="text" placeholder="Enter promo code" id="promo-code">
                            <button type="button" id="apply-promo">
                                <i class="fas fa-tag"></i> Apply
                            </button>
                        </div>
                    </div>
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
            timeout: 4000,
            resetOnHover: true,
            position: 'topRight',
            transitionIn: 'flipInX',
            transitionOut: 'flipOutX',
        });
        
        <?php if(!empty($payment_errors)): ?>
        // Show error toast for payment errors
        iziToast.error({
            title: 'Payment Error',
            message: 'Please review the form and correct all errors.',
            icon: 'fas fa-exclamation-triangle',
            position: 'topCenter',
            timeout: 5000
        });
        <?php endif; ?>

        // Improved Form Validation
        const checkoutForm = document.getElementById('checkout-form');
        if (checkoutForm) {
            // Add input event listeners to remove error class when user starts typing
            const formInputs = checkoutForm.querySelectorAll('input, select, textarea');
            formInputs.forEach(input => {
                // Remove error state when user interacts with the field
                input.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        this.classList.remove('error');
                    }
                });
                
                // For select elements, also listen to change event
                if (input.tagName === 'SELECT') {
                    input.addEventListener('change', function() {
                        if (this.classList.contains('error')) {
                            this.classList.remove('error');
                        }
                    });
                }
            });
            
            // Form submission validation
            checkoutForm.addEventListener('submit', function(e) {
                let hasErrors = false;
                
                // Clear previous error states
                document.querySelectorAll('.error').forEach(el => {
                    el.classList.remove('error');
                });
                
                // Validate required fields
                const requiredFields = checkoutForm.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('error');
                        hasErrors = true;
                    }
                });
                
                // Email validation with better regex
                const emailField = document.getElementById('email');
                if (emailField && emailField.value.trim()) {
                    const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                    if (!emailRegex.test(emailField.value.trim())) {
                        emailField.classList.add('error');
                        hasErrors = true;
                    }
                }
                
                // Credit card validation if credit card payment is selected
                if (document.getElementById('payment_credit_card').checked) {
                    const cardFields = ['card_number', 'card_name', 'expiry_date', 'cvv'];
                    cardFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (field && !field.value.trim()) {
                            field.classList.add('error');
                            hasErrors = true;
                        }
                    });
                    
                    // Enhanced card validations
                    const cardNumberField = document.getElementById('card_number');
                    if (cardNumberField && cardNumberField.value.trim()) {
                        const cardNumber = cardNumberField.value.replace(/\s/g, '');
                        // Basic Luhn algorithm check could be added here for production
                        if (!/^\d{13,19}$/.test(cardNumber)) {
                            cardNumberField.classList.add('error');
                            hasErrors = true;
                        }
                    }
                    
                    // Validate expiry date format and check if not expired
                    const expiryField = document.getElementById('expiry_date');
                    if (expiryField && expiryField.value.trim()) {
                        if (!/^\d{2}\/\d{2}$/.test(expiryField.value)) {
                            expiryField.classList.add('error');
                            hasErrors = true;
                        } else {
                            // Check if card is expired
                            const [month, year] = expiryField.value.split('/').map(part => parseInt(part, 10));
                            const now = new Date();
                            const currentYear = now.getFullYear() % 100; // Get last 2 digits
                            const currentMonth = now.getMonth() + 1; // getMonth is 0-indexed
                            
                            if (year < currentYear || (year === currentYear && month < currentMonth)) {
                                expiryField.classList.add('error');
                                hasErrors = true;
                            }
                        }
                    }
                    
                    // Validate CVV format
                    const cvvField = document.getElementById('cvv');
                    if (cvvField && cvvField.value.trim()) {
                        if (!/^\d{3,4}$/.test(cvvField.value)) {
                            cvvField.classList.add('error');
                            hasErrors = true;
                        }
                    }
                }
                
                // Check if cart is empty
                if (<?php echo count($_SESSION['cart']) === 0 ? 'true' : 'false'; ?>) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'No products ordered',
                        text: 'Please proceed to shop first.',
                        confirmButtonText: 'Go to Shop',
                        allowOutsideClick: false
                    }).then(function() {
                        window.location.href = 'shop.php';
                    });
                    return false;
                }
                
                if (hasErrors) {
                    e.preventDefault();
                    iziToast.error({
                        title: 'Form Error',
                        message: 'Please fill in all required fields correctly.',
                        icon: 'fas fa-exclamation-triangle'
                    });
                    
                    // Scroll to the first error with smooth animation
                    const firstError = document.querySelector('.error');
                    if (firstError) {
                        firstError.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                        
                        // Add a subtle highlight animation
                        firstError.animate([
                            { backgroundColor: 'rgba(138, 56, 58, 0.2)' },
                            { backgroundColor: 'transparent' }
                        ], {
                            duration: 1000,
                            iterations: 1
                        });
                    }
                    return false;
                } else {
                    // Show loading state
                    const submitBtn = document.getElementById('place-order-btn');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                        submitBtn.disabled = true;
                    }
                    
                    // Show toast notification
                    iziToast.info({
                        title: 'Processing',
                        message: 'Your order is being processed...',
                        icon: 'fas fa-spinner fa-spin',
                        timeout: 10000,
                        position: 'center'
                    });
                }
            });
        }

        // Handle remove item buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                
                iziToast.question({
                    timeout: 10000,
                    close: false,
                    overlay: true,
                    displayMode: 'once',
                    id: 'question',
                    zindex: 999,
                    title: 'Remove Item',
                    message: 'Are you sure you want to remove this item?',
                    position: 'center',
                    buttons: [
                        ['<button><b>YES</b></button>', function (instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            
                            // Show loading animation
                            const removeBtn = document.querySelector(`.remove-item[data-id="${productId}"]`);
                            if (removeBtn) {
                                removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                            }
                            
                            // AJAX request to remove item
                            fetch('update_cart.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'action=remove&product_id=' + productId
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Remove the item from the DOM with animation
                                    const item = document.getElementById('item-' + productId);
                                    if (item) {
                                        item.style.opacity = '0';
                                        item.style.transform = 'translateX(20px)';
                                        
                                        setTimeout(() => {
                                            item.remove();
                                            
                                            // Update cart totals
                                            const itemsCountElem = document.getElementById('items-count');
                                            const subtotalElem = document.getElementById('items-subtotal');
                                            const taxElem = document.querySelector('.summary-item:nth-child(3) span:last-child');
                                            const totalElem = document.getElementById('cart-total');
                                            const cartDataElem = document.getElementById('cart-data');
                                            
                                            // Update items count
                                            if (itemsCountElem) itemsCountElem.textContent = 'Items (' + data.itemsCount + ')';
                                            // Update subtotal
                                            if (subtotalElem) subtotalElem.textContent = '$' + data.subtotal.toFixed(2);
                                            // Update tax
                                            if (taxElem) taxElem.textContent = '$' + data.tax.toFixed(2);
                                            // Update total
                                            if (totalElem) totalElem.textContent = '$' + data.total.toFixed(2);
                                            if (cartDataElem) cartDataElem.value = JSON.stringify(data.cart);
                                            
                                            // Show success message
                                            iziToast.success({
                                                title: 'Item Removed',
                                                message: 'Item has been removed from your cart',
                                                icon: 'fas fa-check-circle'
                                            });
                                            
                                            // Display empty cart message if cart is empty
                                            const itemsContainer = document.getElementById('items-container');
                                            if (data.itemsCount === 0 && itemsContainer) {
                                                itemsContainer.innerHTML = `
                                                    <div class="empty-cart-message">
                                                        <i class="fas fa-shopping-bag"></i>
                                                        <p>Your cart is empty</p>
                                                        <a href="shop.php" class="btn btn-outline">Shop Now</a>
                                                    </div>
                                                `;
                                            }
                                        }, 300);
                                    }
                                } else {
                                    // Show error message
                                    iziToast.error({
                                        title: 'Error',
                                        message: 'Failed to remove item. Please try again.',
                                        icon: 'fas fa-exclamation-circle'
                                    });
                                    const removeBtn = document.querySelector(`.remove-item[data-id="${productId}"]`);
                                    if (removeBtn) {
                                        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                iziToast.error({
                                    title: 'Error',
                                    message: 'Something went wrong. Please try again.',
                                    icon: 'fas fa-exclamation-circle'
                                });
                                const removeBtn = document.querySelector(`.remove-item[data-id="${productId}"]`);
                                if (removeBtn) {
                                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                                }
                            });
                        }, true],
                        ['<button>NO</button>', function (instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        }],
                    ]
                });
            });
        });
        
        // OOP-style Cart Manager for quantity control
        class CartManager {
            constructor() {
                this.initQuantityControls();
            }
            
            initQuantityControls() {
                // Handle quantity input changes
                document.querySelectorAll('.qty-input').forEach(input => {
                    input.addEventListener('change', (e) => this.handleQuantityChange(e));
                });
                
                // Handle increase button clicks
                document.querySelectorAll('.increase-qty').forEach(button => {
                    button.addEventListener('click', (e) => this.handleIncrease(e));
                });
                
                // Handle decrease button clicks
                document.querySelectorAll('.decrease-qty').forEach(button => {
                    button.addEventListener('click', (e) => this.handleDecrease(e));
                });
            }
            
            handleQuantityChange(event) {
                const input = event.target;
                const productId = input.getAttribute('data-id');
                let quantity = parseInt(input.value);
                
                if (isNaN(quantity) || quantity < 1) {
                    quantity = 1;
                    input.value = 1;
                }
                
                if (quantity <= 0) {
                    const removeBtn = document.querySelector(`.remove-item[data-id="${productId}"]`);
                    if (removeBtn) {
                        removeBtn.click();
                    }
                    return;
                }
                
                this.updateQuantity(productId, quantity);
            }
            
            handleIncrease(event) {
                const button = event.target.closest('.increase-qty');
                const productId = button.getAttribute('data-id');
                const input = document.querySelector(`.qty-input[data-id="${productId}"]`);
                
                if (input) {
                    let value = parseInt(input.value) || 0;
                    input.value = value + 1;
                    this.updateQuantity(productId, parseInt(input.value));
                }
            }
            
            handleDecrease(event) {
                const button = event.target.closest('.decrease-qty');
                const productId = button.getAttribute('data-id');
                const input = document.querySelector(`.qty-input[data-id="${productId}"]`);
                
                if (input) {
                    let value = parseInt(input.value) || 0;
                    if (value > 1) {
                        input.value = value - 1;
                        this.updateQuantity(productId, parseInt(input.value));
                    } else {
                        const removeBtn = document.querySelector(`.remove-item[data-id="${productId}"]`);
                        if (removeBtn) {
                            removeBtn.click();
                        }
                    }
                }
            }
            
            updateQuantity(productId, quantity) {
                // Show loading indicator on the quantity element
                const item = document.getElementById('item-' + productId);
                if (!item) return;
                item.classList.add('updating');
                fetch('update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    credentials: 'same-origin',
                    body: 'action=update&product_id=' + productId + '&quantity=' + quantity
                })
                .then(response => response.json())
                .then(data => {
                    item.classList.remove('updating');
                    console.log('AJAX update_cart.php response:', data); // Debug log
                    if (data.success) {
                        this.updateCartSummary(data);
                        iziToast.success({
                            title: 'Updated',
                            message: 'Cart has been updated',
                            icon: 'fas fa-check-circle',
                            position: 'bottomRight'
                        });
                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: data.message || 'Failed to update quantity',
                            icon: 'fas fa-exclamation-circle'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    item.classList.remove('updating');
                    iziToast.error({
                        title: 'Error',
                        message: 'Something went wrong. Please try again. (AJAX parse error)',
                        icon: 'fas fa-exclamation-circle'
                    });
                });
            }
            
            updateCartSummary(data) {
                const itemsCountElem = document.getElementById('items-count');
                const subtotalElem = document.getElementById('items-subtotal');
                const shippingElem = document.getElementById('shipping-fee');
                const taxElem = document.getElementById('tax-fee');
                const totalElem = document.getElementById('cart-total');
                const cartDataElem = document.getElementById('cart-data');

                // Update items count
                if (itemsCountElem) itemsCountElem.textContent = 'Items (' + data.itemsCount + ')';
                // Update subtotal (price beside items count)
                if (subtotalElem) subtotalElem.textContent = '$' + data.subtotal.toFixed(2);
                // Update shipping
                if (shippingElem && data.shipping !== undefined) shippingElem.textContent = '$' + data.shipping.toFixed(2);
                // Update tax
                if (taxElem && data.tax !== undefined) taxElem.textContent = '$' + data.tax.toFixed(2);
                // Update total
                if (totalElem) totalElem.textContent = '$' + data.total.toFixed(2);
                // Update hidden cart data
                if (cartDataElem) cartDataElem.value = JSON.stringify(data.cart);

                // Update item subtotal for the changed item
                if (data.product_id !== undefined) {
                    const item = document.getElementById('item-' + data.product_id);
                    if (item) {
                        const subtotalElem = item.querySelector('.item-subtotal');
                        if (subtotalElem) {
                            subtotalElem.textContent = '$' + (data.itemSubtotal ? data.itemSubtotal.toFixed(2) : '0.00');
                        }
                    }
                }

                // If cart is empty, show empty message
                const itemsContainer = document.getElementById('items-container');
                if (data.cart && data.cart.length === 0 && itemsContainer) {
                    itemsContainer.innerHTML = `
                        <div class="empty-cart-message">
                            <i class="fas fa-shopping-bag"></i>
                            <p>Your cart is empty</p>
                            <a href="shop.php" class="btn btn-outline">Shop Now</a>
                        </div>
                    `;
                }

                // If a discount is applied, update it
                if (document.querySelector('.summary-discount')) {
                    this.recalculateDiscount && this.recalculateDiscount();
                }
            }

            // Helper to escape HTML for item names
            escapeHtml(text) {
                const map = {
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, function(m) { return map[m]; });
            }
        }
        
        // Initialize Cart Manager
        const cartManager = new CartManager();

        // Handle promo code
        document.getElementById('apply-promo').addEventListener('click', function() {
            const promoCode = document.getElementById('promo-code').value.trim().toUpperCase();
            
            if (promoCode === '') {
                iziToast.warning({
                    title: 'Empty Code',
                    message: 'Please enter a promo code',
                    icon: 'fas fa-exclamation-triangle'
                });
                return;
            }
            
            // Show loading state
            const button = this;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing';
            button.disabled = true;
            
            // Simulate AJAX request to verify promo code
            setTimeout(() => {
                if (promoCode === 'WELCOME10') {
                    // Calculate 10% discount
                    const subtotal = parseFloat(document.querySelector('.summary-item:first-child span:last-child').textContent.replace('$', ''));
                    const discount = subtotal * 0.1;
                    
                    // Add discount row if not exists
                    if (!document.querySelector('.summary-discount')) {
                        const discountRow = document.createElement('div');
                        discountRow.className = 'summary-item summary-discount';
                        discountRow.innerHTML = `
                            <span>Discount (10%)</span>
                            <span>-$${discount.toFixed(2)}</span>
                        `;
                        
                        // Insert before the divider
                        document.querySelector('.summary-divider').before(discountRow);
                    } else {
                        // Update existing discount
                        document.querySelector('.summary-discount span:last-child').textContent = `-$${discount.toFixed(2)}`;
                    }
                    
                    // Recalculate total
                    const shipping = parseFloat(document.querySelector('.summary-item:nth-child(2) span:last-child').textContent.replace('$', ''));
                    const tax = parseFloat(document.querySelector('.summary-item:nth-child(3) span:last-child').textContent.replace('$', ''));
                    const newTotal = subtotal - discount + shipping + tax;
                    
                    // Update displayed total
                    document.getElementById('cart-total').textContent = `$${newTotal.toFixed(2)}`;
                    
                    // Update hidden input for total
                    document.querySelector('input[name="total_amount"]').value = newTotal.toFixed(2);
                    
                    // Store promo code in session
                    fetch('update_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=apply_promo&code=' + promoCode + '&discount=' + discount.toFixed(2)
                    });
                    
                    // Show success message
                    iziToast.success({
                        title: 'Promo Code Applied',
                        message: '10% discount has been applied to your order',
                        icon: 'fas fa-tag'
                    });
                    
                    // Update button and input
                    button.innerHTML = '<i class="fas fa-check"></i> Applied';
                    button.disabled = true;
                    document.getElementById('promo-code').disabled = true;
                } else {
                    // Invalid promo code
                    iziToast.error({
                        title: 'Invalid Code',
                        message: 'The promo code you entered is not valid',
                        icon: 'fas fa-exclamation-circle'
                    });
                    
                    button.innerHTML = '<i class="fas fa-tag"></i> Apply';
                    button.disabled = false;
                }
            }, 1000);
        });
    });
    </script>

    <!-- Credit card formatting script -->
    <script>
       document.addEventListener('DOMContentLoaded', function() {
    // Toggle payment methods
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            document.querySelectorAll('.payment-box').forEach(box => {
                box.style.display = 'none';
            });

            if (this.checked) {
                const paymentBox = this.nextElementSibling.nextElementSibling;
                paymentBox.style.display = 'block';
            }
        });
    });

    // Initialize the correct payment box
    document.querySelectorAll('input[name="payment_method"]:checked').forEach(method => {
        const event = new Event('change');
        method.dispatchEvent(event);
    });

    // Credit card input formatting
    const cardNumber = document.getElementById('card_number');
    if (cardNumber) {
        cardNumber.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';

            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }

            e.target.value = formattedValue;
        });
    }
});
            
            // Expiry date validation (MM/YY only, no forced formatting)
           const expiryDate = document.getElementById('expiry_date');
if (expiryDate) {
    expiryDate.addEventListener('blur', function (e) {
        const val = e.target.value.trim();
        const errorMsg = e.target.nextElementSibling;
        let valid = true;
        let msg = '';

        if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(val)) {
            valid = false;
            msg = 'Format must be MM/YY (e.g. 05/27)';
        } else {
            const [mm, yy] = val.split('/');
            let expMonth = parseInt(mm, 10);
            let expYear = parseInt(yy, 10) + 2000;
            const now = new Date();
            const expDate = new Date(expYear, expMonth - 1, 1);
            const thisMonth = new Date(now.getFullYear(), now.getMonth(), 1);

            if (expDate < thisMonth) {
                valid = false;
                msg = 'Card is expired';
            }
        }

        if (!valid) {
            e.target.classList.add('error');
            if (errorMsg) errorMsg.textContent = msg;
        } else {
            e.target.classList.remove('error');
            if (errorMsg) errorMsg.textContent = '';
        }
    });
}
    </script>        


</html></body>

<?php
session_start();
require_once 'includes/db_connection.php';

// Check if the cart exists
$cart_empty = true;

// This will be used by JavaScript to check cart contents
$check_cart = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Tipuno Barbershop</title>
        <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/payment.css">
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>Checkout</h1>
            <p>Complete your purchase</p>
        </div>
    </section>

    <!-- Payment Section -->
    <section class="payment-section">
        <div class="container">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    There was a problem with your order. Please try again or contact customer support.
                </div>
            <?php endif; ?>
            
            <div id="checkout-loading" class="checkout-loading">
                <div class="loading-spinner"></div>
                <p>Loading your cart items...</p>
            </div>
            
            <div id="checkout-empty" class="checkout-empty" style="display: none;">
                <div class="empty-checkout-message">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>There are no items in your cart. Add some products before proceeding to checkout.</p>
                    <a href="shop.php" class="btn btn-primary">Return to Shop</a>
                </div>
            </div>
            
            <div class="payment-container" id="payment-container" style="display: none;">
                <div class="payment-form">
                    <form id="checkout-form" action="process_order.php" method="post">
                        <!-- Shipping Information -->
                        <div class="form-section">
                            <h3><i class="fas fa-shipping-fast"></i> Shipping Information</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first-name">First Name</label>
                                    <input type="text" id="first-name" name="first_name" required
                                        value="<?= isset($_SESSION['user']) ? htmlspecialchars(explode(' ', $_SESSION['user']['name'])[0]) : '' ?>">
                                </div>
                                <div class="form-group">
                                    <label for="last-name">Last Name</label>
                                    <input type="text" id="last-name" name="last_name" required
                                        value="<?= isset($_SESSION['user']) ? htmlspecialchars(substr(strstr($_SESSION['user']['name'], ' '), 1)) : '' ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" id="city" name="city" required>
                                </div>
                                <div class="form-group">
                                    <label for="zip">Postal Code</label>
                                    <input type="text" id="zip" name="zip" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <select id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="USA">United States</option>
                                    <option value="CA">Canada</option>
                                    <option value="UK">United Kingdom</option>
                                    <option value="AU">Australia</option>
                                         <option value="AU">Phillipines</option>
                                    <option value="IN">India</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div class="form-section">
                            <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                            <div class="form-group">
                                <label for="card-name">Name on Card</label>
                                <input type="text" id="card-name" name="card_name" required>
                            </div>
                            <div class="form-group">
                                <label for="card-number">Card Number</label>
                                <input type="text" id="card-number" name="card_number" placeholder="XXXX XXXX XXXX XXXX" required
                                       maxlength="19" onkeyup="formatCardNumber(this)">
                                <div class="credit-card-icons">
                                    <i class="fab fa-cc-visa"></i>
                                    <i class="fab fa-cc-mastercard"></i>
                                    <i class="fab fa-cc-amex"></i>
                                    <i class="fab fa-cc-discover"></i>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="card-expiry">Expiration Date</label>
                                    <input type="text" id="card-expiry" name="card_expiry" placeholder="MM/YY" maxlength="5" onkeyup="formatExpiry(this)" required>
                                </div>
                                <div class="form-group">
                                    <label for="card-cvv">CVV</label>
                                    <input type="text" id="card-cvv" name="card_cvv" placeholder="123" maxlength="4" required>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="order_items" id="order-items-input">
                        <input type="hidden" name="order_total" id="order-total-input">
                        
                        <div class="checkout-actions">
                            <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
                            <button type="submit" class="btn btn-primary place-order-btn" id="place-order-btn">
                                <i class="fas fa-lock"></i> Place Order
                            </button>
                        </div>
                    </form>
                </div>

                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="order-items" id="order-items">
                        <!-- Order items will be populated by JavaScript -->
                        <div class="loading-order-items">
                            <div class="spinner-small"></div>
                            <p>Loading items...</p>
                        </div>
                    </div>
                    <div class="order-totals">
                        <div class="order-subtotal">
                            <span>Subtotal:</span>
                            <span id="order-subtotal">$0.00</span>
                        </div>
                        <div class="order-shipping">
                            <span>Shipping:</span>
                            <span id="order-shipping">$5.00</span>
                        </div>
                        <div class="order-total">
                            <span>Total:</span>
                            <span id="order-total">$0.00</span>
                        </div>
                    </div>
                    <div class="promo-code">
                        <input type="text" placeholder="Enter promo code" id="promo-code">
                        <button id="apply-promo">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Payment page loaded");
            
            // First try localStorage
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            console.log("Initial cart from localStorage:", cart);
            
            // Check if cart is in sessionStorage, which takes priority
            const sessionCart = sessionStorage.getItem('cart');
            if (sessionCart) {
                try {
                    const parsedSessionCart = JSON.parse(sessionCart);
                    if (parsedSessionCart && parsedSessionCart.length > 0) {
                        console.log("Using cart from sessionStorage:", parsedSessionCart);
                        cart = parsedSessionCart;
                    }
                } catch (e) {
                    console.error("Error parsing session cart:", e);
                }
            }
            
            // Debug cart contents
            console.log("Final cart data:", cart);
            
            // Display appropriate UI based on cart content
            const checkoutLoading = document.getElementById('checkout-loading');
            const checkoutEmpty = document.getElementById('checkout-empty');
            const paymentContainer = document.getElementById('payment-container');
            
            setTimeout(() => {
                checkoutLoading.style.display = 'none';
                
                if (!cart || cart.length === 0) {
                    console.log("Cart is empty, showing empty state");
                    checkoutEmpty.style.display = 'block';
                } else {
                    console.log("Cart has items, showing payment form");
                    paymentContainer.style.display = 'block';
                    populateOrderSummary(cart);
                    
                    // Save cart to PHP session for backup
                    try {
                        fetch('save_cart_session.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ cart: cart })
                        })
                        .then(response => response.json())
                        .then(data => console.log('Cart saved to session:', data))
                        .catch(error => console.error('Error saving cart to session:', error));
                    } catch (e) {
                        console.error("Error saving cart to session:", e);
                    }
                }
            }, 500);
            
            function populateOrderSummary(cart) {
                const orderItemsContainer = document.getElementById('order-items');
                const orderSubtotalElement = document.getElementById('order-subtotal');
                const orderShippingElement = document.getElementById('order-shipping');
                const orderTotalElement = document.getElementById('order-total');
                const orderItemsInput = document.getElementById('order-items-input');
                const orderTotalInput = document.getElementById('order-total-input');
                
                if (!orderItemsContainer || !orderSubtotalElement) {
                    console.error("Required elements not found");
                    return;
                }
                
                // Calculate shipping cost
                const shippingCost = 5.00;
                
                // Populate order items
                let subtotal = 0;
                orderItemsContainer.innerHTML = '';
                
                console.log("Populating order summary with items:", cart);
                
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    subtotal += itemTotal;
                    
                    const itemElement = document.createElement('div');
                    itemElement.className = 'order-item';
                    itemElement.innerHTML = `
                        <div class="order-item-image">
                            <img src="${item.image || 'images/product-placeholder.jpg'}" alt="${item.name}">
                        </div>
                        <div class="order-item-details">
                            <h4>${item.name}</h4>
                            <div class="order-item-meta">
                                <span class="order-item-price">$${item.price.toFixed(2)} × ${item.quantity}</span>
                                <span class="order-item-total">$${itemTotal.toFixed(2)}</span>
                            </div>
                        </div>
                    `;
                    orderItemsContainer.appendChild(itemElement);
                });
                
                // Display subtotal
                orderSubtotalElement.textContent = `$${subtotal.toFixed(2)}`;
                
                // Display shipping cost
                if (orderShippingElement) {
                    orderShippingElement.textContent = `$${shippingCost.toFixed(2)}`;
                }
                
                // Calculate and display total
                const total = subtotal + shippingCost;
                if (orderTotalElement) {
                    orderTotalElement.textContent = `$${total.toFixed(2)}`;
                }
                
                // Populate hidden inputs for form submission
                if (orderItemsInput) {
                    const jsonCart = JSON.stringify(cart);
                    orderItemsInput.value = jsonCart;
                    console.log("Set order_items input value:", jsonCart);
                }
                
                if (orderTotalInput) {
                    orderTotalInput.value = total.toFixed(2);
                    console.log("Set order_total input value:", total.toFixed(2));
                }
            }
            
            // Make sure the form has needed data when submitted
            const checkoutForm = document.getElementById('checkout-form');
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e) {
                    console.log("Form submission started");
                    
                    // Validate that we have cart data
                    const orderItemsInput = document.getElementById('order-items-input');
                    
                    if (!orderItemsInput || !orderItemsInput.value) {
                        console.log("No cart data found, checking localStorage");
                        let formCart = JSON.parse(localStorage.getItem('cart') || '[]');
                        
                        if (formCart.length === 0) {
                            console.log("Cart is empty, preventing submission");
                            e.preventDefault();
                            alert('Your cart is empty. Please add products before checkout.');
                            window.location.href = 'shop.php';
                            return false;
                        }
                        
                        // Update hidden input
                        orderItemsInput.value = JSON.stringify(formCart);
                        console.log("Updated order items from localStorage:", formCart);
                    }
                    
                    // Show processing state
                    const orderButton = document.getElementById('place-order-btn');
                    if (orderButton) {
                        orderButton.disabled = true;
                        orderButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    }
                    
                    console.log("Form submission proceeding with data:", orderItemsInput.value);
                });
            }
            
            // Card number formatting
            window.formatCardNumber = function(input) {
                let value = input.value.replace(/\D/g, '');
                let formattedValue = '';
                
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                
                input.value = formattedValue;
            };
            
            // Expiry date formatting
            window.formatExpiry = function(input) {
                let value = input.value.replace(/\D/g, '');
                
                if (value.length > 2) {
                    input.value = value.slice(0, 2) + '/' + value.slice(2);
                } else {
                    input.value = value;
                }
            };
            
            // Handle promo code application
            const applyPromoButton = document.getElementById('apply-promo');
            if (applyPromoButton) {
                applyPromoButton.addEventListener('click', function() {
                    const promoCode = document.getElementById('promo-code').value.trim().toUpperCase();
                    const subtotalText = document.getElementById('order-subtotal').textContent;
                    const subtotal = parseFloat(subtotalText.replace('$', ''));
                    const shippingCost = 5.00;
                    
                    // Check for valid promo code
                    if (promoCode === 'WELCOME10') {
                        const discount = subtotal * 0.1; // 10% discount
                        const newTotal = (subtotal - discount) + shippingCost;
                        
                        // Add discount row to order totals
                        const orderTotals = document.querySelector('.order-totals');
                        
                        // Check if discount is already applied
                        if (!document.querySelector('.order-discount')) {
                            const discountElement = document.createElement('div');
                            discountElement.className = 'order-discount';
                            discountElement.innerHTML = `
                                <span>Discount (10%):</span>
                                <span>-$${discount.toFixed(2)}</span>
                            `;
                            
                            // Insert before the total
                            document.querySelector('.order-total').insertAdjacentElement('beforebegin', discountElement);
                        }
                        
                        // Update total
                        document.getElementById('order-total').textContent = `$${newTotal.toFixed(2)}`;
                        document.getElementById('order-total-input').value = newTotal.toFixed(2);
                        
                        // Disable the promo code field and button
                        this.disabled = true;
                        document.getElementById('promo-code').disabled = true;
                        
                        // Visual feedback
                        document.getElementById('promo-code').style.backgroundColor = '#e6f7e6';
                        document.getElementById('promo-code').style.borderColor = '#28a745';
                        
                        // Show success message
                        alert('Promo code "WELCOME10" applied successfully! 10% discount added.');
                    } else {
                        // Invalid promo code
                        document.getElementById('promo-code').style.backgroundColor = '#fce6e6';
                        document.getElementById('promo-code').style.borderColor = '#dc3545';
                        alert('Invalid promo code. Please try again.');
                        
                        // Reset after a moment
                        setTimeout(() => {
                            document.getElementById('promo-code').style.backgroundColor = '';
                            document.getElementById('promo-code').style.borderColor = '';
                        }, 1500);
                    }
                });
            }
        });
    </script>
</body>
</html>

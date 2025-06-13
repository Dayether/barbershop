/**
 * Professional Checkout Experience
 * Handles all checkout flow and cart management
 */

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const checkoutForm = document.getElementById('checkout-form');
    const orderSummaryItems = document.getElementById('order-summary-items');
    const subtotalDisplay = document.getElementById('subtotal-display');
    const taxDisplay = document.getElementById('tax-display');
    const totalDisplay = document.getElementById('total-display');
    const shippingDisplay = document.getElementById('shipping-display');
    const finalTotalDisplay = document.getElementById('final-total');
    
    // Navigation between checkout steps
    const toPaymentBtn = document.getElementById('to-payment');
    const toReviewBtn = document.getElementById('to-review');
    const backToShippingBtn = document.getElementById('back-to-shipping');
    const backToPaymentBtn = document.getElementById('back-to-payment');
    
    const shippingSection = document.getElementById('shipping-section');
    const paymentSection = document.getElementById('payment-section');
    const reviewSection = document.getElementById('review-section');
    
    const shippingStep = document.getElementById('shipping-step');
    const paymentStep = document.getElementById('payment-step');
    const reviewStep = document.getElementById('review-step');
    
    // Constants
    const TAX_RATE = 0.07; // 7% tax
    const SHIPPING_COST = 0; // Free shipping
    
    // Format card number with spaces
    const cardNumberInput = document.getElementById('card_number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/\D/g, '');
            let newValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    newValue += ' ';
                }
                newValue += value[i];
            }
            
            e.target.value = newValue;
        });
    }
    
    // Format expiry date with slash
    const cardExpiryInput = document.getElementById('card_expiry');
    if (cardExpiryInput) {
        cardExpiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            
            e.target.value = value;
        });
    }
    
    // Validate required fields in a section
    function validateSection(section) {
        const requiredFields = section.querySelectorAll('input[required], select[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
                
                // Add shake animation
                field.classList.add('shake-error');
                setTimeout(() => {
                    field.classList.remove('shake-error');
                }, 500);
            } else {
                field.classList.remove('error');
            }
        });
        
        return isValid;
    }
    
    // Navigation to payment section
    if (toPaymentBtn) {
        toPaymentBtn.addEventListener('click', function() {
            if (validateSection(shippingSection)) {
                // Smooth transition
                shippingSection.style.opacity = '0';
                setTimeout(() => {
                    shippingSection.style.display = 'none';
                    paymentSection.style.display = 'block';
                    
                    setTimeout(() => {
                        paymentSection.style.opacity = '1';
                    }, 50);
                }, 300);
                
                // Update progress steps
                shippingStep.classList.remove('active');
                shippingStep.classList.add('completed');
                paymentStep.classList.add('active');
                
                // Scroll to top smoothly
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // Navigation to review section
    if (toReviewBtn) {
        toReviewBtn.addEventListener('click', function() {
            if (validateSection(paymentSection)) {
                // Generate review content
                const firstName = document.getElementById('first_name').value;
                const lastName = document.getElementById('last_name').value;
                const email = document.getElementById('email').value;
                const address = document.getElementById('address').value;
                const city = document.getElementById('city').value;
                const state = document.getElementById('state').value;
                const zip = document.getElementById('zip').value;
                const phone = document.getElementById('phone').value;
                
                const cardName = document.getElementById('card_name').value;
                const cardNumberValue = document.getElementById('card_number').value;
                const lastFour = cardNumberValue.slice(-4);
                
                // Format shipping address
                document.getElementById('review-shipping-address').innerHTML = `
                    <p><strong>${firstName} ${lastName}</strong></p>
                    <p>${address}</p>
                    <p>${city}, ${state} ${zip}</p>
                    <p>Phone: ${phone}</p>
                    <p>Email: ${email}</p>
                `;
                
                // Format payment method
                document.getElementById('review-payment-method').innerHTML = `
                    <p><i class="fas fa-credit-card"></i> <strong>Credit card ending in ${lastFour}</strong></p>
                    <p>Cardholder: ${cardName}</p>
                    <p>Billing address: Same as shipping</p>
                `;
                
                // Smooth transition
                paymentSection.style.opacity = '0';
                setTimeout(() => {
                    paymentSection.style.display = 'none';
                    reviewSection.style.display = 'block';
                    
                    setTimeout(() => {
                        reviewSection.style.opacity = '1';
                    }, 50);
                }, 300);
                
                // Update progress steps
                paymentStep.classList.remove('active');
                paymentStep.classList.add('completed');
                reviewStep.classList.add('active');
                
                // Scroll to top smoothly
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // Navigation back to shipping
    if (backToShippingBtn) {
        backToShippingBtn.addEventListener('click', function() {
            // Smooth transition
            paymentSection.style.opacity = '0';
            setTimeout(() => {
                paymentSection.style.display = 'none';
                shippingSection.style.display = 'block';
                
                setTimeout(() => {
                    shippingSection.style.opacity = '1';
                }, 50);
            }, 300);
            
            // Update progress steps
            shippingStep.classList.add('active');
            shippingStep.classList.remove('completed');
            paymentStep.classList.remove('active');
            
            // Scroll to top smoothly
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Navigation back to payment
    if (backToPaymentBtn) {
        backToPaymentBtn.addEventListener('click', function() {
            // Smooth transition
            reviewSection.style.opacity = '0';
            setTimeout(() => {
                reviewSection.style.display = 'none';
                paymentSection.style.display = 'block';
                
                setTimeout(() => {
                    paymentSection.style.opacity = '1';
                }, 50);
            }, 300);
            
            // Update progress steps
            paymentStep.classList.add('active');
            paymentStep.classList.remove('completed');
            reviewStep.classList.remove('active');
            
            // Scroll to top smoothly
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Load cart from sessionStorage
    let cart = [];
    try {
        const storedCart = sessionStorage.getItem('cart');
        if (storedCart) {
            cart = JSON.parse(storedCart);
            console.log('Loaded cart:', cart); // <-- Add this line
        }
    } catch (e) {
        cart = [];
    }

    function renderOrderSummary(cart) {
        if (!orderSummaryItems) return;
        if (cart.length === 0) {
            orderSummaryItems.innerHTML = `
                <div class="empty-cart-message">
                    <div class="empty-icon"><i class="fas fa-shopping-cart"></i></div>
                    <p>Your cart is now empty.</p>
                    <a href="shop.php" class="btn-continue-shopping">Continue Shopping</a>
                </div>
            `;
            recalculateTotals(cart);
            return;
        }
        orderSummaryItems.innerHTML = '';
        cart.forEach((item, idx) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'cart-item';
            itemDiv.innerHTML = `
                <div class="cart-item-info">
                    <span class="cart-item-name">${item.name}</span>
                    <span class="cart-item-price">$${item.price.toFixed(2)}</span>
                </div>
                <div class="cart-item-controls">
                    <button class="decrease-qty" data-index="${idx}">-</button>
                    <span class="cart-item-qty">${item.quantity}</span>
                    <button class="increase-qty" data-index="${idx}">+</button>
                    <button class="remove-item-btn" data-index="${idx}"><i class="fas fa-trash"></i></button>
                    <span class="item-subtotal">$${(item.price * item.quantity).toFixed(2)}</span>
                </div>
            `;
            orderSummaryItems.appendChild(itemDiv);
        });
        recalculateTotals(cart);
        attachCartHandlers();
    }

    function attachCartHandlers() {
        orderSummaryItems.querySelectorAll('.increase-qty').forEach(btn => {
            btn.onclick = function() {
                const idx = parseInt(this.getAttribute('data-index'));
                cart[idx].quantity += 1;
                sessionStorage.setItem('cart', JSON.stringify(cart));
                renderOrderSummary(cart);
            };
        });
        orderSummaryItems.querySelectorAll('.decrease-qty').forEach(btn => {
            btn.onclick = function() {
                const idx = parseInt(this.getAttribute('data-index'));
                if (cart[idx].quantity > 1) {
                    cart[idx].quantity -= 1;
                    sessionStorage.setItem('cart', JSON.stringify(cart));
                    renderOrderSummary(cart);
                }
            };
        });
        orderSummaryItems.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.onclick = function() {
                const idx = parseInt(this.getAttribute('data-index'));
                cart.splice(idx, 1);
                sessionStorage.setItem('cart', JSON.stringify(cart));
                renderOrderSummary(cart);
            };
        });
    }

    function recalculateTotals(cart) {
        let subtotal = 0;
        let itemCount = 0;
        cart.forEach(item => {
            subtotal += item.price * item.quantity;
            itemCount += item.quantity;
        });
        const tax = subtotal * TAX_RATE;
        const total = subtotal + tax + SHIPPING_COST;

        if (subtotalDisplay) subtotalDisplay.textContent = '$' + subtotal.toFixed(2);
        if (taxDisplay) taxDisplay.textContent = '$' + tax.toFixed(2);
        if (totalDisplay) totalDisplay.textContent = '$' + total.toFixed(2);
        if (finalTotalDisplay) finalTotalDisplay.textContent = '$' + total.toFixed(2);

        if (document.querySelector('input[name="subtotal"]')) document.querySelector('input[name="subtotal"]').value = subtotal;
        if (document.querySelector('input[name="tax"]')) document.querySelector('input[name="tax"]').value = tax;
        if (document.querySelector('input[name="total"]')) document.querySelector('input[name="total"]').value = total;

        const itemCountDisplay = document.querySelector('.item-count');
        if (itemCountDisplay) {
            itemCountDisplay.textContent = itemCount + (itemCount !== 1 ? ' items' : ' item');
        }
    }

    // Render cart and attach handlers on load
    if (orderSummaryItems) {
        renderOrderSummary(cart);
    }

    // Form submission
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            // Prevent order if cart is empty
            if (!cart || cart.length === 0) {
                e.preventDefault();
                if (typeof iziToast !== 'undefined') {
                    iziToast.warning({
                        title: 'Cart Empty',
                        message: 'Please add items to your cart before placing an order.',
                        icon: 'fas fa-shopping-cart'
                    });
                } else {
                    alert('Please add items to your cart before placing an order.');
                }
                return false;
            }
            // Check terms agreement
            const termsCheckbox = document.getElementById('terms');
            if (!termsCheckbox.checked) {
                e.preventDefault();
                
                // Show error message
                const termsError = document.createElement('div');
                termsError.className = 'terms-error';
                termsError.textContent = 'Please accept the Terms & Conditions';
                
                // Remove any existing error
                const existingError = document.querySelector('.terms-error');
                if (existingError) existingError.remove();
                
                // Add new error
                document.querySelector('.terms-checkbox').appendChild(termsError);
                
                // Highlight checkbox
                termsCheckbox.classList.add('error');
                
                // Shake animation
                document.querySelector('.terms-checkbox label').classList.add('shake-error');
                setTimeout(() => {
                    document.querySelector('.terms-checkbox label').classList.remove('shake-error');
                }, 500);
                
                return;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...';
            
            // Add overlay for visual feedback
            const processingOverlay = document.createElement('div');
            processingOverlay.className = 'processing-overlay';
            processingOverlay.innerHTML = `
                <div class="processing-content">
                    <div class="spinner"></div>
                    <p>Processing your order...</p>
                </div>
            `;
            document.body.appendChild(processingOverlay);
            
            // Allow form submission
            return true;
        });
    }
    
    // Add initial visual transitions for form sections
    if (shippingSection) shippingSection.style.opacity = '1';
    if (paymentSection) paymentSection.style.opacity = '0';
    if (reviewSection) reviewSection.style.opacity = '0';
});


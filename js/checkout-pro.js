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
    
    // Cart management
    if (orderSummaryItems) {
        // Get cart data
        let cart = [];
        
        try {
            const cartInput = document.querySelector('input[name="order_items"]');
            if (cartInput && cartInput.value) {
                cart = JSON.parse(cartInput.value);
            }
        } catch (e) {
            console.error('Error parsing cart data:', e);
        }
        
        // Update cart in session and localStorage
        function updateCart(cart) {
            // Update localStorage
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Update session storage
            if (typeof sessionStorage !== 'undefined') {
                sessionStorage.setItem('cart', JSON.stringify(cart));
            }
            
            // Update server session
            fetch('includes/save_cart_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ cart: cart })
            }).catch(error => console.error('Error saving cart:', error));
            
            // Update hidden input
            document.querySelector('input[name="order_items"]').value = JSON.stringify(cart);
        }
        
        // Recalculate totals
        function recalculateTotals(cart) {
            let subtotal = 0;
            let itemCount = 0;
            
            cart.forEach(item => {
                subtotal += item.price * item.quantity;
                itemCount += item.quantity;
            });
            
            const tax = subtotal * TAX_RATE;
            const total = subtotal + tax + SHIPPING_COST;
            
            // Update displays
            if (subtotalDisplay) subtotalDisplay.textContent = '$' + subtotal.toFixed(2);
            if (taxDisplay) taxDisplay.textContent = '$' + tax.toFixed(2);
            if (totalDisplay) totalDisplay.textContent = '$' + total.toFixed(2);
            if (finalTotalDisplay) finalTotalDisplay.textContent = '$' + total.toFixed(2);
            
            // Update hidden inputs
            document.querySelector('input[name="subtotal"]').value = subtotal;
            document.querySelector('input[name="tax"]').value = tax;
            document.querySelector('input[name="total"]').value = total;
            
            // Update item count
            const itemCountDisplay = document.querySelector('.item-count');
            if (itemCountDisplay) {
                itemCountDisplay.textContent = itemCount + (itemCount !== 1 ? ' items' : ' item');
            }
            
            return { subtotal, tax, total, itemCount };
        }
        
        // Handle quantity and removal events
        orderSummaryItems.addEventListener('click', function(e) {
            // Decrease quantity
            if (e.target.classList.contains('decrease-qty')) {
                const itemId = e.target.getAttribute('data-id');
                const itemIndex = cart.findIndex(item => item.id == itemId);
                
                if (itemIndex !== -1 && cart[itemIndex].quantity > 1) {
                    cart[itemIndex].quantity--;
                    
                    // Update display
                    const qtyElement = e.target.nextElementSibling;
                    qtyElement.textContent = cart[itemIndex].quantity;
                    
                    const itemElement = e.target.closest('.cart-item');
                    const subtotalElement = itemElement.querySelector('.item-subtotal');
                    subtotalElement.textContent = '$' + (cart[itemIndex].price * cart[itemIndex].quantity).toFixed(2);
                    
                    // Pulse animation for feedback
                    subtotalElement.classList.add('price-update');
                    setTimeout(() => {
                        subtotalElement.classList.remove('price-update');
                    }, 700);
                    
                    // Update totals
                    recalculateTotals(cart);
                    updateCart(cart);
                }
            }
            
            // Increase quantity
            if (e.target.classList.contains('increase-qty')) {
                const itemId = e.target.getAttribute('data-id');
                const itemIndex = cart.findIndex(item => item.id == itemId);
                
                if (itemIndex !== -1) {
                    cart[itemIndex].quantity++;
                    
                    // Update display
                    const qtyElement = e.target.previousElementSibling;
                    qtyElement.textContent = cart[itemIndex].quantity;
                    
                    const itemElement = e.target.closest('.cart-item');
                    const subtotalElement = itemElement.querySelector('.item-subtotal');
                    subtotalElement.textContent = '$' + (cart[itemIndex].price * cart[itemIndex].quantity).toFixed(2);
                    
                    // Pulse animation for feedback
                    subtotalElement.classList.add('price-update');
                    setTimeout(() => {
                        subtotalElement.classList.remove('price-update');
                    }, 700);
                    
                    // Update totals
                    recalculateTotals(cart);
                    updateCart(cart);
                }
            }
            
            // Remove item
            if (e.target.classList.contains('remove-item-btn') || e.target.closest('.remove-item-btn')) {
                const button = e.target.classList.contains('remove-item-btn') ? 
                    e.target : e.target.closest('.remove-item-btn');
                const itemId = button.getAttribute('data-id');
                const itemIndex = cart.findIndex(item => item.id == itemId);
                
                if (itemIndex !== -1) {
                    cart.splice(itemIndex, 1);
                    
                    // Remove from display with animation
                    const itemElement = button.closest('.cart-item');
                    itemElement.classList.add('removing');
                    
                    setTimeout(() => {
                        itemElement.remove();
                        
                        // Check if cart is now empty
                        if (cart.length === 0) {
                            // Create a custom message element
                            const emptyMessage = document.createElement('div');
                            emptyMessage.className = 'empty-cart-message';
                            emptyMessage.innerHTML = `
                                <div class="empty-icon"><i class="fas fa-shopping-cart"></i></div>
                                <p>Your cart is now empty.</p>
                                <a href="shop.php" class="btn-continue-shopping">Continue Shopping</a>
                            `;
                            
                            // Clear item area and add message
                            orderSummaryItems.innerHTML = '';
                            orderSummaryItems.appendChild(emptyMessage);
                            
                            // Redirect after delay
                            setTimeout(() => {
                                window.location.href = 'shop.php';
                            }, 3000);
                            
                            return;
                        }
                        
                        // Update totals
                        recalculateTotals(cart);
                        updateCart(cart);
                    }, 300);
                }
            }
        });
    }
    
    // Form submission
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
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

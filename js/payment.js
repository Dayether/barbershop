/**
 * Payment page functionality for Tipuno Barbershop
 * Handles cart data processing and form submission
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Payment page initialized');
    
    // Retrieve cart data from both localStorage and sessionStorage
    let cart;
    
    // First try to get cart from sessionStorage (higher priority)
    if (sessionStorage.getItem('cart')) {
        cart = JSON.parse(sessionStorage.getItem('cart'));
        console.log("Cart data loaded from sessionStorage:", cart);
    } 
    // Fall back to localStorage if not in sessionStorage
    else {
        cart = JSON.parse(localStorage.getItem('cart') || '[]');
        console.log("Cart data loaded from localStorage:", cart);
        
        // Store in sessionStorage for consistency
        if (cart.length > 0) {
            sessionStorage.setItem('cart', JSON.stringify(cart));
        }
    }
    
    // Send cart data to server for PHP session storage as backup
    sendCartToServer(cart);
    
    // Show appropriate UI based on cart status
    showCheckoutUI(cart);
    
    // Handle form submission
    setupFormSubmission(cart);
    
    // Setup promo code functionality
    setupPromoCode();
});

/**
 * Send cart data to server for PHP session storage
 */
function sendCartToServer(cart) {
    fetch('save_cart_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ cart: cart })
    })
    .then(response => response.json())
    .then(data => console.log('Cart saved to PHP session:', data))
    .catch(error => console.error('Error saving cart to PHP session:', error));
}

/**
 * Show appropriate UI based on cart contents
 */
function showCheckoutUI(cart) {
    const checkoutLoading = document.getElementById('checkout-loading');
    const checkoutEmpty = document.getElementById('checkout-empty');
    const paymentContainer = document.getElementById('payment-container');
    
    setTimeout(() => {
        checkoutLoading.style.display = 'none';
        
        if (!cart || cart.length === 0) {
            checkoutEmpty.style.display = 'block';
            console.log("Cart is empty, showing empty state");
        } else {
            paymentContainer.style.display = 'block';
            populateOrderSummary(cart);
            console.log("Cart has items, showing payment form");
        }
    }, 500);
}

/**
 * Populate the order summary with cart items
 */
function populateOrderSummary(cart) {
    const orderItemsContainer = document.getElementById('order-items');
    const orderSubtotalElement = document.getElementById('order-subtotal');
    const orderShippingElement = document.getElementById('order-shipping');
    const orderTotalElement = document.getElementById('order-total');
    const orderItemsInput = document.getElementById('order-items-input');
    const orderTotalInput = document.getElementById('order-total-input');
    
    // Calculate shipping cost
    const shippingCost = 5.00;
    
    // Populate order items
    let subtotal = 0;
    orderItemsContainer.innerHTML = '';
    
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
    orderShippingElement.textContent = `$${shippingCost.toFixed(2)}`;
    
    // Calculate and display total
    const total = subtotal + shippingCost;
    orderTotalElement.textContent = `$${total.toFixed(2)}`;
    
    // Populate hidden inputs for form submission
    orderItemsInput.value = JSON.stringify(cart);
    orderTotalInput.value = total.toFixed(2);
    
    console.log("Order summary populated with", cart.length, "items");
    console.log("Order total:", total.toFixed(2));
}

/**
 * Setup form submission handler
 */
function setupFormSubmission(cart) {
    const checkoutForm = document.getElementById('checkout-form');
    if (!checkoutForm) return;
    
    checkoutForm.addEventListener('submit', function(e) {
        // Prevent form submission if cart is empty
        if (!cart || cart.length === 0) {
            e.preventDefault();
            alert('Your cart is empty! Please add products before checking out.');
            window.location.href = 'shop.php';
            return false;
        }
        
        // Double check that the hidden inputs are populated
        const orderItemsInput = document.getElementById('order-items-input');
        const orderTotalInput = document.getElementById('order-total-input');
        
        if (!orderItemsInput.value) {
            console.log("Order items input was empty, repopulating...");
            orderItemsInput.value = JSON.stringify(cart);
        }
        
        if (!orderTotalInput.value) {
            console.log("Order total input was empty, calculating...");
            const subtotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            const shippingCost = 5.00;
            const total = subtotal + shippingCost;
            orderTotalInput.value = total.toFixed(2);
        }
        
        console.log("Form submission - order items:", orderItemsInput.value);
        console.log("Form submission - order total:", orderTotalInput.value);
        
        // Show processing state
        const orderButton = document.getElementById('place-order-btn');
        orderButton.disabled = true;
        orderButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    });
}

/**
 * Setup promo code functionality
 */
function setupPromoCode() {
    const applyPromoButton = document.getElementById('apply-promo');
    if (!applyPromoButton) return;
    
    applyPromoButton.addEventListener('click', function() {
        const promoCode = document.getElementById('promo-code').value.trim().toUpperCase();
        const subtotalText = document.getElementById('order-subtotal').textContent;
        const subtotal = parseFloat(subtotalText.replace('$', ''));
        const shippingCost = 5.00;
        
        // Check for valid promo code
        if (promoCode === 'WELCOME10') {
            const discount = subtotal * 0.1; // 10% discount
            const newTotal = (subtotal - discount) + shippingCost;
            
            // Add discount row if it doesn't exist
            if (!document.querySelector('.order-discount')) {
                const discountElement = document.createElement('div');
                discountElement.className = 'order-discount';
                discountElement.innerHTML = `
                    <span>Discount (10%):</span>
                    <span>-$${discount.toFixed(2)}</span>
                `;
                
                document.querySelector('.order-total')
                    .insertAdjacentElement('beforebegin', discountElement);
            }
            
            // Update total
            document.getElementById('order-total').textContent = `$${newTotal.toFixed(2)}`;
            document.getElementById('order-total-input').value = newTotal.toFixed(2);
            
            // Disable promo code field and button
            this.disabled = true;
            document.getElementById('promo-code').disabled = true;
            
            // Visual feedback
            document.getElementById('promo-code').style.backgroundColor = '#e6f7e6';
            document.getElementById('promo-code').style.borderColor = '#28a745';
            
            alert('Promo code "WELCOME10" applied successfully! 10% discount added.');
        } else {
            // Invalid promo code
            document.getElementById('promo-code').style.backgroundColor = '#fce6e6';
            document.getElementById('promo-code').style.borderColor = '#dc3545';
            alert('Invalid promo code. Please try again.');
            
            setTimeout(() => {
                document.getElementById('promo-code').style.backgroundColor = '';
                document.getElementById('promo-code').style.borderColor = '';
            }, 1500);
        }
    });
}

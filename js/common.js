document.addEventListener('DOMContentLoaded', function() {
    // Handle navigation
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }
    
    // Initialize profile dropdown properly
    initProfileDropdown();
    
    // Initialize global cart functionality if on a shop page
    const pageType = document.body.getAttribute('data-page-type') || '';
    if (pageType === 'shop' || pageType === 'product' || pageType === 'checkout') {
        setupCartFunctionality();
    } else {
        // For non-shop pages, hide the cart elements if they exist
        const cartSidebar = document.getElementById('cart-sidebar');
        const cartOverlay = document.getElementById('cart-overlay');
        if (cartSidebar) cartSidebar.style.display = 'none';
        if (cartOverlay) cartOverlay.style.display = 'none';
        
        // Just update cart count badge
        updateCartBadge();
    }

    setupCheckoutButtons();
});

/**
 * Initialize profile dropdown with proper event handlers and accessibility
 */
function initProfileDropdown() {
    const profileToggle = document.getElementById('profile-toggle');
    const profilePanel = document.getElementById('profile-panel');
    
    if (profileToggle && profilePanel) {
        console.log("Initializing profile dropdown");
        
        // Toggle dropdown on button click
        profileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle active class on panel
            profilePanel.classList.toggle('active');
            
            // Update ARIA attributes
            const isExpanded = profilePanel.classList.contains('active');
            this.setAttribute('aria-expanded', isExpanded);
        });
        
        // Close dropdown when clicking elsewhere
        document.addEventListener('click', function(e) {
            if (profilePanel.classList.contains('active') && 
                !profilePanel.contains(e.target) && 
                e.target !== profileToggle) {
                profilePanel.classList.remove('active');
                profileToggle.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Add keyboard accessibility (close with Escape key)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && profilePanel.classList.contains('active')) {
                profilePanel.classList.remove('active');
                profileToggle.setAttribute('aria-expanded', 'false');
                profileToggle.focus(); // Return focus to toggle for better a11y
            }
        });
        
        // Make the dropdown accessible via keyboard navigation
        profilePanel.querySelectorAll('a').forEach(link => {
            link.addEventListener('keydown', function(e) {
                if (e.key === 'Tab' && !e.shiftKey && 
                    this === profilePanel.querySelector('a:last-child')) {
                    profilePanel.classList.remove('active');
                    profileToggle.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }
}

/**
 * Update cart badge count from localStorage
 */
function updateCartBadge() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
    
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        if (element) {
            // Add animation if count is changing
            const currentCount = parseInt(element.textContent) || 0;
            if (currentCount !== totalItems) {
                element.classList.add('bounce');
                setTimeout(() => element.classList.remove('bounce'), 500);
            }
            element.textContent = totalItems;
        }
    });
}

// Global cart functions that should be available everywhere
function setupCartFunctionality() {
    console.log("Setting up cart functionality...");
    
    // Load cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Debug: Log current cart contents
    console.log("Initial cart contents:", cart);
    
    // Update the cart count badge
    updateCartCount(cart);
    
    // Set up cart icon functionality
    const cartIcon = document.getElementById('cart-icon');
    const cartSidebar = document.getElementById('cart-sidebar');
    const cartOverlay = document.getElementById('cart-overlay');
    const closeCart = document.getElementById('close-cart');
    
    if (cartIcon && cartSidebar) {
        // Cart icon click - open cart sidebar
        cartIcon.addEventListener('click', function(e) {
            console.log("Cart icon clicked");
            e.preventDefault();
            cartSidebar.classList.add('active');
            cartOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // This is critical - update cart items when opening the sidebar
            updateCartItems(cart);
        });
        
        // Close cart button click
        if (closeCart) {
            closeCart.addEventListener('click', function() {
                cartSidebar.classList.remove('active');
                cartOverlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        
        // Overlay click (clicking outside the cart)
        if (cartOverlay) {
            cartOverlay.addEventListener('click', function() {
                cartSidebar.classList.remove('active');
                this.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
    }
    
    // Add to cart functionality for all products on current page
    setupAddToCartButtons(cart);
}

// Set up Add to Cart buttons on the page
function setupAddToCartButtons(cart) {
    const addToCartButtons = document.querySelectorAll('.btn-add-to-cart:not([disabled])');
    
    console.log("Found", addToCartButtons.length, "add to cart buttons");
    
    addToCartButtons.forEach(button => {
        // Remove any existing click event listeners to prevent duplicates
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        // Add click event listener to the new button
        newButton.addEventListener('click', function(e) {
            console.log("Add to cart button clicked");
            
            // Get product information
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const price = parseFloat(this.getAttribute('data-price'));
            const image = this.getAttribute('data-image') || 'images/product-placeholder.jpg';
            
            // Load latest cart data
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Check if product is already in cart
            const existingItem = cart.find(item => item.id === id);
            
            if (existingItem) {
                console.log("Updating existing item in cart");
                existingItem.quantity += 1;
            } else {
                console.log("Adding new item to cart:", name);
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    image: image,
                    quantity: 1
                });
            }
            
            // Save cart and update UI
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount(cart);
            
            // Show cart after item is added
            const cartSidebar = document.getElementById('cart-sidebar');
            const cartOverlay = document.getElementById('cart-overlay');
            
            if (cartSidebar && cartOverlay) {
                cartSidebar.classList.add('active');
                cartOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                updateCartItems(cart);
            }
            
            // Visual feedback
            this.classList.add('added');
            setTimeout(() => {
                this.classList.remove('added');
            }, 1000);
            
            // Store in sessionStorage too for checkout
            sessionStorage.setItem('cart', JSON.stringify(cart));
        });
    });
}

// Update the cart counter
function updateCartCount(cart) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
    
    console.log("Updating cart count to:", totalItems);
    
    cartCountElements.forEach(element => {
        // Add bounce animation if count is changing
        const currentCount = parseInt(element.textContent) || 0;
        if (currentCount !== totalItems) {
            element.classList.add('bounce');
            setTimeout(() => {
                element.classList.remove('bounce');
            }, 500);
        }
        
        element.textContent = totalItems;
    });
}

// Update cart items in the sidebar
function updateCartItems(cart) {
    const cartItems = document.getElementById('cart-items');
    const cartTotal = document.getElementById('cart-total');
    const checkoutBtn = document.getElementById('checkout-btn');
    
    if (!cartItems || !cartTotal) return;
    
    // Get cart from localStorage if not provided
    if (!cart) {
        cart = JSON.parse(localStorage.getItem('cart')) || [];
    }
    
    console.log("Updating cart UI with", cart.length, "items");
    
    if (cart.length === 0) {
        // Empty cart
        cartItems.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-bag"></i>
                <p>Your cart is empty</p>
                <a href="shop.php" class="btn btn-secondary btn-sm">Start Shopping</a>
            </div>
        `;
        cartTotal.textContent = '$0.00';
        
        // Disable checkout button
        if (checkoutBtn) {
            checkoutBtn.classList.add('disabled');
            checkoutBtn.setAttribute('href', 'javascript:void(0);');
            checkoutBtn.setAttribute('onclick', "alert('Your cart is empty!'); return false;");
            console.log("Checkout button disabled");
        }
    } else {
        // Cart has items
        let total = 0;
        cartItems.innerHTML = '';
        
        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            
            console.log(`Adding item to cart display: ${item.name} x${item.quantity}`);
            
            const cartItemElement = document.createElement('div');
            cartItemElement.className = 'cart-item';
            cartItemElement.innerHTML = `
                <div class="cart-item-image">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="cart-item-details">
                    <h4>${item.name}</h4>
                    <div class="cart-item-meta">
                        <span class="cart-item-price">$${item.price.toFixed(2)}</span>
                        <div class="cart-item-quantity">
                            <button class="quantity-btn decrease-qty" data-index="${index}">-</button>
                            <span class="quantity">${item.quantity}</span>
                            <button class="quantity-btn increase-qty" data-index="${index}">+</button>
                        </div>
                    </div>
                    <button class="remove-item" data-index="${index}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;
            
            cartItems.appendChild(cartItemElement);
        });
        
        // Update total price
        cartTotal.textContent = '$' + total.toFixed(2);
        
        // Enable checkout button - this is critical
        if (checkoutBtn) {
            checkoutBtn.classList.remove('disabled');
            checkoutBtn.removeAttribute('onclick');
            checkoutBtn.setAttribute('href', 'payment.php');
            
            // Make sure the button is clickable
            checkoutBtn.style.pointerEvents = 'auto';
            checkoutBtn.style.opacity = '1';
            console.log("Checkout button enabled");
            
            // Update cart in sessionStorage when clicking checkout
            checkoutBtn.addEventListener('click', function(e) {
                // Don't add event listener more than once
                if (this.getAttribute('data-event-added')) return;
                
                this.setAttribute('data-event-added', 'true');
                
                // Store cart data in sessionStorage for checkout page
                sessionStorage.setItem('cart', JSON.stringify(cart));
                console.log("Cart data stored in sessionStorage for checkout");
            });
        }
        
        // Add quantity adjustment event listeners
        addQuantityButtonListeners(cart);
    }
}

// Add event listeners to quantity buttons
function addQuantityButtonListeners(cart) {
    // Decrease quantity buttons
    document.querySelectorAll('.decrease-qty').forEach(button => {
        button.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            if (cart[index].quantity > 1) {
                cart[index].quantity--;
                saveAndUpdateCart(cart);
            } else {
                removeCartItem(index, cart);
            }
        });
    });
    
    // Increase quantity buttons
    document.querySelectorAll('.increase-qty').forEach(button => {
        button.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            cart[index].quantity++;
            saveAndUpdateCart(cart);
        });
    });
    
    // Remove item buttons
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            removeCartItem(index, cart);
        });
    });
}

// Remove an item from the cart
function removeCartItem(index, cart) {
    console.log("Removing item at index", index);
    
    // Get the item element
    const button = document.querySelector(`.remove-item[data-index="${index}"]`);
    if (button) {
        const cartItem = button.closest('.cart-item');
        if (cartItem) {
            // Apply removal animation
            cartItem.classList.add('removing');
            
            setTimeout(() => {
                // Remove item from cart array
                cart.splice(index, 1);
                saveAndUpdateCart(cart);
            }, 300);
        } else {
            // No animation, just remove immediately
            cart.splice(index, 1);
            saveAndUpdateCart(cart);
        }
    } else {
        // Button not found, just remove immediately
        cart.splice(index, 1);
        saveAndUpdateCart(cart);
    }
}

// Save cart and update UI
function saveAndUpdateCart(cart) {
    console.log("Saving cart and updating UI");
    localStorage.setItem('cart', JSON.stringify(cart));
    sessionStorage.setItem('cart', JSON.stringify(cart)); // For checkout consistency
    updateCartCount(cart);
    updateCartItems(cart);
}

// Check if cart was cleared after successful order
function checkCartCleared() {
    // Check for cookie indicating cart was cleared
    const getCookie = (name) => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    };
    
    if (getCookie('cart_cleared') === 'true') {
        // Clear the cart data from storage
        localStorage.removeItem('cart');
        sessionStorage.removeItem('cart');
        
        // Update cart UI
        updateCartCount([]);
        
        // Clear the cookie
        document.cookie = "cart_cleared=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        
        console.log("Cart cleared after successful order");
    }
}

// Document ready handler
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart on page load
    initCartFunctionality();
    
    // Fix checkout button if needed
    fixCheckoutButtonState();
});

// Function to fix checkout button state based on cart
function fixCheckoutButtonState() {
    const checkoutBtn = document.getElementById('checkout-btn');
    if (!checkoutBtn) return;
    
    // Get current cart
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    if (cart.length > 0) {
        // If cart has items, ensure checkout button is enabled
        checkoutBtn.classList.remove('disabled');
        checkoutBtn.removeAttribute('onclick');
        checkoutBtn.setAttribute('href', 'payment.php');
        checkoutBtn.style.pointerEvents = 'auto';
        checkoutBtn.style.opacity = '1';
        console.log("Fixed checkout button state - enabled");
    } else {
        // If cart is empty, button should be disabled
        checkoutBtn.classList.add('disabled');
        checkoutBtn.setAttribute('href', 'javascript:void(0);');
        checkoutBtn.setAttribute('onclick', "alert('Your cart is empty!'); return false;");
        console.log("Fixed checkout button state - disabled");
    }
}

// Make updateCartCount available globally even when full cart functionality isn't initialized
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
    
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        if (element) element.textContent = totalItems;
    });
}

// Add this placeholder function if it doesn't exist elsewhere
function checkCartCleared() {
    // Implementation depends on your specific cart clearing logic
}

// Handle checkout button clicks globally
function setupCheckoutButtons() {
    const checkoutButtons = document.querySelectorAll('.notification-checkout, .cart-checkout');
    
    checkoutButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            window.location.href = 'payment.php';
        });
    });
}

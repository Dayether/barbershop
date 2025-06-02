/**
 * Cart management for Tipuno Barbershop
 * Handles cart operations and session management
 */

document.addEventListener('DOMContentLoaded', function() {
    initCartSystem();
    
    // Listen for Add to Cart from any page
    initAddToCartListeners();
    
    // Initialize cart sidebar and toggle functionality
    initCartSidebar();
});

/**
 * Initialize cart system with user session awareness
 */
function initCartSystem() {
    // Check if user is logged in by looking for data-user-id attribute on body
    const userLoggedIn = document.body.hasAttribute('data-user-id');
    const userId = document.body.getAttribute('data-user-id');
    
    // Get cart from localStorage
    const existingCart = localStorage.getItem('tipunoCart');
    const existingCartOwner = localStorage.getItem('tipunoCartOwner');
    
    // If cart doesn't belong to current user or cart exists but user not logged in, clear it
    if ((existingCartOwner && existingCartOwner !== userId) || 
        (existingCart && !userLoggedIn)) {
        console.log('Clearing cart: User session mismatch');
        clearCart();
    }
    
    // If user is logged in, associate cart with user
    if (userLoggedIn && userId) {
        localStorage.setItem('tipunoCartOwner', userId);
    } else {
        // Guest user - no cart persistence
        localStorage.removeItem('tipunoCartOwner');
    }
    
    // Initialize cart UI
    updateCartDisplay();
}

/**
 * Initialize cart sidebar and toggle functionality
 */
function initCartSidebar() {
    // Create cart sidebar if it doesn't exist
    if (!document.getElementById('cart-sidebar')) {
        const cartSidebar = document.createElement('div');
        cartSidebar.id = 'cart-sidebar';
        cartSidebar.className = 'cart-sidebar';
        cartSidebar.innerHTML = `
            <div class="cart-header">
                <h3>Your Cart</h3>
                <button class="close-cart">&times;</button>
            </div>
            <div class="cart-items-container">
                <div class="cart-items"></div>
                <div class="cart-empty">
                    <i class="fas fa-shopping-bag"></i>
                    <p>Your cart is empty</p>
                    <a href="shop.php" class="btn-shop-now">Shop Now</a>
                </div>
            </div>
            <div class="cart-footer">
                <div class="cart-subtotal">
                    <span>Subtotal:</span>
                    <span class="subtotal-amount">$0.00</span>
                </div>
                <a href="checkout.php" class="btn-checkout">Checkout</a>
                <button class="btn-continue-shopping">Continue Shopping</button>
            </div>
        `;
        document.body.appendChild(cartSidebar);
        
        // Add backdrop for mobile
        const cartBackdrop = document.createElement('div');
        cartBackdrop.className = 'cart-backdrop';
        document.body.appendChild(cartBackdrop);
        
        // Close cart when clicking backdrop or close button
        cartBackdrop.addEventListener('click', toggleCartSidebar);
        cartSidebar.querySelector('.close-cart').addEventListener('click', toggleCartSidebar);
        cartSidebar.querySelector('.btn-continue-shopping').addEventListener('click', toggleCartSidebar);
    }
    
    // Cart icon toggle functionality
    const cartToggle = document.querySelector('.cart-icon');
    if (cartToggle) {
        cartToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleCartSidebar();
            return false;
        });
    }
    
    // Initial render of cart contents
    renderCartItems();
}

/**
 * Toggle cart sidebar visibility
 */
function toggleCartSidebar() {
    const cartSidebar = document.getElementById('cart-sidebar');
    const cartBackdrop = document.querySelector('.cart-backdrop');
    
    if (cartSidebar.classList.contains('active')) {
        cartSidebar.classList.remove('active');
        cartBackdrop.classList.remove('active');
        document.body.classList.remove('cart-open');
    } else {
        cartSidebar.classList.add('active');
        cartBackdrop.classList.add('active');
        document.body.classList.add('cart-open');
        
        // Update cart contents when opening
        renderCartItems();
    }
}

/**
 * Render cart items in the sidebar
 */
function renderCartItems() {
    const cart = JSON.parse(localStorage.getItem('tipunoCart')) || [];
    const cartItemsContainer = document.querySelector('.cart-items');
    const emptyCart = document.querySelector('.cart-empty');
    const cartFooter = document.querySelector('.cart-footer');
    
    if (!cartItemsContainer) return;
    
    // Clear current items
    cartItemsContainer.innerHTML = '';
    
    if (cart.length === 0) {
        // Show empty cart message
        emptyCart.style.display = 'flex';
        cartFooter.style.display = 'none';
        return;
    }
    
    // Hide empty cart message, show footer
    emptyCart.style.display = 'none';
    cartFooter.style.display = 'block';
    
    // Calculate subtotal
    let subtotal = 0;
    
    // Add each item to cart sidebar
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        const cartItemElement = document.createElement('div');
        cartItemElement.className = 'cart-item';
        cartItemElement.innerHTML = `
            <div class="cart-item-image">
                <img src="${item.image}" alt="${item.name}">
            </div>
            <div class="cart-item-details">
                <h4>${item.name}</h4>
                <div class="cart-item-price">$${item.price.toFixed(2)} × ${item.quantity}</div>
                <div class="cart-item-total">$${itemTotal.toFixed(2)}</div>
            </div>
            <div class="cart-item-actions">
                <button class="change-qty minus" data-id="${item.id}">-</button>
                <span class="item-qty">${item.quantity}</span>
                <button class="change-qty plus" data-id="${item.id}">+</button>
                <button class="remove-item" data-id="${item.id}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
        
        cartItemsContainer.appendChild(cartItemElement);
    });
    
    // Update subtotal
    document.querySelector('.subtotal-amount').textContent = `$${subtotal.toFixed(2)}`;
    
    // Add event listeners for quantity changes and removals
    document.querySelectorAll('.change-qty').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const isIncrement = this.classList.contains('plus');
            updateCartItemQuantity(id, isIncrement);
        });
    });
    
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            removeCartItem(id);
        });
    });
}

/**
 * Update quantity of an item in the cart
 */
function updateCartItemQuantity(id, isIncrement) {
    let cart = JSON.parse(localStorage.getItem('tipunoCart')) || [];
    const itemIndex = cart.findIndex(item => item.id === id);
    
    if (itemIndex > -1) {
        if (isIncrement) {
            cart[itemIndex].quantity += 1;
        } else {
            cart[itemIndex].quantity -= 1;
            
            // Remove item if quantity is zero
            if (cart[itemIndex].quantity <= 0) {
                cart.splice(itemIndex, 1);
            }
        }
        
        localStorage.setItem('tipunoCart', JSON.stringify(cart));
        updateCartDisplay();
        renderCartItems();
    }
}

/**
 * Remove an item from the cart
 */
function removeCartItem(id) {
    let cart = JSON.parse(localStorage.getItem('tipunoCart')) || [];
    const itemIndex = cart.findIndex(item => item.id === id);
    
    if (itemIndex > -1) {
        cart.splice(itemIndex, 1);
        localStorage.setItem('tipunoCart', JSON.stringify(cart));
        updateCartDisplay();
        renderCartItems();
    }
}

/**
 * Add product to cart with improved error handling
 */
function addToCart(product) {
    // Check if product is valid
    if (!product || !product.id) {
        console.error('Invalid product data');
        return false;
    }
    
    // Check if user is logged in first
    if (!document.body.hasAttribute('data-user-id')) {
        // Store intended product in session storage for after login
        sessionStorage.setItem('pendingCartAdd', JSON.stringify(product));
        
        // Redirect to login if not logged in
        window.location.href = 'login.php';
        return false;
    }
    
    let cart = JSON.parse(localStorage.getItem('tipunoCart')) || [];
    
    // Check if product already exists in cart
    const existingProductIndex = cart.findIndex(item => item.id === product.id);
    
    if (existingProductIndex > -1) {
        // Increment quantity
        cart[existingProductIndex].quantity += product.quantity || 1;
    } else {
        // Add new product with default quantity of 1 if not provided
        const newProduct = {
            ...product,
            quantity: product.quantity || 1
        };
        cart.push(newProduct);
    }
    
    // Save cart
    localStorage.setItem('tipunoCart', JSON.stringify(cart));
    
    // Update cart UI
    updateCartDisplay();
    renderCartItems();
    
    // Open cart sidebar to show the added item
    const cartSidebar = document.getElementById('cart-sidebar');
    if (cartSidebar && !cartSidebar.classList.contains('active')) {
        toggleCartSidebar();
    }
    
    return true;
}

/**
 * Update cart display with animation
 */
function updateCartDisplay() {
    const cart = JSON.parse(localStorage.getItem('tipunoCart')) || [];
    const cartCount = document.querySelector('.cart-count');
    
    if (cartCount) {
        // Calculate total quantity
        const totalItems = cart.reduce((total, item) => total + (item.quantity || 1), 0);
        
        // Update count with animation
        const oldCount = parseInt(cartCount.textContent) || 0;
        if (totalItems > oldCount) {
            cartCount.classList.add('cart-added');
            setTimeout(() => {
                cartCount.classList.remove('cart-added');
            }, 500);
        }
        
        cartCount.textContent = totalItems;
        cartCount.style.display = totalItems > 0 ? 'flex' : 'none';
    }
}

/**
 * Initialize add to cart buttons for static buttons in the page
 */
function initAddToCartListeners() {
    if (document.body.getAttribute('data-page-type') !== 'shop') return;
    
    // Check for any pending cart additions after login
    const pendingCartAdd = sessionStorage.getItem('pendingCartAdd');
    if (pendingCartAdd) {
        try {
            const product = JSON.parse(pendingCartAdd);
            addToCart(product);
            // Clear pending add
            sessionStorage.removeItem('pendingCartAdd');
        } catch (e) {
            console.error('Error processing pending cart addition', e);
        }
    }
}

/**
 * Clear cart completely
 */
function clearCart() {
    localStorage.removeItem('tipunoCart');
    localStorage.removeItem('tipunoCartOwner');
    updateCartDisplay();
    renderCartItems();
}

// Export functions for global use
window.tipunoCart = {
    add: addToCart,
    update: updateCartDisplay,
    clear: clearCart,
    toggle: toggleCartSidebar
};

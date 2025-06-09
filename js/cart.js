/**
 * Cart functionality for Tipuno Barbershop
 */
document.addEventListener('DOMContentLoaded', function() {
    // Cart state
    let cart = [];
    
    // Load cart from localStorage
    loadCart();
    
    // Make addToCart available globally
    window.addToCart = function(item) {
        addItemToCart(item);
    };
    
    /**
     * Load cart data
     */
    function loadCart() {
        const savedCart = localStorage.getItem('cart');
        if (savedCart) {
            try {
                cart = JSON.parse(savedCart);
            } catch(e) {
                console.error('Error loading cart:', e);
                cart = [];
            }
        }
    }
    
    /**
     * Save cart data
     */
    function saveCart() {
        localStorage.setItem('cart', JSON.stringify(cart));
        
        if (typeof sessionStorage !== 'undefined') {
            sessionStorage.setItem('cart', JSON.stringify(cart));
        }
    }
    
    /**
     * Add item to cart
     */
    function addItemToCart(item) {
        // Check if item exists
        const existingItem = cart.find(cartItem => cartItem.id == item.id);
        
        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({
                id: item.id,
                name: item.name,
                price: parseFloat(item.price),
                image: item.image,
                quantity: 1
            });
        }
        
        // Save cart
        saveCart();
        
        // Update server-side cart
        fetch('includes/api/save_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart })
        }).catch(err => console.error('Error saving cart to server:', err));
        
        // Note: No notification needed
    }

    // Handle clicking the "Checkout Now" button in the notification
    document.addEventListener('click', function(e) {
        if (e.target.matches('.notification-checkout')) {
            e.preventDefault();
            window.location.href = 'payment.php';
        }
    });
});

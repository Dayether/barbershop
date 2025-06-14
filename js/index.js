/**
 * Tipuno Barbershop - Homepage JavaScript
 * Handles all interactive elements specific to the homepage
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize testimonial slider
    initTestimonialsSlider();
    
    // Initialize parallax effect on about section image
    initParallaxEffect();
    
    // Add hover effect for service cards
    initServiceHoverEffects();
    
    // Setup quick view functionality for products
    setupQuickViews();
    
    // Handle page section transitions
    handlePageTransitions();
});

/**
 * Initialize testimonial slider with automatic rotation
 */
function initTestimonialsSlider() {
    const testimonialCards = document.querySelectorAll('.testimonial-card');
    let currentIndex = 0;
    
    // Exit if no testimonials found
    if (testimonialCards.length === 0) return;
    
    // Hide all except first
    testimonialCards.forEach((card, index) => {
        if (index !== 0) {
            card.style.display = 'none';
            card.style.opacity = '0';
        } else {
            card.classList.add('active');
        }
    });
    
    // Set interval for automatic rotation
    setInterval(() => {
        // Fade out current testimonial
        testimonialCards[currentIndex].style.opacity = '0';
        setTimeout(() => {
            testimonialCards[currentIndex].style.display = 'none';
            testimonialCards[currentIndex].classList.remove('active');
            
            // Move to next testimonial
            currentIndex = (currentIndex + 1) % testimonialCards.length;
            
            // Fade in next testimonial
            testimonialCards[currentIndex].style.display = 'block';
            setTimeout(() => {
                testimonialCards[currentIndex].style.opacity = '1';
                testimonialCards[currentIndex].classList.add('active');
            }, 50);
        }, 500);
    }, 5000); // Change testimonial every 5 seconds
}

/**
 * Initialize parallax effect on scroll
 */


/**
 * Initialize hover effects for service cards
 */
function initServiceHoverEffects() {
    const serviceCards = document.querySelectorAll('.service-card');
    
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('hover');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('hover');
        });
    });
}

/**
 * Setup quick view modals for products
 */
function setupQuickViews() {
    const quickViewButtons = document.querySelectorAll('.quick-view-btn');
    
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product');
            
            // Product data would typically come from a database
            // For this example, we'll use hardcoded product info
            const productInfo = {
                1: {
                    name: "Premium Pomade",
                    price: "$15.00",
                    description: "Our premium water-based pomade provides strong hold with a matte finish. Perfect for creating classic and modern styles with easy washout.",
                    image: "images/product1.jpg",
                    rating: 4.5,
                    reviews: 42
                },
                2: {
                    name: "Luxury Beard Oil",
                    price: "$20.00",
                    description: "This luxury beard oil softens coarse hair and moisturizes skin underneath for a healthier, more manageable beard. Scented with essential oils.",
                    image: "images/product2.jpg",
                    rating: 5,
                    reviews: 27
                },
                3: {
                    name: "Premium Shaving Cream",
                    price: "$10.00",
                    description: "Our premium shaving cream creates a rich lather that softens hair for a smooth, comfortable shave while protecting your skin.",
                    image: "images/product3.jpg",
                    rating: 4,
                    reviews: 18
                }
            };
            
            const product = productInfo[productId];
            
            // Create and show modal
            if (product) {
                createQuickViewModal(product, productId);
            }
        });
    });
}

/**
 * Create quick view modal for product
 */
function createQuickViewModal(product, productId) {
    // Create modal HTML
    const modalHTML = `
        <div class="quick-view-modal">
            <div class="modal-content">
                <button class="close-modal">&times;</button>
                <div class="modal-grid">
                    <div class="modal-image">
                        <img src="${product.image}" alt="${product.name}">
                    </div>
                    <div class="modal-details">
                        <h2>${product.name}</h2>
                        <div class="product-rating">
                            ${generateStarRating(product.rating)}
                            <span>(${product.reviews} reviews)</span>
                        </div>
                        <div class="product-price">${product.price}</div>
                        <div class="product-description">${product.description}</div>
                        <button class="btn btn-primary btn-add-to-cart" 
                            data-id="${productId}" 
                            data-name="${product.name}" 
                            data-price="${product.price.replace('$', '')}" 
                            data-image="${product.image}">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Get modal element
    const modal = document.querySelector('.quick-view-modal');
    
    // Show modal with animation
    setTimeout(() => {
        modal.classList.add('active');
    }, 10);
    
    // Close modal functionality
    const closeBtn = modal.querySelector('.close-modal');
    closeBtn.addEventListener('click', () => {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
        }, 300);
    });
    
    // Close on overlay click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.remove();
            }, 300);
        }
    });
    
    // Add to cart functionality for button in modal
    const addToCartBtn = modal.querySelector('.btn-add-to-cart');
    addToCartBtn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const name = this.getAttribute('data-name');
        const price = parseFloat(this.getAttribute('data-price'));
        const image = this.getAttribute('data-image');
        
        // Add to cart (assuming the cart functionality is defined in common.js)
        addToCart(id, name, price, image);
        
        // Close the modal after adding to cart
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
        }, 300);
    });
}

/**
 * Generate star rating HTML based on rating value
 */
function generateStarRating(rating) {
    let stars = '';
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
    
    // Add full stars
    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }
    
    // Add half star if needed
    if (halfStar) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }
    
    // Add empty stars
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star"></i>';
    }
    
    return stars;
}

/**
 * Add item to cart
 */
function addToCart(id, name, price, image) {
    // Get current cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Check if product already in cart
    const existingItem = cart.find(item => item.id === id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: id,
            name: name,
            price: price,
            image: image,
            quantity: 1
        });
    }
    
    // Save cart back to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Update cart UI (assuming this function exists in common.js)
    if (typeof updateCartCount === 'function') {
        updateCartCount(cart);
    }
    
    // Show success message
    showAddToCartNotification(name);
}

/**
 * Show notification when product is added to cart
 */
function showAddToCartNotification(productName) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="notification-message">
            <p><strong>${productName}</strong> added to your cart</p>
        </div>
    `;
    
    // Add to DOM
    document.body.appendChild(notification);
    
    // Show notification with animation
    setTimeout(() => {
        notification.classList.add('active');
        
        // Hide and remove after delay
        setTimeout(() => {
            notification.classList.remove('active');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }, 10);
}

/**
 * Handle page section transitions and animations
 */
function handlePageTransitions() {
    // Animate hero scroll indicator
    const scrollIndicator = document.querySelector('.hero-scroll-indicator');
    if (scrollIndicator) {
        setTimeout(() => {
            scrollIndicator.classList.add('active');
        }, 2000);
    }
}

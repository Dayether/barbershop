/**
 * Shop page functionality for Tipuno Barbershop
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get all filter buttons and products
    const filterButtons = document.querySelectorAll('.filter-btn');
    const productCards = document.querySelectorAll('.product-card');
    const sortDropdown = document.getElementById('sort-products');
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Initialize product cards with proper display and opacity
    productCards.forEach(card => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    });
    
    // Add click event to filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get filter value
            const filterValue = this.getAttribute('data-filter');
            
            // Show/hide products based on filter with smooth animation
            let visibleCount = 0;
            productCards.forEach(card => {
                if (filterValue === 'all' || card.getAttribute('data-category') === filterValue) {
                    card.style.display = 'block';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 10);
                    visibleCount++;
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });
            
            // Update product count
            const countElement = document.querySelector('.product-count');
            if (countElement) {
                countElement.textContent = `Showing ${visibleCount} of ${productCards.length} products`;
            }
        });
    });
    
    // Add change event to sort dropdown with improved functionality
    if (sortDropdown) {
        sortDropdown.addEventListener('change', function() {
            const sortValue = this.value;
            const productGrid = document.querySelector('.shop-grid');
            
            if (!productGrid) {
                console.error('Product grid not found!');
                return;
            }
            
            const productsArray = Array.from(productCards);
            
            // Apply sorting with visual feedback
            document.body.style.cursor = 'wait';
            productGrid.style.opacity = '0.6';
            
            setTimeout(() => {
                // Sort products based on selected value
                switch(sortValue) {
                    case 'price-low':
                        productsArray.sort((a, b) => {
                            const priceA = parseFloat(a.getAttribute('data-price')) || 0;
                            const priceB = parseFloat(b.getAttribute('data-price')) || 0;
                            return priceA - priceB;
                        });
                        break;
                    case 'price-high':
                        productsArray.sort((a, b) => {
                            const priceA = parseFloat(a.getAttribute('data-price')) || 0;
                            const priceB = parseFloat(b.getAttribute('data-price')) || 0;
                            return priceB - priceA;
                        });
                        break;
                    case 'name':
                        productsArray.sort((a, b) => {
                            const nameA = a.querySelector('h3')?.textContent || '';
                            const nameB = b.querySelector('h3')?.textContent || '';
                            return nameA.localeCompare(nameB);
                        });
                        break;
                    default:
                        // Default sort - restore original order
                        productsArray.sort((a, b) => {
                            const orderA = parseInt(a.getAttribute('data-original-order')) || 0;
                            const orderB = parseInt(b.getAttribute('data-original-order')) || 0;
                            return orderA - orderB;
                        });
                }
                
                // Clear and re-append sorted products
                while (productGrid.firstChild) {
                    productGrid.removeChild(productGrid.firstChild);
                }
                
                productsArray.forEach(product => {
                    productGrid.appendChild(product);
                });
                
                // Restore visual state
                productGrid.style.opacity = '1';
                document.body.style.cursor = 'default';
            }, 100);
        });
    }
    
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.product-card .btn-add-to-cart');
    
    // Update cart in session
    function updateCart(cart) {
        localStorage.setItem('cart', JSON.stringify(cart));
        
        // Send cart update to server
        fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ cart: cart }),
        });
    }
    
    // Add to cart functionality with direct checkout option
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = parseInt(this.getAttribute('data-id'));
            const productName = this.getAttribute('data-name');
            const productPrice = parseFloat(this.getAttribute('data-price'));
            const productImage = this.getAttribute('data-image');
            
            // Check if product already in cart
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    image: productImage,
                    quantity: 1
                });
            }
            
            // Update cart
            updateCart(cart);
            
            // Show success message
            this.innerHTML = '<i class="fas fa-check"></i> Added to Cart';
            this.classList.add('added');
            
            // Create floating notification
            const notification = document.createElement('div');
            notification.classList.add('cart-notification');
            notification.innerHTML = `
                <div class="cart-notification-content">
                    <i class="fas fa-check-circle"></i>
                    <div class="notification-text">
                        <p><strong>${productName}</strong> added to cart</p>
                        <div class="notification-actions">
                            <button class="notification-continue">Continue Shopping</button>
                            <button class="notification-checkout">Checkout Now</button>
                        </div>
                    </div>
                    <button class="notification-close">&times;</button>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            // Handle notification buttons
            notification.querySelector('.notification-continue').addEventListener('click', () => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            });
            
            notification.querySelector('.notification-checkout').addEventListener('click', () => {
                window.location.href = 'checkout.php';
            });
            
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            });
            
            // Reset button after delay
            setTimeout(() => {
                this.innerHTML = 'Add to Cart';
                this.classList.remove('added');
            }, 2000);
        });
    });
    
    // Quick view functionality
    const quickViewButtons = document.querySelectorAll('.quick-view-btn');
    
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productCard = this.closest('.product-card');
            const productName = productCard.querySelector('h3').textContent;
            const productPrice = productCard.querySelector('.product-price').textContent;
            const productDesc = productCard.querySelector('.product-description').textContent;
            const productImage = productCard.querySelector('.product-image-wrapper img').src;
            const addToCartBtn = productCard.querySelector('.btn-add-to-cart');
            
            // Create modal
            const modal = document.createElement('div');
            modal.classList.add('quick-view-modal');
            modal.innerHTML = `
                <style>
                .btn-back-modal {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    background: #2a9d8f;
                    color: #fff;
                    border: none;
                    border-radius: 24px;
                    padding: 10px 24px;
                    font-size: 1rem;
                    font-weight: 600;
                    cursor: pointer;
                    margin-top: 18px;
                    transition: background 0.2s;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                }
                .btn-back-modal:hover {
                    background: #21867a;
                }
                </style>
                <div class="quick-view-content">
                    <button class="close-modal">&times;</button>
                    <div class="quick-view-image">
                        <img src="${productImage}" alt="${productName}">
                    </div>
                    <div class="quick-view-details">
                        <h2>${productName}</h2>
                        <div class="quick-view-price">${productPrice}</div>
                        <p>${productDesc}</p>
                        <button class="btn-back-modal"><i class='fas fa-arrow-left'></i> Back</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            document.body.classList.add('modal-open');
            
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            
            // Close modal (X button)
            modal.querySelector('.close-modal').addEventListener('click', () => {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.remove();
                    document.body.classList.remove('modal-open');
                }, 300);
            });
            // Back button closes modal
            modal.querySelector('.btn-back-modal').addEventListener('click', () => {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.remove();
                    document.body.classList.remove('modal-open');
                }, 300);
            });
        });
    });
    
    // Initialize - apply active filter and default sort on page load
    const activeFilterButton = document.querySelector('.filter-btn.active');
    if (activeFilterButton) {
        activeFilterButton.click();
    } else if (filterButtons.length > 0) {
        filterButtons[0].click();
    }
    
    // Apply initial sort if URL parameter exists
    const urlParams = new URLSearchParams(window.location.search);
    const sortParam = urlParams.get('sort');
    if (sortParam && sortDropdown && sortDropdown.querySelector(`option[value="${sortParam}"]`)) {
        sortDropdown.value = sortParam;
        sortDropdown.dispatchEvent(new Event('change'));
    }
});

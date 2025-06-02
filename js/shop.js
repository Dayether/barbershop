/**
 * Shop page functionality for Tipuno Barbershop
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get all filter buttons and products
    const filterButtons = document.querySelectorAll('.filter-btn');
    const productCards = document.querySelectorAll('.product-card');
    const sortDropdown = document.getElementById('sort-products');
    
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
            productCards.forEach(card => {
                if (filterValue === 'all' || card.getAttribute('data-category') === filterValue) {
                    card.style.display = 'block';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 10);
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });
            
            // Update product count
            updateProductCount();
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
                
                // Update any display counters if needed
                updateProductCount();
            }, 100);
        });
    }
    
    // Function to update product count
    function updateProductCount() {
        const visibleProducts = Array.from(productCards).filter(card => 
            card.style.display !== 'none'
        ).length;
        const totalProducts = productCards.length;
        
        // If you have a product count element, update it
        const countElement = document.querySelector('.product-count');
        if (countElement) {
            countElement.textContent = `Showing ${visibleProducts} of ${totalProducts} products`;
        }
    }
    
    // Handle quick view button clicks
    const quickViewButtons = document.querySelectorAll('.quick-view-btn');
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productCard = this.closest('.product-card');
            const productName = productCard.querySelector('h3').textContent;
            const productImage = productCard.querySelector('img').src;
            const productPrice = productCard.querySelector('.product-price').textContent;
            const productDescription = productCard.querySelector('.product-description')?.textContent || 'No description available';
            const productId = productCard.querySelector('.btn-add-to-cart')?.getAttribute('data-id');
            
            // Create and show modal
            showProductQuickView(productName, productImage, productPrice, productDescription, productId);
        });
    });
    
    // Enhanced quick view modal function
    function showProductQuickView(name, image, price, description, productId) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('quick-view-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'quick-view-modal';
            modal.className = 'quick-view-modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <div class="modal-body">
                        <div class="product-quick-view">
                            <div class="product-image">
                                <img src="" alt="">
                            </div>
                            <div class="product-info">
                                <h2 class="product-title"></h2>
                                <div class="product-price"></div>
                                <div class="product-description"></div>
                                <div class="product-quantity">
                                    <label for="qty">Quantity:</label>
                                    <div class="quantity-selector">
                                        <button type="button" class="qty-btn minus">-</button>
                                        <input type="number" id="qty" name="qty" min="1" value="1">
                                        <button type="button" class="qty-btn plus">+</button>
                                    </div>
                                </div>
                                <button class="btn-add-to-cart">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Add close functionality
            modal.querySelector('.close-modal').addEventListener('click', function() {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            });
            
            // Close when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                }
            });
            
            // Quantity selector functionality
            const qtyInput = modal.querySelector('#qty');
            const minusBtn = modal.querySelector('.qty-btn.minus');
            const plusBtn = modal.querySelector('.qty-btn.plus');
            
            minusBtn.addEventListener('click', function() {
                const currentValue = parseInt(qtyInput.value);
                if (currentValue > 1) {
                    qtyInput.value = currentValue - 1;
                }
            });
            
            plusBtn.addEventListener('click', function() {
                const currentValue = parseInt(qtyInput.value);
                qtyInput.value = currentValue + 1;
            });
        }
        
        // Update modal content
        modal.querySelector('.product-title').textContent = name;
        modal.querySelector('.product-image img').src = image;
        modal.querySelector('.product-image img').alt = name;
        modal.querySelector('.product-price').textContent = price;
        modal.querySelector('.product-description').textContent = description;
        
        // Reset quantity
        const qtyInput = modal.querySelector('#qty');
        if (qtyInput) qtyInput.value = 1;
        
        // Add to cart functionality
        const addToCartBtn = modal.querySelector('.btn-add-to-cart');
        addToCartBtn.addEventListener('click', function() {
            const quantity = parseInt(modal.querySelector('#qty').value) || 1;
            
            if (window.tipunoCart && typeof window.tipunoCart.add === 'function') {
                const product = {
                    id: productId,
                    name: name,
                    price: parseFloat(price.replace('$', '')),
                    image: image,
                    quantity: quantity
                };
                
                const success = window.tipunoCart.add(product);
                
                if (success) {
                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.className = 'add-to-cart-success';
                    successMsg.innerHTML = `
                        <i class="fas fa-check-circle"></i>
                        Added to cart! <a href="cart.php">View Cart</a>
                    `;
                    
                    addToCartBtn.style.display = 'none';
                    addToCartBtn.insertAdjacentElement('afterend', successMsg);
                    
                    // Remove message after 3 seconds and restore button
                    setTimeout(() => {
                        successMsg.remove();
                        addToCartBtn.style.display = 'block';
                    }, 3000);
                    
                    // Close modal after a delay
                    setTimeout(() => {
                        modal.style.display = 'none';
                        document.body.classList.remove('modal-open');
                    }, 2000);
                }
            }
        });
        
        // Show the modal
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
    }
    
    // Add to cart functionality for product grid buttons
    const addToCartButtons = document.querySelectorAll('.product-card .btn-add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (this.disabled) return;
            
            const product = {
                id: this.getAttribute('data-id'),
                name: this.getAttribute('data-name'),
                price: parseFloat(this.getAttribute('data-price')),
                image: this.getAttribute('data-image'),
                quantity: 1
            };
            
            if (window.tipunoCart && typeof window.tipunoCart.add === 'function') {
                const success = window.tipunoCart.add(product);
                
                if (success) {
                    // Visual feedback
                    const originalText = this.textContent;
                    this.innerHTML = '<i class="fas fa-check"></i> Added!';
                    this.classList.add('added');
                    
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.classList.remove('added');
                    }, 2000);
                }
            }
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

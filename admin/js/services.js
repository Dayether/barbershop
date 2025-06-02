/**
 * Services Management JavaScript
 */

// Global variables
let allServices = [];
let filteredServices = [];
let currentPage = 1;
const itemsPerPage = 10;

// Initialize when the document is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Show loading overlay
    document.getElementById('loading-overlay').style.display = 'flex';
    
    // Initialize services page
    initServicesPage();
});

/**
 * Initialize the services page
 */
function initServicesPage() {
    // Fetch services data
    fetchServices()
        .then(data => {
            // Store services data
            allServices = data;
            filteredServices = [...allServices];
            
            // Update statistics
            updateStatistics();
            
            // Render services in both views
            renderTableView();
            renderCardView();
            
            // Hide loading overlay with fade effect
            const loadingOverlay = document.getElementById('loading-overlay');
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                loadingOverlay.style.opacity = '1';
            }, 500);
        })
        .catch(error => {
            console.error('Error loading services:', error);
            // Hide loading overlay
            document.getElementById('loading-overlay').style.display = 'none';
            // Show error notification
            showNotification('Failed to load services data', 'error');
        });
    
    // Set up event listeners
    setupEventListeners();
}

/**
 * Fetch services from server or use mock data
 */
function fetchServices() {
    return new Promise((resolve) => {
        // For demonstration, we'll use mock data
        // In a real app, you would fetch from a server endpoint
        setTimeout(() => {
            resolve(generateMockServices());
        }, 800); // Simulate network delay
    });
}

/**
 * Generate mock services data
 */
function generateMockServices() {
    return [
        {
            id: 1,
            name: "Classic Haircut",
            description: "Precision cut tailored to your style preferences with expert attention to detail",
            duration: 45,
            price: 30.00,
            image: "uploads/services/haircut.jpg",
            active: 1
        },
        {
            id: 2,
            name: "Beard Trim",
            description: "Expert grooming and shaping to keep your facial hair looking sharp and well-maintained",
            duration: 30,
            price: 25.00,
            image: "uploads/services/beard.jpg",
            active: 1
        },
        {
            id: 3,
            name: "Hot Towel Shave",
            description: "Traditional barbering experience with hot towels, premium shaving cream, and straight razor for the closest shave",
            duration: 45,
            price: 35.00,
            image: "uploads/services/shave.jpg",
            active: 1
        },
        {
            id: 4,
            name: "Complete Package",
            description: "Full grooming experience including haircut, beard trim, and hot towel shave for a total transformation",
            duration: 90,
            price: 75.00,
            image: "uploads/services/package.jpg",
            active: 1
        },
        {
            id: 5,
            name: "Hair Styling",
            description: "Professional styling with premium products to achieve your desired look for any occasion",
            duration: 30,
            price: 20.00,
            image: "uploads/services/styling.jpg",
            active: 1
        },
        {
            id: 6,
            name: "Hair Coloring",
            description: "Expert color application to refresh your look or try something completely new",
            duration: 120,
            price: 85.00,
            image: "uploads/services/coloring.jpg",
            active: 0
        },
        {
            id: 7,
            name: "Kids Haircut",
            description: "Gentle and patient approach to children's haircuts in a fun and friendly atmosphere",
            duration: 30,
            price: 20.00,
            image: "uploads/services/kids.jpg",
            active: 1
        },
        {
            id: 8,
            name: "Beard Coloring",
            description: "Professional beard coloring service to cover grays or change your look",
            duration: 45,
            price: 40.00,
            image: "uploads/services/beard-color.jpg",
            active: 0
        }
    ];
}

/**
 * Set up event listeners
 */
function setupEventListeners() {
    // View toggle buttons
    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Update active state
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Show selected view
            const view = this.getAttribute('data-view');
            if (view === 'table') {
                document.getElementById('table-view').style.display = 'block';
                document.getElementById('card-view').style.display = 'none';
            } else {
                document.getElementById('table-view').style.display = 'none';
                document.getElementById('card-view').style.display = 'block';
            }
        });
    });
    
    // Search input
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            filterServices();
        }, 300));
    }
    
    // Filter selects
    document.getElementById('status-filter').addEventListener('change', filterServices);
    document.getElementById('price-filter').addEventListener('change', filterServices);
    
    // Reset filters button
    document.getElementById('reset-filters').addEventListener('click', function() {
        document.getElementById('search-input').value = '';
        document.getElementById('status-filter').value = '';
        document.getElementById('price-filter').value = '';
        
        filterServices();
    });
    
    // Add service button
    document.getElementById('add-service-btn').addEventListener('click', function() {
        openServiceModal('add');
    });
    
    // Service form submission
    document.getElementById('service-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveService();
    });
    
    // Image upload preview
    const imageInput = document.getElementById('service-image');
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const fileNameSpan = document.querySelector('.file-name');
            const previewDiv = document.getElementById('image-preview');
            
            if (this.files && this.files[0]) {
                // Update file name
                fileNameSpan.textContent = this.files[0].name;
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewDiv.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                fileNameSpan.textContent = 'No file chosen';
                previewDiv.innerHTML = '';
            }
        });
    }
    
    // Close modal buttons
    document.querySelectorAll('.close-modal').forEach(button => {
        button.addEventListener('click', function() {
            closeAllModals();
        });
    });
    
    // Confirm delete action button
    document.getElementById('confirm-action-btn').addEventListener('click', function() {
        const serviceId = parseInt(this.getAttribute('data-id'));
        if (!isNaN(serviceId)) {
            deleteService(serviceId);
        }
        closeAllModals();
    });
}

/**
 * Filter services based on search input and filter selects
 */
function filterServices() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const statusFilter = document.getElementById('status-filter').value;
    const priceFilter = document.getElementById('price-filter').value;
    
    filteredServices = allServices.filter(service => {
        // Search term filter
        const matchesSearch = searchTerm === '' || 
            service.name.toLowerCase().includes(searchTerm) || 
            service.description.toLowerCase().includes(searchTerm);
        
        // Status filter
        let matchesStatus = true;
        if (statusFilter === 'active') {
            matchesStatus = service.active === 1;
        } else if (statusFilter === 'inactive') {
            matchesStatus = service.active === 0;
        }
        
        // Price filter
        let matchesPrice = true;
        if (priceFilter) {
            const price = service.price;
            switch (priceFilter) {
                case '0-25':
                    matchesPrice = price >= 0 && price <= 25;
                    break;
                case '25-50':
                    matchesPrice = price > 25 && price <= 50;
                    break;
                case '50-100':
                    matchesPrice = price > 50 && price <= 100;
                    break;
                case '100+':
                    matchesPrice = price > 100;
                    break;
            }
        }
        
        return matchesSearch && matchesStatus && matchesPrice;
    });
    
    // Reset to first page
    currentPage = 1;
    
    // Update statistics (not needed here since totals don't change)
    
    // Re-render views
    renderTableView();
    renderCardView();
}

/**
 * Update statistics counters
 */
function updateStatistics() {
    // Total services
    document.getElementById('total-services').textContent = allServices.length;
    
    // Active services
    const activeServices = allServices.filter(service => service.active === 1).length;
    document.getElementById('active-services').textContent = activeServices;
    
    // Average price
    const totalPrice = allServices.reduce((sum, service) => sum + service.price, 0);
    const avgPrice = allServices.length ? (totalPrice / allServices.length).toFixed(2) : '0.00';
    document.getElementById('avg-price').textContent = `$${avgPrice}`;
}

/**
 * Render the table view
 */
function renderTableView() {
    const tableBody = document.getElementById('services-table-body');
    const pagination = document.getElementById('table-pagination');
    
    // Calculate pagination
    const totalPages = Math.ceil(filteredServices.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredServices.length);
    const currentItems = filteredServices.slice(startIndex, endIndex);
    
    // Clear the table body
    tableBody.innerHTML = '';
    
    if (currentItems.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">
                    No services found matching your criteria.
                </td>
            </tr>
        `;
    } else {
        // Render each service row
        currentItems.forEach(service => {
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>
                    <div class="service-name-cell">
                        <img src="../${service.image}" alt="${service.name}" class="service-image">
                        <h4>${escapeHtml(service.name)}</h4>
                    </div>
                </td>
                <td class="description-cell" title="${escapeHtml(service.description)}">
                    ${escapeHtml(service.description)}
                </td>
                <td>${service.duration} min</td>
                <td>$${service.price.toFixed(2)}</td>
                <td>
                    <span class="status-badge ${service.active === 1 ? 'active' : 'inactive'}">
                        ${service.active === 1 ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <div class="actions">
                        <button class="action-btn edit" title="Edit Service" onclick="openServiceModal('edit', ${service.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" title="Delete Service" onclick="confirmDelete(${service.id}, '${escapeHtml(service.name)}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
    }
    
    // Render pagination
    renderPagination(pagination, totalPages);
}

/**
 * Render the card view
 */
function renderCardView() {
    const cardsContainer = document.getElementById('services-grid');
    const pagination = document.getElementById('card-pagination');
    
    // Calculate pagination
    const totalPages = Math.ceil(filteredServices.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredServices.length);
    const currentItems = filteredServices.slice(startIndex, endIndex);
    
    // Clear the cards container
    cardsContainer.innerHTML = '';
    
    if (currentItems.length === 0) {
        cardsContainer.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 20px;">
                No services found matching your criteria.
            </div>
        `;
    } else {
        // Render each service card
        currentItems.forEach(service => {
            const card = document.createElement('div');
            card.className = 'service-card';
            
            card.innerHTML = `
                <img src="../${service.image}" alt="${service.name}" class="service-card-image">
                <div class="service-card-body">
                    <div class="service-card-title">
                        <h3>${escapeHtml(service.name)}</h3>
                        <span class="price">$${service.price.toFixed(2)}</span>
                    </div>
                    <div class="service-card-description">
                        ${escapeHtml(service.description)}
                    </div>
                    <div class="service-card-details">
                        <div class="service-card-detail">
                            <span>Duration</span>
                            <strong>${service.duration} min</strong>
                        </div>
                        <div class="service-card-detail">
                            <span>Status</span>
                            <span class="status-badge ${service.active === 1 ? 'active' : 'inactive'}">
                                ${service.active === 1 ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    </div>
                    <div class="service-card-actions">
                        <button class="btn btn-sm btn-outline" onclick="openServiceModal('edit', ${service.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(${service.id}, '${escapeHtml(service.name)}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            `;
            
            cardsContainer.appendChild(card);
        });
    }
    
    // Render pagination
    renderPagination(pagination, totalPages);
}

/**
 * Render pagination controls
 */
function renderPagination(container, totalPages) {
    if (!container) return;
    
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Previous button
    html += `
        <button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
            <i class="fas fa-chevron-left"></i>
        </button>
    `;
    
    // Page numbers
    const maxButtons = 5;
    const startPage = Math.max(1, Math.min(currentPage - Math.floor(maxButtons / 2), totalPages - maxButtons + 1));
    const endPage = Math.min(totalPages, startPage + maxButtons - 1);
    
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">
                ${i}
            </button>
        `;
    }
    
    // Next button
    html += `
        <button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
            <i class="fas fa-chevron-right"></i>
        </button>
    `;
    
    container.innerHTML = html;
}

/**
 * Change page
 */
function changePage(page) {
    currentPage = page;
    renderTableView();
    renderCardView();
}

/**
 * Open modal for adding or editing a service
 */
function openServiceModal(action, serviceId = null) {
    const modal = document.getElementById('service-modal');
    const form = document.getElementById('service-form');
    const modalTitle = document.getElementById('modal-title');
    const formAction = document.getElementById('form-action');
    const imagePreview = document.getElementById('image-preview');
    
    // Reset form
    form.reset();
    imagePreview.innerHTML = '';
    document.querySelector('.file-name').textContent = 'No file chosen';
    
    if (action === 'add') {
        // Adding a new service
        modalTitle.textContent = 'Add New Service';
        formAction.value = 'add';
    } else if (action === 'edit' && serviceId) {
        // Editing existing service
        modalTitle.textContent = 'Edit Service';
        formAction.value = 'edit';
        
        // Find the service
        const service = allServices.find(s => s.id === serviceId);
        if (service) {
            // Populate form fields
            document.getElementById('service-id').value = service.id;
            document.getElementById('service-name').value = service.name;
            document.getElementById('service-description').value = service.description;
            document.getElementById('service-duration').value = service.duration;
            document.getElementById('service-price').value = service.price;
            document.getElementById('service-status').value = service.active;
            
            // Show image preview if available
            if (service.image) {
                imagePreview.innerHTML = `<img src="../${service.image}" alt="Service Image">`;
            }
        }
    }
    
    // Show modal
    modal.style.display = 'block';
}

/**
 * Save a service (add or edit)
 */
function saveService() {
    const formAction = document.getElementById('form-action').value;
    const serviceId = document.getElementById('service-id').value;
    
    // Get form data
    const serviceData = {
        id: serviceId ? parseInt(serviceId) : allServices.length + 1,
        name: document.getElementById('service-name').value,
        description: document.getElementById('service-description').value,
        duration: parseInt(document.getElementById('service-duration').value),
        price: parseFloat(document.getElementById('service-price').value),
        active: parseInt(document.getElementById('service-status').value),
        image: 'uploads/services/default.jpg' // Default image path
    };
    
    // Check for image upload
    const imageInput = document.getElementById('service-image');
    if (imageInput.files && imageInput.files[0]) {
        // In a real app, you would upload this file to the server
        // For this demo, we'll just use a placeholder path
        serviceData.image = 'uploads/services/uploaded.jpg';
    }
    
    if (formAction === 'add') {
        // Add new service
        allServices.unshift(serviceData);
        showNotification('Service added successfully!', 'success');
    } else {
        // Update existing service
        const index = allServices.findIndex(s => s.id === serviceData.id);
        if (index !== -1) {
            // Preserve the original image if no new one was selected
            if (!imageInput.files || !imageInput.files[0]) {
                serviceData.image = allServices[index].image;
            }
            
            allServices[index] = serviceData;
            showNotification('Service updated successfully!', 'success');
        }
    }
    
    // Close modal
    closeAllModals();
    
    // Update statistics
    updateStatistics();
    
    // Refresh filtered services and render views
    filteredServices = [...allServices];
    filterServices();
}

/**
 * Confirm deletion of a service
 */
function confirmDelete(serviceId, serviceName) {
    const modal = document.getElementById('confirm-modal');
    const message = document.getElementById('confirm-message');
    const confirmBtn = document.getElementById('confirm-action-btn');
    
    message.textContent = `Are you sure you want to delete the service "${serviceName}"?`;
    confirmBtn.setAttribute('data-id', serviceId);
    
    modal.style.display = 'block';
}

/**
 * Delete a service
 */
function deleteService(serviceId) {
    // Remove service from array
    const index = allServices.findIndex(s => s.id === serviceId);
    if (index !== -1) {
        allServices.splice(index, 1);
        
        // Update statistics
        updateStatistics();
        
        // Refresh filtered services and render views
        filteredServices = [...allServices];
        filterServices();
        
        showNotification('Service deleted successfully!', 'success');
    }
}

/**
 * Close all modals
 */
function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
}

/**
 * Show a notification
 */
function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        </div>
        <div class="notification-message">${message}</div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Add close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.classList.add('hide');
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

/**
 * Debounce function for search input
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

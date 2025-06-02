/**
 * Admin Dashboard JavaScript
 * For Tipuno Barbershop Admin Panel
 */

document.addEventListener('DOMContentLoaded', function() {
    /**
     * Sidebar toggle functionality
     */
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const body = document.body;
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            body.classList.toggle('sidebar-collapsed');
            
            // Update toggle icon
            const icon = this.querySelector('i');
            if (body.classList.contains('sidebar-collapsed')) {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-left');
            }
            
            // Store preference in local storage
            localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
        });
        
        // Check if sidebar was previously collapsed
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            body.classList.add('sidebar-collapsed');
            const icon = sidebarToggle.querySelector('i');
            icon.classList.remove('fa-chevron-left');
            icon.classList.add('fa-chevron-right');
        }
    }
    
    /**
     * Add fade effect to alerts
     */
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Add dismiss button if not present
        if (!alert.querySelector('.dismiss-alert')) {
            const dismissButton = document.createElement('button');
            dismissButton.className = 'dismiss-alert';
            dismissButton.innerHTML = '<i class="fas fa-times"></i>';
            dismissButton.addEventListener('click', () => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            });
            alert.appendChild(dismissButton);
        }
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });
    
    /**
     * Data tables enhancement
     */
    const dataTables = document.querySelectorAll('.data-table');
    dataTables.forEach(table => {
        // Add hover effect to rows
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseover', () => {
                row.style.backgroundColor = '#f8f9fa';
            });
            
            row.addEventListener('mouseout', () => {
                row.style.backgroundColor = '';
            });
        });
        
        // Add sorting functionality if needed
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.addEventListener('click', () => {
                const column = header.getAttribute('data-column');
                const currentDirection = header.getAttribute('data-direction') || 'asc';
                const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                
                // Reset all headers
                headers.forEach(h => {
                    h.setAttribute('data-direction', '');
                    h.querySelector('i')?.remove();
                });
                
                // Set new direction and icon
                header.setAttribute('data-direction', newDirection);
                
                // Add sort icon
                const icon = document.createElement('i');
                icon.className = newDirection === 'asc' 
                    ? 'fas fa-sort-up ml-2' 
                    : 'fas fa-sort-down ml-2';
                header.appendChild(icon);
                
                // Implement sorting logic as needed
                // This would typically be done server-side or with a library
            });
        });
    });
    
    /**
     * Form validation enhancement
     */
    const adminForms = document.querySelectorAll('.admin-form');
    adminForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let hasError = false;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    hasError = true;
                    field.classList.add('field-error');
                    
                    // Add error message if not exists
                    let errorMessage = field.parentElement.querySelector('.error-message');
                    if (!errorMessage) {
                        errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = 'This field is required';
                        field.parentElement.appendChild(errorMessage);
                    }
                } else {
                    field.classList.remove('field-error');
                    const errorMessage = field.parentElement.querySelector('.error-message');
                    if (errorMessage) {
                        errorMessage.remove();
                    }
                }
            });
            
            if (hasError) {
                e.preventDefault();
            }
        });
        
        // Clear error styles when field is changed
        const formFields = form.querySelectorAll('input, select, textarea');
        formFields.forEach(field => {
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('field-error');
                    const errorMessage = this.parentElement.querySelector('.error-message');
                    if (errorMessage) {
                        errorMessage.remove();
                    }
                }
            });
        });
    });
    
    /**
     * Responsive navigation enhancements
     */
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    if (mobileMenuToggle) {
        const mobileMenu = document.querySelector('.admin-sidebar');
        
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
        });
    }
});

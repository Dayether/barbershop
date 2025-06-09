/**
 * Admin Dashboard JavaScript
 * For Tipuno Barbershop Admin Panel
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin JS loaded');

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
    });

    /**
     * IziToast delete confirmation
     */
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Prevent the default action
            e.preventDefault();
            console.log('Delete button clicked');
            
            // Get delete URL and item information
            const deleteUrl = this.getAttribute('href');
            const itemName = this.getAttribute('data-item-name') || 'this item';
            const confirmTitle = this.getAttribute('data-confirm-title') || 'Confirm Deletion';
            const confirmMessage = this.getAttribute('data-confirm') || 
                                  `Are you sure you want to delete ${itemName}? This action cannot be undone.`;
            
            console.log('Showing confirmation dialog for:', deleteUrl);

            // Show IziToast confirmation
            iziToast.question({
                timeout: false,
                close: false,
                overlay: true,
                displayMode: 'once',
                id: 'question',
                zindex: 999,
                title: confirmTitle,
                message: confirmMessage,
                position: 'center',
                buttons: [
                    ['<button><b>Yes, Delete</b></button>', function (instance, toast) {
                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        
                        // Add loading indicator
                        iziToast.info({
                            message: 'Deleting...',
                            timeout: 1000
                        });
                        
                        // Redirect with confirmation parameter
                        window.location.href = deleteUrl + (deleteUrl.includes('?') ? '&' : '?') + 'confirm_delete=1';
                    }, true],
                    ['<button>Cancel</button>', function (instance, toast) {
                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                    }]
                ]
            });
        });
    });
    
    /**
     * Image preview for uploads
     */
    const imageInputs = document.querySelectorAll('.image-upload');
    if (imageInputs && imageInputs.length > 0) {
        imageInputs.forEach(input => {
            input.addEventListener('change', function() {
                const previewId = this.dataset.preview;
                const preview = document.getElementById(previewId);
                
                if (preview && this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        if (preview.classList.contains('no-image')) {
                            preview.innerHTML = '';
                            preview.classList.remove('no-image');
                            
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            preview.appendChild(img);
                        } else {
                            const img = preview.querySelector('img') || document.createElement('img');
                            img.src = e.target.result;
                            
                            if (!preview.contains(img)) {
                                preview.appendChild(img);
                            }
                        }
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });
    }
    
    /**
     * Status toggle form submission 
     */
    window.submitStatusForm = function(id) {
        const form = document.getElementById('status-form-' + id);
        if (!form) return;
        
        const statusBadge = form.querySelector('.status-badge');
        
        // Show loading state
        if (statusBadge) {
            const originalHTML = statusBadge.innerHTML;
            statusBadge.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        }

        // Submit the form
            form.submit();
        }
    });
    
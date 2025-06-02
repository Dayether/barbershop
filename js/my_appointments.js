/**
 * My Appointments page functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Improve responsive behavior for appointment cards
    const appointmentCards = document.querySelectorAll('.appointment-card');
    
    // Add data attributes for responsive behavior
    appointmentCards.forEach(card => {
        const status = card.querySelector('.appointment-status')?.textContent.trim().toLowerCase() || '';
        card.setAttribute('data-status', status);
        
        // Set proper heights for card elements
        const cardHeight = card.offsetHeight;
        if (window.innerWidth <= 768) {
            // Reposition status badge on mobile
            const statusBadge = card.querySelector('.appointment-status');
            if (statusBadge) {
                card.insertBefore(statusBadge, card.firstChild);
            }
        }
    });
    
    // Improve filter experience
    const statusFilter = document.getElementById('status-filter');
    const filteredCount = document.getElementById('filtered-count');
    const quickFilterBtns = document.querySelectorAll('.quick-filter-btn');
    
    // Filter function with better animation
    function filterAppointments(filterValue) {
        let visibleCount = 0;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        appointmentCards.forEach(card => {
            // Get appointment date for time-based filtering
            const dateElement = card.querySelector('.date-value');
            const dateText = dateElement ? dateElement.textContent : '';
            const appointmentDate = dateText ? new Date(dateText) : null;
            
            // Get appointment status
            const status = card.getAttribute('data-status');
            
            let shouldShow = false;
            
            switch (filterValue) {
                case 'all':
                    shouldShow = true;
                    break;
                case 'pending':
                case 'confirmed':
                case 'completed':
                case 'cancelled':
                    shouldShow = status === filterValue;
                    break;
                case 'upcoming':
                    shouldShow = appointmentDate && appointmentDate >= today && 
                               (status === 'confirmed' || status === 'pending');
                    break;
                case 'past':
                    shouldShow = appointmentDate && (appointmentDate < today || 
                               status === 'completed' || status === 'cancelled');
                    break;
            }
            
            // Apply smooth animation with proper sizing
            if (shouldShow) {
                card.style.display = 'block';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                    card.style.height = 'auto';
                }, 10);
                visibleCount++;
            } else {
                card.style.opacity = '0';
                card.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        });
        
        // Update the counter
        if (filteredCount) filteredCount.textContent = visibleCount;
        
        // Update active state on quick filter buttons
        quickFilterBtns.forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-filter') === filterValue);
        });
        
        // Update dropdown to match
        if (statusFilter) statusFilter.value = filterValue;
    }
    
    // Fix filter events
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterAppointments(this.value);
        });
    }
    
    // Quick filter buttons
    quickFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterAppointments(this.getAttribute('data-filter'));
        });
    });
    
    // Handle window resize events for responsive adjustments
    window.addEventListener('resize', function() {
        adjustLayoutForScreenSize();
    });
    
    function adjustLayoutForScreenSize() {
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.appointment-date, .appointment-service, .appointment-barber').forEach(item => {
                item.style.paddingRight = '0';
            });
        } else {
            document.querySelectorAll('.appointment-date, .appointment-service, .appointment-barber').forEach(item => {
                item.style.paddingRight = '80px';
            });
        }
    }
    
    // Run responsive adjustments on page load
    adjustLayoutForScreenSize();
    
    // Add smooth animation styles to cards
    appointmentCards.forEach(card => {
        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease';
    });
    
    // Appointment cancellation confirmation
    window.confirmCancelAppointment = function(appointmentId) {
        if (confirm('Are you sure you want to cancel this appointment?')) {
            window.location.href = 'cancel_appointment.php?id=' + appointmentId;
        }
    };
});

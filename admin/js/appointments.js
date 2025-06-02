/**
 * Appointments Management JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Document loaded, initializing appointments page');
    initAppointmentsPage();
});

// Global variables
let allAppointments = [];
let filteredAppointments = [];
let currentPage = 1;
const itemsPerPage = 10;

/**
 * Initialize the appointments page
 */
function initAppointmentsPage() {
    // Show loading overlay
    document.getElementById('loading-overlay').style.display = 'flex';
    
    // Fetch appointments data
    fetchAppointments()
        .then(data => {
            // Store appointments
            allAppointments = data;
            filteredAppointments = [...allAppointments];
            
            // Update stats
            updateStatistics();
            
            // Render appointments in both views
            renderTableView();
            renderCardView();
            
            // Hide loading overlay
            setTimeout(() => {
                document.getElementById('loading-overlay').style.opacity = '0';
                setTimeout(() => {
                    document.getElementById('loading-overlay').style.display = 'none';
                }, 300);
            }, 500);
        })
        .catch(error => {
            console.error('Error fetching appointments:', error);
            document.getElementById('loading-overlay').style.display = 'none';
            showNotification('Failed to load appointments. Please try again.', 'error');
        });
    
    // Set up event listeners
    setupEventListeners();
}

/**
 * Fetch appointments from the server
 */
function fetchAppointments() {
    return new Promise((resolve, reject) => {
        fetch('api/get_appointments.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch appointments');
                }
                return response.json();
            })
            .then(data => resolve(data))
            .catch(error => {
                console.error('Error:', error);
                // For demonstration, generate sample data if API fails
                const sampleData = generateSampleAppointments();
                resolve(sampleData);
            });
    });
}

/**
 * Generate sample appointments for demonstration purposes
 */
function generateSampleAppointments() {
    const statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    const services = ['Classic Haircut', 'Beard Trim', 'Hot Towel Shave', 'Complete Package'];
    const barbers = ['John', 'Michael', 'David', 'Robert'];
    const sampleAppointments = [];
    
    // Generate 25 sample appointments
    for (let i = 1; i <= 25; i++) {
        // Generate random date within +/- 10 days of today
        const date = new Date();
        date.setDate(date.getDate() + Math.floor(Math.random() * 21) - 10);
        
        // Format date as YYYY-MM-DD
        const formattedDate = date.toISOString().split('T')[0];
        
        // Generate random time between 9am and 5pm
        const hour = Math.floor(Math.random() * 8) + 9;
        const minute = Math.random() < 0.5 ? '00' : '30';
        const time = `${hour.toString().padStart(2, '0')}:${minute}`;
        
        sampleAppointments.push({
            id: i,
            client_name: `Client ${i}`,
            client_email: `client${i}@example.com`,
            client_phone: `555-000-${i.toString().padStart(4, '0')}`,
            service: services[Math.floor(Math.random() * services.length)],
            appointment_date: formattedDate,
            appointment_time: time,
            barber: barbers[Math.floor(Math.random() * barbers.length)],
            status: statuses[Math.floor(Math.random() * statuses.length)],
            notes: Math.random() > 0.7 ? 'Some client notes here.' : '',
            profile_pic: 'https://via.placeholder.com/40'
        });
    }
    
    return sampleAppointments;
}

/**
 * Set up event listeners
 */
function setupEventListeners() {
    // View selector buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active state
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
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
    document.getElementById('search-input').addEventListener('input', function() {
        filterAppointments();
    });
    
    // Filters
    document.getElementById('date-filter').addEventListener('change', filterAppointments);
    document.getElementById('barber-filter').addEventListener('change', filterAppointments);
    document.getElementById('service-filter').addEventListener('change', filterAppointments);
    document.getElementById('status-filter').addEventListener('change', filterAppointments);
    
    // Reset filters
    document.getElementById('reset-filters').addEventListener('click', function() {
        document.getElementById('search-input').value = '';
        document.getElementById('date-filter').value = '';
        document.getElementById('barber-filter').value = '';
        document.getElementById('service-filter').value = '';
        document.getElementById('status-filter').value = '';
        
        filterAppointments();
    });
    
    // Add appointment button
    const addAppointmentBtn = document.getElementById('add-appointment-btn');
    if (addAppointmentBtn) {
        addAppointmentBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openAddAppointmentModal();
        });
    }
    
    // Appointment form submission
    const appointmentForm = document.getElementById('appointment-form');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveAppointment();
        });
    } else {
        console.error('Appointment form not found');
    }
    
    // Modal close buttons
    document.querySelectorAll('.close-modal, .btn-secondary').forEach(btn => {
        btn.addEventListener('click', function() {
            closeAllModals();
        });
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeAllModals();
        }
    });
}

/**
 * Filter appointments based on search and filter inputs
 */
function filterAppointments() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const dateFilter = document.getElementById('date-filter').value;
    const barberFilter = document.getElementById('barber-filter').value;
    const serviceFilter = document.getElementById('service-filter').value;
    const statusFilter = document.getElementById('status-filter').value;
    
    // Apply filters
    filteredAppointments = allAppointments.filter(appointment => {
        // Search filter
        const matchesSearch = !searchTerm || 
            appointment.client_name.toLowerCase().includes(searchTerm) || 
            appointment.client_email.toLowerCase().includes(searchTerm) || 
            appointment.client_phone.toLowerCase().includes(searchTerm);
        
        // Date filter
        const matchesDate = !dateFilter || appointment.appointment_date === dateFilter;
        
        // Barber filter
        const matchesBarber = !barberFilter || appointment.barber === barberFilter;
        
        // Service filter
        const matchesService = !serviceFilter || appointment.service === serviceFilter;
        
        // Status filter
        const matchesStatus = !statusFilter || appointment.status === statusFilter;
        
        return matchesSearch && matchesDate && matchesBarber && matchesService && matchesStatus;
    });
    
    // Reset current page
    currentPage = 1;
    
    // Update stats
    updateStatistics();
    
    // Render the filtered appointments
    renderTableView();
    renderCardView();
}

/**
 * Open the modal for adding a new appointment
 */
function openAddAppointmentModal() {
    const modal = document.getElementById('appointment-modal');
    const form = document.getElementById('appointment-form');
    const modalTitle = document.getElementById('modal-title');
    
    if (modal && form && modalTitle) {
        // Reset form
        form.reset();
        
        // Set form action to add
        document.getElementById('form-action').value = 'add';
        document.getElementById('appointment-id').value = '';
        
        // Set default date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('appointment-date').value = today;
        
        // Set default status to pending
        document.getElementById('status').value = 'pending';
        
        // Update modal title
        modalTitle.textContent = 'Add New Appointment';
        
        // Show the modal
        modal.style.display = 'block';
    }
}

/**
 * Save appointment (add or edit)
 */
function saveAppointment() {
    // Show loading overlay
    document.getElementById('loading-overlay').style.display = 'flex';
    
    // Get form data
    const form = document.getElementById('appointment-form');
    if (!form) {
        console.error('Form element not found');
        document.getElementById('loading-overlay').style.display = 'none';
        showNotification('Form not found', 'error');
        return;
    }
    
    const formData = new FormData(form);
    
    // Debug: Log form data being sent to server
    console.log('Submitting appointment with data:', Object.fromEntries(formData));
    
    // Send AJAX request
    fetch('api/save_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        // Check for network errors
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`Server returned ${response.status}: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        // Hide loading overlay
        document.getElementById('loading-overlay').style.display = 'none';
        
        console.log('Server response:', data);
        
        if (data.success) {
            // Close modal
            closeAllModals();
            
            // Show success message
            showNotification(data.message, 'success');
            
            const formAction = formData.get('action');
            
            if (formAction === 'add') {
                // Add new appointment to the beginning of the list
                if (data.appointment) {
                    allAppointments.unshift(data.appointment);
                }
            } else {
                // Update existing appointment in the list
                const appointmentId = parseInt(formData.get('id'));
                const index = allAppointments.findIndex(a => a.id === appointmentId);
                
                if (index !== -1 && data.appointment) {
                    allAppointments[index] = data.appointment;
                }
            }
            
            // Re-filter and render appointments
            filterAppointments();
            
            // Update statistics
            updateStatistics();
        } else {
            // Show error message
            showNotification(data.error || 'Failed to save appointment', 'error');
        }
    })
    .catch(error => {
        // Hide loading overlay
        document.getElementById('loading-overlay').style.display = 'none';
        
        console.error('Error saving appointment:', error);
        showNotification('An error occurred while saving the appointment', 'error');
    });
}

/**
 * Update statistics counters
 * This function updates all the stat counters based on appointment data
 */
function updateStatistics() {
    const today = new Date().toISOString().split('T')[0];
    
    // Count today's appointments
    const todayCount = allAppointments.filter(app => app.appointment_date === today).length;
    
    // Count by status
    const pendingCount = allAppointments.filter(app => app.status === 'pending').length;
    const completedCount = allAppointments.filter(app => app.status === 'completed').length;
    const cancelledCount = allAppointments.filter(app => app.status === 'cancelled').length;
    
    // Update the counters with animation
    animateCounter('today-count', parseInt(document.getElementById('today-count').textContent) || 0, todayCount, 800);
    animateCounter('pending-count', parseInt(document.getElementById('pending-count').textContent) || 0, pendingCount, 800);
    animateCounter('completed-count', parseInt(document.getElementById('completed-count').textContent) || 0, completedCount, 800);
    animateCounter('cancelled-count', parseInt(document.getElementById('cancelled-count').textContent) || 0, cancelledCount, 800);
}

/**
 * Render the table view
 */
function renderTableView() {
    const tableBody = document.getElementById('appointments-table-body');
    const pagination = document.getElementById('table-pagination');
    
    // Calculate pagination
    const totalPages = Math.ceil(filteredAppointments.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredAppointments.length);
    const currentItems = filteredAppointments.slice(startIndex, endIndex);
    
    // Clear the table body
    tableBody.innerHTML = '';
    
    if (filteredAppointments.length === 0) {
        // No results message
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No appointments found</td></tr>';
    } else {
        // Render each appointment row
        currentItems.forEach(appointment => {
            const row = document.createElement('tr');
            
            // Format date for display
            const appointmentDate = new Date(appointment.appointment_date);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            const formattedDate = appointmentDate.toLocaleDateString('en-US', options);
            
            // Check which action buttons should be available based on status
            const canEdit = appointment.status !== 'cancelled' && appointment.status !== 'completed';
            const canCancel = appointment.status !== 'cancelled' && appointment.status !== 'completed';
            const canConfirm = appointment.status === 'pending';
            const canComplete = appointment.status === 'confirmed';
            
            row.innerHTML = `
                <td>
                    <div class="client-info">
                        <img src="${appointment.profile_pic || 'https://via.placeholder.com/40'}" alt="${appointment.client_name}">
                        <div class="client-details">
                            <h4>${escapeHtml(appointment.client_name)}</h4>
                            <p>${escapeHtml(appointment.client_email)}</p>
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(appointment.service)}</td>
                <td>${formattedDate}, ${appointment.appointment_time}</td>
                <td>${escapeHtml(appointment.barber)}</td>
                <td><span class="status-badge ${appointment.status}">${capitalizeFirst(appointment.status)}</span></td>
                <td>
                    <div class="actions">
                        ${canEdit ? `<button class="action-btn edit" title="Edit" onclick="editAppointment(${appointment.id})">
                            <i class="fas fa-edit"></i>
                        </button>` : ''}
                        
                        ${canConfirm ? `<button class="action-btn confirm" title="Confirm" onclick="confirmAppointment(${appointment.id})">
                            <i class="fas fa-check"></i>
                        </button>` : ''}
                        
                        ${canComplete ? `<button class="action-btn complete" title="Complete" onclick="completeAppointment(${appointment.id})">
                            <i class="fas fa-check-double"></i>
                        </button>` : ''}
                        
                        ${canCancel ? `<button class="action-btn cancel" title="Cancel" onclick="cancelAppointment(${appointment.id})">
                            <i class="fas fa-times"></i>
                        </button>` : ''}
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
    const cardContainer = document.getElementById('appointment-cards');
    const pagination = document.getElementById('card-pagination');
    
    // Calculate pagination
    const totalPages = Math.ceil(filteredAppointments.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredAppointments.length);
    const currentItems = filteredAppointments.slice(startIndex, endIndex);
    
    // Clear the card container
    cardContainer.innerHTML = '';
    
    if (filteredAppointments.length === 0) {
        // No results message
        cardContainer.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 20px;">No appointments found</div>';
    } else {
        // Render each appointment card
        currentItems.forEach(appointment => {
            // Format date for display
            const appointmentDate = new Date(appointment.appointment_date);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            const formattedDate = appointmentDate.toLocaleDateString('en-US', options);
            
            // Check which action buttons should be available based on status
            const canEdit = appointment.status !== 'cancelled' && appointment.status !== 'completed';
            const canCancel = appointment.status !== 'cancelled' && appointment.status !== 'completed';
            const canConfirm = appointment.status === 'pending';
            const canComplete = appointment.status === 'confirmed';
            
            const card = document.createElement('div');
            card.className = 'appointment-card';
            
            card.innerHTML = `
                <div class="card-header">
                    <div class="card-date">
                        <i class="far fa-calendar-alt"></i>
                        <span>${formattedDate}, ${appointment.appointment_time}</span>
                    </div>
                    <div class="card-status">
                        <span class="status-badge ${appointment.status}">${capitalizeFirst(appointment.status)}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-client">
                        <img src="${appointment.profile_pic || 'https://via.placeholder.com/50'}" alt="${appointment.client_name}">
                        <div class="card-client-info">
                            <h4>${escapeHtml(appointment.client_name)}</h4>
                            <p>${escapeHtml(appointment.client_email)}</p>
                        </div>
                    </div>
                    <div class="card-detail">
                        <div class="detail-label">Service:</div>
                        <div class="detail-value">${escapeHtml(appointment.service)}</div>
                    </div>
                    <div class="card-detail">
                        <div class="detail-label">Barber:</div>
                        <div class="detail-value">${escapeHtml(appointment.barber)}</div>
                    </div>
                    <div class="card-detail">
                        <div class="detail-label">Phone:</div>
                        <div class="detail-value">${escapeHtml(appointment.client_phone)}</div>
                    </div>
                    ${appointment.notes ? `
                    <div class="card-detail">
                        <div class="detail-label">Notes:</div>
                        <div class="detail-value">${escapeHtml(appointment.notes)}</div>
                    </div>` : ''}
                </div>
                <div class="card-footer">
                    <div class="actions">
                        ${canEdit ? `<button class="action-btn edit" title="Edit" onclick="editAppointment(${appointment.id})">
                            <i class="fas fa-edit"></i>
                        </button>` : ''}
                        
                        ${canConfirm ? `<button class="action-btn confirm" title="Confirm" onclick="confirmAppointment(${appointment.id})">
                            <i class="fas fa-check"></i>
                        </button>` : ''}
                        
                        ${canComplete ? `<button class="action-btn complete" title="Complete" onclick="completeAppointment(${appointment.id})">
                            <i class="fas fa-check-double"></i>
                        </button>` : ''}
                        
                        ${canCancel ? `<button class="action-btn cancel" title="Cancel" onclick="cancelAppointment(${appointment.id})">
                            <i class="fas fa-times"></i>
                        </button>` : ''}
                    </div>
                </div>
            `;
            
            cardContainer.appendChild(card);
        });
    }
    
    // Render pagination
    renderPagination(pagination, totalPages);
}

/**
 * Render pagination controls
 */
function renderPagination(container, totalPages) {
    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Previous button
    html += `<button class="pagination-btn ${currentPage === 1 ? 'disabled' : ''}" 
                    ${currentPage === 1 ? 'disabled' : `onclick="changePage(${currentPage - 1})"`}>
                <i class="fas fa-chevron-left"></i>
            </button>`;
    
    // Page numbers
    const maxButtons = 5;
    const startPage = Math.max(1, Math.min(currentPage - Math.floor(maxButtons / 2), totalPages - maxButtons + 1));
    const endPage = Math.min(totalPages, startPage + maxButtons - 1);
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" 
                        onclick="changePage(${i})">
                    ${i}
                </button>`;
    }
    
    // Next button
    html += `<button class="pagination-btn ${currentPage === totalPages ? 'disabled' : ''}" 
                    ${currentPage === totalPages ? 'disabled' : `onclick="changePage(${currentPage + 1})"`}>
                <i class="fas fa-chevron-right"></i>
            </button>`;
    
    container.innerHTML = html;
}

/**
 * Change the current page
 */
function changePage(page) {
    currentPage = page;
    renderTableView();
    renderCardView();
    
    // Scroll to top of view
    window.scrollTo({
        top: document.querySelector('.filter-section').offsetTop - 20,
        behavior: 'smooth'
    });
}

/**
 * Show the appointment modal for adding or editing
 */
function showAppointmentModal(action, appointmentId = null) {
    const modal = document.getElementById('appointment-modal');
    const form = document.getElementById('appointment-form');
    const modalTitle = document.getElementById('modal-title');
    
    // Reset the form
    form.reset();
    
    if (action === 'add') {
        // Adding a new appointment
        modalTitle.textContent = 'Add New Appointment';
        document.getElementById('appointment-id').value = '';
        document.getElementById('form-action').value = 'add';
        
        // Set today's date as default
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('appointment-date').value = today;
        
    } else if (action === 'edit') {
        // Editing an existing appointment
        modalTitle.textContent = 'Edit Appointment';
        document.getElementById('form-action').value = 'edit';
        
        // Find the appointment
        const appointment = allAppointments.find(a => a.id === appointmentId);
        if (!appointment) {
            showNotification('Appointment not found', 'error');
            return;
        }
        
        // Fill in the form with appointment data
        document.getElementById('appointment-id').value = appointment.id;
        document.getElementById('client-name').value = appointment.client_name;
        document.getElementById('client-email').value = appointment.client_email;
        document.getElementById('client-phone').value = appointment.client_phone;
        document.getElementById('service').value = appointment.service;
        document.getElementById('appointment-date').value = appointment.appointment_date;
        document.getElementById('appointment-time').value = appointment.appointment_time;
        document.getElementById('barber').value = appointment.barber;
        document.getElementById('status').value = appointment.status;
        document.getElementById('notes').value = appointment.notes || '';
    }
    
    // Show the modal
    modal.style.display = 'block';
}

/**
 * Update appointment status (confirm, complete, cancel)
 * @param {number} appointmentId - The ID of the appointment
 * @param {string} status - New status (confirmed, completed, cancelled)
 */
function updateAppointmentStatus(appointmentId, status) {
    // Close any open modals
    closeAllModals();
    
    // Show loading overlay
    document.getElementById('loading-overlay').style.display = 'flex';
    
    // Create form data
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('status', status);
    
    // Send request to API
    fetch('api/update_appointment_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading overlay
        document.getElementById('loading-overlay').style.display = 'none';
        
        if (data.success) {
            // Show success notification
            showNotification(`Appointment ${status} successfully`, 'success');
            
            // Update the appointment in our data arrays
            updateAppointmentInArrays(data.appointment);
            
            // Re-render the views
            renderTableView();
            renderCardView();
            
            // Update statistics
            updateStatistics();
        } else {
            // Show error notification
            showNotification(data.error || 'Failed to update appointment status', 'error');
        }
    })
    .catch(error => {
        // Hide loading overlay
        document.getElementById('loading-overlay').style.display = 'none';
        
        // Show error notification
        console.error('Error updating appointment status:', error);
        showNotification('An error occurred while updating the appointment', 'error');
    });
}

/**
 * Helper function to update appointment in data arrays
 */
function updateAppointmentInArrays(updatedAppointment) {
    // Find and update in allAppointments array
    const allIndex = allAppointments.findIndex(a => a.id === updatedAppointment.id);
    if (allIndex !== -1) {
        allAppointments[allIndex] = {...allAppointments[allIndex], ...updatedAppointment};
    }
    
    // Find and update in filteredAppointments array if it exists there
    const filteredIndex = filteredAppointments.findIndex(a => a.id === updatedAppointment.id);
    if (filteredIndex !== -1) {
        filteredAppointments[filteredIndex] = {...filteredAppointments[filteredIndex], ...updatedAppointment};
    }
}

/**
 * Confirm an appointment
 */
function confirmAppointment(id) {
    const appointment = allAppointments.find(a => a.id === id);
    if (!appointment) {
        showNotification('Appointment not found', 'error');
        return;
    }
    
    const confirmModal = document.getElementById('confirm-modal');
    const confirmMessage = document.getElementById('confirm-message');
    const confirmButton = document.getElementById('confirm-action-btn');
    
    confirmMessage.textContent = `Are you sure you want to confirm this appointment for ${appointment.client_name}?`;
    confirmButton.textContent = 'Confirm Appointment';
    confirmButton.className = 'btn btn-primary';
    
    // Set up the action for the confirm button
    confirmButton.onclick = function() {
        updateAppointmentStatus(id, 'confirmed');
    };
    
    // Show the modal
    confirmModal.style.display = 'block';
}

/**
 * Mark an appointment as completed
 */
function completeAppointment(id) {
    const appointment = allAppointments.find(a => a.id === id);
    if (!appointment) {
        showNotification('Appointment not found', 'error');
        return;
    }
    
    const confirmModal = document.getElementById('confirm-modal');
    const confirmMessage = document.getElementById('confirm-message');
    const confirmButton = document.getElementById('confirm-action-btn');
    
    confirmMessage.textContent = `Are you sure you want to mark this appointment for ${appointment.client_name} as completed?`;
    confirmButton.textContent = 'Mark as Completed';
    confirmButton.className = 'btn btn-success';
    
    // Set up the action for the confirm button
    confirmButton.onclick = function() {
        updateAppointmentStatus(id, 'completed');
    };
    
    // Show the modal
    confirmModal.style.display = 'block';
}

/**
 * Cancel an appointment
 */
function cancelAppointment(id) {
    const appointment = allAppointments.find(a => a.id === id);
    if (!appointment) {
        showNotification('Appointment not found', 'error');
        return;
    }
    
    const confirmModal = document.getElementById('confirm-modal');
    const confirmMessage = document.getElementById('confirm-message');
    const confirmButton = document.getElementById('confirm-action-btn');
    
    confirmMessage.textContent = `Are you sure you want to cancel this appointment for ${appointment.client_name}?`;
    confirmButton.textContent = 'Cancel Appointment';
    confirmButton.className = 'btn btn-danger';
    
    // Set up the action for the confirm button
    confirmButton.onclick = function() {
        updateAppointmentStatus(id, 'cancelled');
    };
    
    // Show the modal
    confirmModal.style.display = 'block';
}

/**
 * Close all modals
 */
function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
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
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        </div>
        <div class="notification-message">${message}</div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Append to body
    document.body.appendChild(notification);
    
    // Add close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.classList.add('hiding');
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
    
    // Show notification with animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.classList.add('hiding');
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

/**
 * Helper function to capitalize the first letter of a string
 */
function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Helper function to escape HTML
 */
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/**
 * Function to fetch appointment statistics from the API
 */
function fetchAppointmentStats() {
    fetch('api/get_appointment_stats.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Update stats counters with animation
            animateCounter('today-count', 0, data.today_count, 1200);
            animateCounter('pending-count', 0, data.pending_count, 1200);
            animateCounter('confirmed-count', 0, data.confirmed_count, 1200);
            animateCounter('completed-count', 0, data.completed_count, 1200);
            animateCounter('cancelled-count', 0, data.cancelled_count, 1200);
        })
        .catch(error => {
            console.error('Error fetching appointment statistics:', error);
            // If error, show default values
            document.getElementById('today-count').textContent = '0';
            document.getElementById('pending-count').textContent = '0';
            document.getElementById('confirmed-count').textContent = '0';
            document.getElementById('completed-count').textContent = '0';
            document.getElementById('cancelled-count').textContent = '0';
        });
}

// Animate counter function
function animateCounter(id, start, end, duration) {
    const element = document.getElementById(id);
    if (!element) return;
    
    let current = start;
    const increment = (end - start) / (duration / 16);
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            element.textContent = end;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current);
        }
        }, 16);
    }

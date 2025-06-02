document.addEventListener('DOMContentLoaded', function() {
    // Initialize the dashboard
    initDashboard();
    
    // Theme toggle functionality
    const themeToggle = document.getElementById('theme-toggle');
    
    // Check for saved theme
    if (localStorage.getItem('barbershop-theme') === 'dark') {
        document.body.classList.add('dark-theme');
        themeToggle.checked = true;
    }
    
    themeToggle.addEventListener('change', function() {
        if (this.checked) {
            document.body.classList.add('dark-theme');
            localStorage.setItem('barbershop-theme', 'dark');
        } else {
            document.body.classList.remove('dark-theme');
            localStorage.setItem('barbershop-theme', 'light');
        }
        
        // Update all charts with new theme
        updateChartsTheme();
    });
    
    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarClose = document.getElementById('sidebar-close');
    const sidebar = document.getElementById('sidebar');
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.add('active');
    });
    
    sidebarClose.addEventListener('click', function() {
        sidebar.classList.remove('active');
    });
    
    // Close sidebar when clicking outside (for mobile)
    document.addEventListener('click', function(e) {
        if (sidebar.classList.contains('active') && 
            !sidebar.contains(e.target) && 
            e.target !== sidebarToggle) {
            sidebar.classList.remove('active');
        }
    });
    
    // Navigation active state
    const navItems = document.querySelectorAll('.sidebar-nav li');
    
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Revenue chart period selector
    const revenueChartPeriod = document.getElementById('revenue-chart-period');
    if (revenueChartPeriod) {
        revenueChartPeriod.addEventListener('change', function() {
            showChartLoading('revenue-chart');
            updateRevenueChartPeriod(this.value);
        });
    }
    
    // Service chart type selector
    const serviceChartType = document.getElementById('service-chart-type');
    if (serviceChartType) {
        serviceChartType.addEventListener('change', function() {
            showChartLoading('service-chart');
            updateServiceChartType(this.value);
        });
    }
    
    // Refresh inventory chart
    const refreshInventory = document.getElementById('refresh-inventory');
    
    if (refreshInventory) {
        refreshInventory.addEventListener('click', function() {
            this.classList.add('rotating');
            
            // Simulate data refresh
            setTimeout(() => {
                updateInventoryChart();
                this.classList.remove('rotating');
            }, 1000);
        });
    }
    
    // Add event listeners for modals
    setupModalEventListeners();
});

function initDashboard() {
    // Show loading effect
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.opacity = '1';
    }
    
    // Fetch dashboard data from the API
    fetch('get_dashboard_data.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Update metrics
            updateMetrics(data);
            
            // Initialize charts with real data
            initRevenueChart(data.weekly_revenue);
            initServiceChart(data.service_breakdown);
            initInventoryChart(data.inventory);
            
            // Fetch recent appointments
            fetchRecentAppointments();
            
            // Hide loading overlay
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 500);
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
            
            // Initialize with default data in case of error
            initRevenueChart();
            initServiceChart();
            initInventoryChart();
            
            // Hide loading overlay
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 500);
            }
            
            // Show error notification
            showNotification('Failed to load dashboard data. Please try again later.', 'error');
        });
    
    // Fetch appointment statistics
    fetchAppointmentStats();
}

// Function to fetch appointment statistics from the API
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
            animateCounter('confirmed-count', 0, data.confirmed_count, 1200);
            animateCounter('pending-count', 0, data.pending_count, 1200);
        })
        .catch(error => {
            console.error('Error fetching appointment statistics:', error);
            // If error, show default values
            document.getElementById('today-count').textContent = '0';
            document.getElementById('confirmed-count').textContent = '0';
            document.getElementById('pending-count').textContent = '0';
        });
}

function updateMetrics(data) {
    // Update Total Bookings
    const totalBookings = document.getElementById('total-bookings');
    if (totalBookings && data.bookings) {
        animateCounter('total-bookings', 0, data.bookings.total, 1500);
        
        // Update percentage change
        const bookingsChange = document.querySelector('.metric-card:nth-child(1) .metric-change');
        if (bookingsChange) {
            const percentChange = data.bookings.percentage_change;
            bookingsChange.textContent = `${percentChange >= 0 ? '+' : ''}${percentChange}% `;
            bookingsChange.classList.remove('positive', 'negative');
            bookingsChange.classList.add(percentChange >= 0 ? 'positive' : 'negative');
            
            const periodSpan = document.createElement('span');
            periodSpan.textContent = 'this week';
            bookingsChange.appendChild(periodSpan);
            
            const icon = document.createElement('i');
            icon.className = percentChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
            bookingsChange.prepend(icon);
        }
    }
    
    // Update Revenue
    const totalRevenue = document.getElementById('total-revenue');
    if (totalRevenue && data.revenue) {
        animateCounter('total-revenue', 0, data.revenue.total, 1500, '$');
        
        // Update percentage change
        const revenueChange = document.querySelector('.metric-card:nth-child(2) .metric-change');
        if (revenueChange) {
            const percentChange = data.revenue.percentage_change;
            revenueChange.textContent = `${percentChange >= 0 ? '+' : ''}${percentChange}% `;
            revenueChange.classList.remove('positive', 'negative');
            revenueChange.classList.add(percentChange >= 0 ? 'positive' : 'negative');
            
            const periodSpan = document.createElement('span');
            periodSpan.textContent = 'this month';
            revenueChange.appendChild(periodSpan);
            
            const icon = document.createElement('i');
            icon.className = percentChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
            revenueChange.prepend(icon);
        }
    }
    
    // Update Products Sold
    const productsSold = document.getElementById('products-sold');
    if (productsSold && data.products) {
        animateCounter('products-sold', 0, data.products.total, 1500);
        
        // Update percentage change
        const productsChange = document.querySelector('.metric-card:nth-child(3) .metric-change');
        if (productsChange) {
            const percentChange = data.products.percentage_change;
            productsChange.textContent = `${percentChange >= 0 ? '+' : ''}${percentChange}% `;
            productsChange.classList.remove('positive', 'negative');
            productsChange.classList.add(percentChange >= 0 ? 'positive' : 'negative');
            
            const periodSpan = document.createElement('span');
            periodSpan.textContent = 'this week';
            productsChange.appendChild(periodSpan);
            
            const icon = document.createElement('i');
            icon.className = percentChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
            productsChange.prepend(icon);
        }
    }
    
    // Update New Clients
    const newClients = document.getElementById('new-clients');
    if (newClients && data.clients) {
        animateCounter('new-clients', 0, data.clients.total, 1500);
        
        // Update percentage change
        const clientsChange = document.querySelector('.metric-card:nth-child(4) .metric-change');
        if (clientsChange) {
            const percentChange = data.clients.percentage_change;
            clientsChange.textContent = `${percentChange >= 0 ? '+' : ''}${percentChange}% `;
            clientsChange.classList.remove('positive', 'negative');
            clientsChange.classList.add(percentChange >= 0 ? 'positive' : 'negative');
            
            const periodSpan = document.createElement('span');
            periodSpan.textContent = 'this month';
            clientsChange.appendChild(periodSpan);
            
            const icon = document.createElement('i');
            icon.className = percentChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
            clientsChange.prepend(icon);
        }
    }
}

// Animate counter from one value to another
function animateCounter(id, start, end, duration) {
    const element = document.getElementById(id);
    if (!element) return;
    
    let current = start;
    const increment = (end - start) / (duration / 16);
    const timer = setInterval(() => {
        current += increment;
        if (current >= end) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current);
    }, 16);
}

// Helper function to get chart colors based on current theme
function getChartColors() {
    const isDarkTheme = document.body.classList.contains('dark-theme');
    
    return {
        textColor: isDarkTheme ? '#e0e0e0' : '#666666',
        gridColor: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
        primaryColor: '#d4af37',
        colors: [
            'rgba(212, 175, 55, 0.7)',
            'rgba(53, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)'
        ]
    };
}

// Update chart themes when switching between light/dark mode
function updateChartsTheme() {
    const colors = getChartColors();
    
    // Update all charts with new theme colors
    if (window.revenueChart) {
        window.revenueChart.options.scales.x.grid.color = colors.gridColor;
        window.revenueChart.options.scales.x.ticks.color = colors.textColor;
        window.revenueChart.options.scales.y.grid.color = colors.gridColor;
        window.revenueChart.options.scales.y.ticks.color = colors.textColor;
        window.revenueChart.update();
    }
    
    if (window.serviceChart) {
        window.serviceChart.options.plugins.legend.labels.color = colors.textColor;
        window.serviceChart.update();
    }
    
    if (window.inventoryChart) {
        window.inventoryChart.options.scales.x.grid.color = colors.gridColor;
        window.inventoryChart.options.scales.x.ticks.color = colors.textColor;
        window.inventoryChart.options.scales.y.grid.color = colors.gridColor;
        window.inventoryChart.options.scales.y.ticks.color = colors.textColor;
        window.inventoryChart.update();
    }
}

function initRevenueChart(data = null) {
    const ctx = document.getElementById('revenue-chart');
    if (!ctx) return;
    
    const colors = getChartColors();
    
    // Use provided data or default data
    const chartData = {
        labels: data ? data.labels : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        datasets: [{
            label: 'Revenue',
            data: data ? data.data : [650, 750, 620, 820, 950, 1200, 980],
            backgroundColor: 'rgba(212, 175, 55, 0.2)',
            borderColor: colors.primaryColor,
            borderWidth: 2,
            tension: 0.3,
            pointBackgroundColor: colors.primaryColor
        }]
    };
    
    const config = {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return `Revenue: $${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: true,
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor,
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    };
    
    window.revenueChart = new Chart(ctx, config);
}

function updateRevenueChart(period) {
    if (!window.revenueChart) return;
    
    // Show loading overlay
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.opacity = '1';
    }
    
    // Fetch data for the selected period
    fetch(`get_dashboard_data.php?period=${period}`)
        .then(response => response.json())
        .then(data => {
            // Update chart data
            window.revenueChart.data.labels = data.weekly_revenue.labels;
            window.revenueChart.data.datasets[0].data = data.weekly_revenue.data;
            window.revenueChart.update();
            
            // Hide loading overlay
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 500);
            }
        })
        .catch(error => {
            console.error('Error fetching revenue data:', error);
            
            // Hide loading overlay
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                }, 500);
            }
            
            // Show error notification
            showNotification('Failed to update revenue chart. Please try again later.', 'error');
        });
}

// Update revenue chart based on selected period
function updateRevenueChartPeriod(period) {
    if (!window.revenueChart) return;
    
    // Fetch data for the selected period
    fetch(`get_chart_data.php?chart=revenue&period=${period}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Update chart with new data
            window.revenueChart.data.labels = data.labels;
            window.revenueChart.data.datasets[0].data = data.data;
            window.revenueChart.update();
            hideChartLoading('revenue-chart');
        })
        .catch(error => {
            console.error('Error updating revenue chart:', error);
            hideChartLoading('revenue-chart');
            
            // Use fallback data
            const fallbackData = getFallbackRevenueData(period);
            window.revenueChart.data.labels = fallbackData.labels;
            window.revenueChart.data.datasets[0].data = fallbackData.data;
            window.revenueChart.update();
            
            // Show error notification
            showNotification('Failed to update revenue chart with server data. Showing sample data instead.', 'error');
        });
}

// Fallback data for revenue chart if the API call fails
function getFallbackRevenueData(period) {
    switch(period) {
        case 'week':
            return {
                labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                data: [650, 750, 620, 820, 950, 1200, 980]
            };
        case 'month':
            return {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                data: [3200, 3800, 4100, 4500]
            };
        case 'year':
            return {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                data: [12500, 13200, 14800, 13900, 15200, 16500, 17200, 18100, 17800, 19200, 20500, 22000]
            };
        default:
            return {
                labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                data: [650, 750, 620, 820, 950, 1200, 980]
            };
    }
}

function initServiceChart(data = null) {
    const ctx = document.getElementById('service-chart');
    if (!ctx) return;
    
    const colors = getChartColors();
    
    // Use provided data or default data
    const chartData = {
        labels: data ? data.labels : ['Haircut', 'Beard Trim', 'Hair Styling', 'Shaving', 'Hair Color'],
        datasets: [{
            data: data ? data.data : [40, 25, 15, 10, 10],
            backgroundColor: colors.colors,
            borderColor: colors.colors.map(color => color.replace('0.7', '1')),
            borderWidth: 1
        }]
    };
    
    const config = {
        type: 'pie',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: colors.textColor,
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw;
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${percentage}% (${value} appointments)`;
                        }
                    }
                }
            }
        }
    };
    
    window.serviceChart = new Chart(ctx, config);
}

// Function to update service chart type (pie or doughnut)
function updateServiceChartType(type) {
    if (!window.serviceChart) return;
    
    // Store the current data and options
    const currentData = window.serviceChart.data;
    const currentOptions = window.serviceChart.options;
    
    // Destroy the current chart
    window.serviceChart.destroy();
    
    // Create a new chart with the new type
    const ctx = document.getElementById('service-chart').getContext('2d');
    
    window.serviceChart = new Chart(ctx, {
        type: type,
        data: currentData,
        options: currentOptions
    });
    
    // Hide loading indicator
    hideChartLoading('service-chart');
}

function initInventoryChart(data = null) {
    const ctx = document.getElementById('inventory-chart');
    if (!ctx) return;
    
    const colors = getChartColors();
    
    // Use provided data or default data
    const products = data ? data.labels : [
        'Styling Pomade',
        'Beard Oil',
        'Hair Wax',
        'Shaving Cream',
        'Hair Shampoo',
        'Conditioner'
    ];
    
    const currentStock = data ? data.stock : [28, 32, 15, 22, 18, 12];
    const reorderLevel = data ? data.reorder_levels : [10, 15, 8, 12, 10, 8];
    
    const chartData = {
        labels: products,
        datasets: [
            {
                label: 'Current Stock',
                data: currentStock,
                backgroundColor: colors.colors[1],
                borderColor: colors.colors[1].replace('0.7', '1'),
                borderWidth: 1
            },
            {
                label: 'Reorder Level',
                data: reorderLevel,
                backgroundColor: colors.colors[2],
                borderColor: colors.colors[2].replace('0.7', '1'),
                borderWidth: 1
            }
        ]
    };
    
    const config = {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: colors.textColor
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor
                    }
                }
            }
        }
    };
    
    window.inventoryChart = new Chart(ctx, config);
}

function updateInventoryChart() {
    if (!window.inventoryChart) return;
    
    // Generate new random data for the demo
    const newStockData = window.inventoryChart.data.datasets[0].data.map(
        value => Math.max(5, Math.floor(value + (Math.random() * 10) - 3))
    );
    
    // Update with animation
    window.inventoryChart.data.datasets[0].data = newStockData;
    window.inventoryChart.update('active');
}

// Add a CSS class for rotation animation
document.head.insertAdjacentHTML('beforeend', `
<style>
.rotating {
    animation: rotate 1s linear;
}
@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
`);

// Helper function to show notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;
    
    document.body.appendChild(notification);
    
    // Add close button functionality
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
    
    // Show notification with animation
    setTimeout(() => {
        notification.classList.add('show');
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }, 10);
}

// Function to fetch recent appointments
function fetchRecentAppointments() {
    fetch('get_recent_appointments.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Update the appointments table
            updateAppointmentsTable(data);
        })
        .catch(error => {
            console.error('Error fetching appointments:', error);
            
            // Show error message in the table
            const appointmentsTableBody = document.getElementById('appointments-table-body');
            if (appointmentsTableBody) {
                appointmentsTableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">Failed to load appointments. Please try again later.</td>
                    </tr>
                `;
            }
            
            // Show error notification
            showNotification('Failed to load appointments. Please try again later.', 'error');
        });
}

// Function to update the appointments table with data
function updateAppointmentsTable(appointments) {
    const appointmentsTableBody = document.getElementById('appointments-table-body');
    if (!appointmentsTableBody) return;
    
    if (appointments.length === 0) {
        appointmentsTableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">No appointments found.</td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    appointments.forEach(appointment => {
        // Check if the appointment can have actions performed on it
        const canEdit = appointment.status !== 'cancelled' && appointment.status !== 'completed';
        const canConfirm = appointment.status === 'pending';
        const canDelete = appointment.status !== 'cancelled' && appointment.status !== 'completed';
        
        html += `
            <tr data-id="${appointment.id}" data-ref="${appointment.booking_reference}">
                <td>
                    <div class="client-info">
                        <img src="${appointment.profile_pic}" alt="Client">
                        <span>${appointment.client_name}</span>
                    </div>
                </td>
                <td>${appointment.service}</td>
                <td>${appointment.date}, ${appointment.time}</td>
                <td>${appointment.barber || 'Not assigned'}</td>
                <td><span class="status ${appointment.status}">${appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}</span></td>
                <td>
                    <div class="actions">
                        ${canEdit ? `<button class="action-btn edit" title="Edit" onclick="openEditModal(${appointment.id})">
                            <i class="fas fa-edit"></i>
                        </button>` : ''}
                        
                        ${canConfirm ? `<button class="action-btn confirm" title="Confirm" onclick="confirmAppointment(${appointment.id})">
                            <i class="fas fa-check"></i>
                        </button>` : ''}
                        
                        ${canDelete ? `<button class="action-btn delete" title="Cancel" onclick="deleteAppointment(${appointment.id})">
                            <i class="fas fa-times"></i>
                        </button>` : ''}
                    </div>
                </td>
            </tr>
        `;
    });
    
    appointmentsTableBody.innerHTML = html;
}

// Functions for handling appointment actions
function openEditModal(appointmentId) {
    // Fetch appointment details first
    fetch(`get_recent_appointments.php?id=${appointmentId}`)
        .then(response => response.json())
        .then(appointments => {
            if (appointments.length === 0) {
                showNotification('Appointment not found', 'error');
                return;
            }
            
            const appointment = appointments[0];
            
            // Populate the form fields
            document.getElementById('edit-appointment-id').value = appointment.id;
            document.getElementById('edit-client-name').value = appointment.client_name;
            document.getElementById('edit-service').value = appointment.service;
            document.getElementById('edit-appointment-date').value = formatDateForInput(appointment.date);
            document.getElementById('edit-appointment-time').value = appointment.time;
            document.getElementById('edit-barber').value = appointment.barber;
            document.getElementById('edit-client-email').value = appointment.client_email;
            document.getElementById('edit-client-phone').value = appointment.client_phone;
            document.getElementById('edit-notes').value = appointment.notes;
            
            // Show the modal
            const modal = document.getElementById('edit-appointment-modal');
            modal.style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching appointment details:', error);
            showNotification('Failed to load appointment details', 'error');
        });
}

function confirmAppointment(appointmentId) {
    // Show confirmation modal
    const confirmModal = document.getElementById('confirm-modal');
    const confirmMessage = document.getElementById('confirm-message');
    const confirmActionBtn = document.getElementById('confirm-action-btn');
    
    confirmMessage.textContent = 'Are you sure you want to confirm this appointment?';
    confirmModal.style.display = 'block';
    
    // Set up the confirm button action
    confirmActionBtn.onclick = function() {
        // Close the modal
        confirmModal.style.display = 'none';
        
        // Send the confirmation request
        const formData = new FormData();
        formData.append('id', appointmentId);
        formData.append('action', 'confirm');
        
        fetch('appointment_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                fetchRecentAppointments(); // Refresh appointments
            } else {
                showNotification(data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error confirming appointment:', error);
            showNotification('Failed to confirm appointment', 'error');
        });
    };
}

function deleteAppointment(appointmentId) {
    // Show confirmation modal
    const confirmModal = document.getElementById('confirm-modal');
    const confirmMessage = document.getElementById('confirm-message');
    const confirmActionBtn = document.getElementById('confirm-action-btn');
    
    confirmMessage.textContent = 'Are you sure you want to cancel this appointment?';
    confirmModal.style.display = 'block';
    
    // Set up the confirm button action
    confirmActionBtn.onclick = function() {
        // Close the modal
        confirmModal.style.display = 'none';
        
        // Send the delete request
        const formData = new FormData();
        formData.append('id', appointmentId);
        formData.append('action', 'delete');
        
        fetch('appointment_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                fetchRecentAppointments(); // Refresh appointments
            } else {
                showNotification(data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error cancelling appointment:', error);
            showNotification('Failed to cancel appointment', 'error');
        });
    };
}

// Set up modal event listeners
function setupModalEventListeners() {
    // Close modals when clicking the X or outside the modal
    document.querySelectorAll('.close-modal, .close-modal-btn').forEach(element => {
        element.addEventListener('click', function() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        document.querySelectorAll('.modal').forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Handle edit appointment form submission
    const editForm = document.getElementById('edit-appointment-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(editForm);
            
            fetch('appointment_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide the modal and show success message
                    document.getElementById('edit-appointment-modal').style.display = 'none';
                    showNotification(data.message, 'success');
                    
                    // Refresh the appointments list
                    fetchRecentAppointments();
                } else {
                    showNotification(data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Error updating appointment:', error);
                showNotification('Failed to update appointment', 'error');
            });
        });
    }
}

// Function to show loading animation for a specific chart
function showChartLoading(chartId) {
    const chartCanvas = document.getElementById(chartId);
    if (!chartCanvas) return;
    
    // Add a loading overlay just for this chart
    const chartContainer = chartCanvas.closest('.chart-container');
    if (chartContainer) {
        // Check if loading overlay already exists
        let chartLoading = chartContainer.querySelector('.chart-loading');
        if (!chartLoading) {
            chartLoading = document.createElement('div');
            chartLoading.className = 'chart-loading';
            chartLoading.innerHTML = `
                <div class="spinner-small"></div>
                <span>Updating...</span>
            `;
            chartContainer.querySelector('.chart-body').appendChild(chartLoading);
        } else {
            chartLoading.style.display = 'flex';
        }
    }
}

// Function to hide loading animation for a specific chart
function hideChartLoading(chartId) {
    const chartCanvas = document.getElementById(chartId);
    if (!chartCanvas) return;
    
    const chartContainer = chartCanvas.closest('.chart-container');
    if (chartContainer) {
        const chartLoading = chartContainer.querySelector('.chart-loading');
        if (chartLoading) {
            chartLoading.style.display = 'none';
        }
    }
}

// Helper function to format date for input field (YYYY-MM-DD)
function formatDateForInput(dateString) {
    if (dateString === 'Today') {
        return new Date().toISOString().split('T')[0];
    } else if (dateString === 'Tomorrow') {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        return tomorrow.toISOString().split('T')[0];
    } else {
        // Parse the date string (e.g., "May 28, 2025")
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
    }
}

<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    // Not logged in, redirect to login page
    header('Location: ../login.php');
    exit;
}

// Check if user is an admin (account_type = 1)
if (!isset($_SESSION['user']['account_type']) || $_SESSION['user']['account_type'] != 1) {
    // Not an admin, redirect to homepage
    header('Location: ../index.php');
    exit;
}

// Get admin user data for display
$admin = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbershop Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Add loading overlay at the top of the body -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
        <p>Loading dashboard data...</p>
    </div>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-cut"></i>
                    <h2>Tipuno</h2>
                </div>
                <button id="sidebar-close" class="sidebar-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="index.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="appointments.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Appointments</span>
                        </a>
                    </li>
                    <li>
                        <a href="services.php">
                            <i class="fas fa-cut"></i>
                            <span>Services</span>
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i class="fas fa-chart-bar"></i>
                            <span>Sales Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button id="sidebar-toggle" class="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Dashboard</h1>
                </div>
                
                <div class="header-right">
                    <div class="theme-switch-wrapper">
                        <label class="theme-switch" for="theme-toggle">
                            <input type="checkbox" id="theme-toggle">
                            <div class="slider round">
                                <i class="fas fa-sun"></i>
                                <i class="fas fa-moon"></i>
                            </div>
                        </label>
                    </div>
                    
                    <div class="user-profile">
                        <img src="<?= htmlspecialchars($admin['profile_pic']) ?>" alt="User Profile">
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($admin['name']) ?></span>
                            <span class="user-role">Administrator</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Overview Cards -->
                <section class="metrics-section">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="metric-details">
                            <h3>Total Bookings</h3>
                            <h2 id="total-bookings">128</h2>
                            <p class="metric-change positive">+12.5% <span>this week</span></p>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="metric-details">
                            <h3>Revenue</h3>
                            <h2 id="total-revenue">$4,582</h2>
                            <p class="metric-change positive">+8.2% <span>this month</span></p>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="metric-details">
                            <h3>Products Sold</h3>
                            <h2 id="products-sold">64</h2>
                            <p class="metric-change negative">-3.8% <span>this week</span></p>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="metric-details">
                            <h3>New Clients</h3>
                            <h2 id="new-clients">36</h2>
                            <p class="metric-change positive">+24.1% <span>this month</span></p>
                        </div>
                    </div>
                </section>

                <!-- Charts Section -->
                <section class="charts-section">
                    <div class="chart-row">
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3>Weekly Revenue</h3>
                                <div class="chart-actions">
                                    <select id="revenue-chart-period">
                                        <option value="week">This Week</option>
                                        <option value="month">This Month</option>
                                        <option value="year">This Year</option>
                                    </select>
                                </div>
                            </div>
                            <div class="chart-body">
                                <canvas id="revenue-chart"></canvas>
                            </div>
                        </div>
                        
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3>Service Breakdown</h3>
                                <div class="chart-actions">
                                    <select id="service-chart-type">
                                        <option value="pie">Pie Chart</option>
                                        <option value="doughnut">Doughnut</option>
                                    </select>
                                </div>
                            </div>
                            <div class="chart-body">
                                <canvas id="service-chart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="chart-row">
                        <div class="chart-container full-width">
                            <div class="chart-header">
                                <h3>Product Inventory Status</h3>
                                <div class="chart-actions">
                                    <button id="refresh-inventory" class="refresh-btn">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="chart-body">
                                <canvas id="inventory-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Recent Appointments -->
                <section class="appointments-section">
                    <div class="section-header">
                        <h3>Recent Appointments</h3>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    
                    <div class="appointments-table">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Service</th>
                                        <th>Date & Time</th>
                                        <th>Barber</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="appointments-table-body">
                                    <!-- Appointments will be loaded dynamically -->
                                    <tr>
                                        <td colspan="6" class="text-center">Loading appointments...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Edit Appointment Modal -->
    <div id="edit-appointment-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Appointment</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="edit-appointment-form">
                    <input type="hidden" id="edit-appointment-id" name="id">
                    <input type="hidden" name="action" value="edit">
                    
                    <div class="form-group">
                        <label for="edit-client-name">Client Name</label>
                        <input type="text" id="edit-client-name" name="client_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-service">Service</label>
                        <select id="edit-service" name="service" class="form-control" required>
                            <option value="Classic Haircut">Classic Haircut</option>
                            <option value="Beard Trim">Beard Trim</option>
                            <option value="Hot Towel Shave">Hot Towel Shave</option>
                            <option value="Complete Package">Complete Package</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="edit-appointment-date">Date</label>
                            <input type="date" id="edit-appointment-date" name="appointment_date" class="form-control" required>
                        </div>
                        <div class="form-group half">
                            <label for="edit-appointment-time">Time</label>
                            <input type="time" id="edit-appointment-time" name="appointment_time" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-barber">Barber</label>
                        <select id="edit-barber" name="barber" class="form-control" required>
                            <option value="John">John</option>
                            <option value="Michael">Michael</option>
                            <option value="David">David</option>
                            <option value="Robert">Robert</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-client-email">Email</label>
                        <input type="email" id="edit-client-email" name="client_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-client-phone">Phone</label>
                        <input type="tel" id="edit-client-phone" name="client_phone" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-notes">Notes</label>
                        <textarea id="edit-notes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmation</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p id="confirm-message">Are you sure you want to perform this action?</p>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
                    <button type="button" id="confirm-action-btn" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>

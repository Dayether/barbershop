<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['account_type']) || $_SESSION['user']['account_type'] != 1) {
    header('Location: ../login.php');
    exit;
}

// Get admin user data for display
$admin = $_SESSION['user'];

// Connect to database
require_once '../includes/db_connection.php';

// Get barbers for dropdown
$barbers_query = "SELECT DISTINCT name FROM barbers WHERE active = 1 ORDER BY name";
$barbers_result = $conn->query($barbers_query);
$barbers = [];
while ($row = $barbers_result->fetch_assoc()) {
    $barbers[] = $row['name'];
}

// Get services for dropdown
$services_query = "SELECT DISTINCT name FROM services WHERE active = 1 ORDER BY name";
$services_result = $conn->query($services_query);
$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - Barbershop Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/appointments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Loading overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
        <p>Loading appointments...</p>
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
                    <li>
                        <a href="index.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
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
                    <h1 class="page-title">Appointment Management</h1>
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

            <!-- Content -->
            <div class="content-wrapper">
                <!-- Page Header with Stats -->
                <div class="page-header">
                    <div class="stats-cards">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Today's Appointments</h3>
                                <h2 id="today-count">0</h2>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon yellow">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Pending</h3>
                                <h2 id="pending-count">0</h2>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Completed</h3>
                                <h2 id="completed-count">0</h2>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon red">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Cancelled</h3>
                                <h2 id="cancelled-count">0</h2>
                            </div>
                        </div>
                    </div>
                    
                    <button id="add-appointment-btn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Appointment
                    </button>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="search-input" placeholder="Search by client name, email or phone...">
                    </div>
                    
                    <div class="filters">
                        <div class="filter-group">
                            <label for="date-filter">Date</label>
                            <input type="date" id="date-filter" class="form-control">
                        </div>
                        
                        <div class="filter-group">
                            <label for="barber-filter">Barber</label>
                            <select id="barber-filter" class="form-control">
                                <option value="">All Barbers</option>
                                <?php foreach($barbers as $barber): ?>
                                <option value="<?= htmlspecialchars($barber) ?>"><?= htmlspecialchars($barber) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="service-filter">Service</label>
                            <select id="service-filter" class="form-control">
                                <option value="">All Services</option>
                                <?php foreach($services as $service): ?>
                                <option value="<?= htmlspecialchars($service) ?>"><?= htmlspecialchars($service) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="status-filter">Status</label>
                            <select id="status-filter" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <button id="reset-filters" class="btn btn-secondary">
                            <i class="fas fa-sync-alt"></i> Reset Filters
                        </button>
                    </div>
                </div>
                
                <!-- View Selector -->
                <div class="view-selector">
                    <button class="view-btn active" data-view="table"><i class="fas fa-list"></i> Table View</button>
                    <button class="view-btn" data-view="card"><i class="fas fa-th-large"></i> Card View</button>
                </div>

                <!-- Table View -->
                <div class="table-view" id="table-view">
                    <div class="table-responsive">
                        <table class="appointments-table">
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
                                <!-- Will be populated with JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination" id="table-pagination">
                        <!-- Pagination controls will be populated with JavaScript -->
                    </div>
                </div>

                <!-- Card View -->
                <div class="card-view" id="card-view" style="display:none;">
                    <div class="appointment-cards" id="appointment-cards">
                        <!-- Will be populated with JavaScript -->
                    </div>
                    <div class="pagination" id="card-pagination">
                        <!-- Pagination controls will be populated with JavaScript -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Appointment Modal -->
    <div id="appointment-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Add New Appointment</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="appointment-form">
                    <input type="hidden" id="appointment-id" name="id">
                    <input type="hidden" id="form-action" name="action" value="add">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="client-name">Client Name*</label>
                            <input type="text" id="client-name" name="client_name" required>
                        </div>
                        <div class="form-group">
                            <label for="client-email">Email*</label>
                            <input type="email" id="client-email" name="client_email" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="client-phone">Phone*</label>
                            <input type="tel" id="client-phone" name="client_phone" required>
                        </div>
                        <div class="form-group">
                            <label for="service">Service*</label>
                            <select id="service" name="service" required>
                                <option value="">Select Service</option>
                                <?php foreach($services as $service): ?>
                                <option value="<?= htmlspecialchars($service) ?>"><?= htmlspecialchars($service) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="appointment-date">Date*</label>
                            <input type="date" id="appointment-date" name="appointment_date" required>
                        </div>
                        <div class="form-group">
                            <label for="appointment-time">Time*</label>
                            <input type="time" id="appointment-time" name="appointment_time" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="barber">Barber*</label>
                            <select id="barber" name="barber" required>
                                <option value="">Select Barber</option>
                                <?php foreach($barbers as $barber): ?>
                                <option value="<?= htmlspecialchars($barber) ?>"><?= htmlspecialchars($barber) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" id="status-group">
                            <label for="status">Status*</label>
                            <select id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Appointment</button>
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
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    <button type="button" id="confirm-action-btn" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/appointments.js"></script>
    <script>
        // Debug console output
        console.log('Appointments page loaded');
        
        // Add error handler for AJAX requests
        window.addEventListener('error', function(e) {
            console.error('Global error caught:', e);
        });
        
        // Fetch appointment statistics when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM content loaded - fetching stats');
            fetchAppointmentStats();
        });
    </script>
</body>
</html>
</html>

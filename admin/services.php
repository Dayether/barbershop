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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management - Barbershop Admin</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/services.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Loading overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
        <p>Loading services...</p>
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
                    <li>
                        <a href="appointments.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Appointments</span>
                        </a>
                    </li>
                    <li class="active">
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
                    <h1 class="page-title">Service Management</h1>
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
                <!-- Page Header with Stats and Add Button -->
                <div class="page-header">
                    <div class="stats-cards">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-cut"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Total Services</h3>
                                <h2 id="total-services">0</h2>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-toggle-on"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Active Services</h3>
                                <h2 id="active-services">0</h2>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon yellow">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-content">
                                <h3>Avg. Price</h3>
                                <h2 id="avg-price">$0</h2>
                            </div>
                        </div>
                    </div>
                    
                    <button id="add-service-btn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Service
                    </button>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="search-input" placeholder="Search services...">
                    </div>
                    
                    <div class="filters">
                        <div class="filter-group">
                            <label for="status-filter">Status</label>
                            <select id="status-filter" class="form-control">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="price-filter">Price Range</label>
                            <select id="price-filter" class="form-control">
                                <option value="">All Prices</option>
                                <option value="0-25">$0 - $25</option>
                                <option value="25-50">$25 - $50</option>
                                <option value="50-100">$50 - $100</option>
                                <option value="100+">$100+</option>
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
                        <table class="services-table">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Description</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="services-table-body">
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
                    <div class="services-grid" id="services-grid">
                        <!-- Will be populated with JavaScript -->
                    </div>
                    <div class="pagination" id="card-pagination">
                        <!-- Pagination controls will be populated with JavaScript -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Service Modal -->
    <div id="service-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Add New Service</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="service-form">
                    <input type="hidden" id="service-id" name="id">
                    <input type="hidden" id="form-action" name="action" value="add">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="service-name">Service Name*</label>
                            <input type="text" id="service-name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="service-price">Price ($)*</label>
                            <input type="number" id="service-price" name="price" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="service-duration">Duration (minutes)*</label>
                            <input type="number" id="service-duration" name="duration" min="5" step="5" required>
                        </div>
                        <div class="form-group">
                            <label for="service-status">Status*</label>
                            <select id="service-status" name="active" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="service-description">Description</label>
                        <textarea id="service-description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="service-image">Service Image</label>
                        <div class="image-upload">
                            <input type="file" id="service-image" name="image" accept="image/*">
                            <label for="service-image" class="upload-label">
                                <i class="fas fa-cloud-upload-alt"></i> Choose Image
                            </label>
                            <span class="file-name">No file chosen</span>
                        </div>
                        <div id="image-preview" class="image-preview"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Service</button>
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
                    <button type="button" id="confirm-action-btn" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
    <script src="js/services.js"></script>
</body>
</html>

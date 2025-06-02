<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connection.php';

$user_id = $_SESSION['user']['id'];

// Get appointments for this user - optimized query with better structure
$appointments = [];

// First, check if we need to filter by status from query params
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$where_clause = "WHERE a.user_id = ?";
$params = [$user_id];
$types = "i";

// Add status filter if provided
if ($status_filter && in_array($status_filter, ['pending', 'confirmed', 'completed', 'cancelled'])) {
    $where_clause .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Build query with proper indexing hints and join optimization
$query = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes, 
          s.name AS service_name, s.duration, s.price, s.id AS service_id,
          b.name AS barber_name, b.id AS barber_id
          FROM appointments a 
          LEFT JOIN services s ON a.service = s.id 
          LEFT JOIN barbers b ON a.barber = b.id 
          $where_clause
          ORDER BY 
            CASE 
                WHEN a.status = 'pending' OR a.status = 'confirmed' THEN 0
                ELSE 1
            END,
            CASE 
                WHEN a.appointment_date >= CURDATE() THEN 0
                ELSE 1
            END,
            a.appointment_date ASC, 
            a.appointment_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}
$stmt->close();

// Get upcoming appointments count for highlighting
$upcoming_count = 0;
$today = date('Y-m-d');
foreach ($appointments as $appointment) {
    if (($appointment['status'] == 'confirmed' || $appointment['status'] == 'pending') && 
        $appointment['appointment_date'] >= $today) {
        $upcoming_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="description" content="Manage your Tipuno Barbershop appointments - view, reschedule, or cancel your booking in just a few clicks.">
    <meta name="keywords" content="barbershop appointments, haircut booking, manage appointments">
    <meta property="og:title" content="My Appointments - Tipuno Barbershop">
    <meta property="og:description" content="Manage your barbershop appointments with ease.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://tipunobarbershop.com/my_appointments.php">
    <meta property="og:image" content="https://tipunobarbershop.com/images/og-image.jpg">
    
    <title>My Appointments - Tipuno Barbershop</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/appointments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Preconnect to external resources for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Structured data for better SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "My Appointments - Tipuno Barbershop",
        "description": "Manage your barbershop appointments",
        "publisher": {
            "@type": "Organization",
            "name": "Tipuno Barbershop",
            "logo": "https://tipunobarbershop.com/images/logo.png"
        }
    }
    </script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>My Appointments</h1>
            <p>View and manage your scheduled appointments</p>
        </div>
    </section>

    <section class="profile-page">
        <div class="container">
            <!-- Add navigation tabs back to profile sections -->
            <div class="profile-tabs">
                <a href="profile.php" class="tab">
                    <i class="fas fa-user"></i> <span>Profile Information</span>
                </a>
                <a href="profile.php#security" class="tab">
                    <i class="fas fa-lock"></i> <span>Security</span>
                </a>
                <a href="my_appointments.php" class="tab active">
                    <i class="fas fa-calendar-alt"></i> <span>My Appointments</span>
                </a>
                <a href="profile.php#orders" class="tab">
                    <i class="fas fa-shopping-bag"></i> <span>My Orders</span>
                </a>
            </div>
            
            <div class="appointments-container">
                <?php if (empty($appointments)): ?>
                    <!-- Empty state display -->
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h3>No Appointments Yet</h3>
                        <p>You don't have any scheduled appointments at the moment.</p>
                        <a href="appointment.php" class="btn btn-primary">Book an Appointment</a>
                    </div>
                <?php else: ?>
                    <!-- Enhanced appointment filters -->
                    <div class="appointments-filter">
                        <div class="filter-group">
                            <div class="filter-wrapper">
                                <label for="status-filter"><i class="fas fa-filter"></i> Filter:</label>
                                <div class="select-container">
                                    <select id="status-filter" class="form-control">
                                        <option value="all">All Appointments</option>
                                        <option value="upcoming">Upcoming</option>
                                        <option value="pending">Pending</option>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <i class="fas fa-chevron-down select-arrow"></i>
                                </div>
                            </div>
                            <div class="appointments-count">
                                Showing <span id="filtered-count" class="count-badge"><?= count($appointments) ?></span> of <span class="total-count"><?= count($appointments) ?></span>
                            </div>
                        </div>
                        <div class="quick-filters">
                            <button class="quick-filter-btn active" data-filter="all">All</button>
                            <button class="quick-filter-btn" data-filter="upcoming">Upcoming</button>
                            <button class="quick-filter-btn" data-filter="completed">Completed</button>
                            <button class="quick-filter-btn" data-filter="cancelled">Cancelled</button>
                        </div>
                    </div>
                    
                    <div class="appointments-list">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-status <?php echo strtolower($appointment['status']); ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </div>
                                <div class="appointment-date">
                                    <div class="date-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="date-details">
                                        <span class="date-label">Date & Time</span>
                                        <span class="date-value"><?php echo date('l, F j, Y', strtotime($appointment['appointment_date'])); ?></span>
                                        <span class="time-value"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></span>
                                    </div>
                                </div>
                                <div class="appointment-service">
                                    <div class="service-icon">
                                        <i class="fas fa-cut"></i>
                                    </div>
                                    <div class="service-details">
                                        <span class="service-label">Service</span>
                                        <span class="service-value"><?php echo htmlspecialchars($appointment['service_name'] ?? 'N/A'); ?></span>
                                        <span class="duration-price">
                                            <?php if(!is_null($appointment['duration'])): ?>
                                            <span class="duration"><?php echo $appointment['duration']; ?> min</span> | 
                                            <?php endif; ?>
                                            <?php if(!is_null($appointment['price'])): ?>
                                            <span class="price">$<?php echo number_format($appointment['price'], 2); ?></span>
                                            <?php else: ?>
                                            <span class="price">Price not set</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="appointment-barber">
                                    <div class="barber-icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div class="barber-details">
                                        <span class="barber-label">Barber</span>
                                        <span class="barber-value"><?php echo htmlspecialchars($appointment['barber_name'] ?? 'Not assigned'); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($appointment['status'] == 'confirmed' || $appointment['status'] == 'pending'): ?>
                                    <div class="appointment-actions">
                                        <a href="reschedule_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline">
                                            <i class="fas fa-calendar-plus"></i> Reschedule
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger" onclick="confirmCancelAppointment(<?php echo $appointment['id']; ?>)">
                                            <i class="fas fa-times-circle"></i> Cancel
                                        </a>
                                    </div>
                                <?php elseif ($appointment['status'] == 'completed'): ?>
                                    <div class="appointment-actions">
                                        <a href="book_again.php?service_id=<?php echo $appointment['service_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-redo"></i> Book Again
                                        </a>
                                        <a href="review.php?appointment_id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline">
                                            <i class="fas fa-star"></i> Leave Review
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($appointment['notes'])): ?>
                                    <div class="appointment-notes">
                                        <strong>Notes:</strong> <?php echo htmlspecialchars($appointment['notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="appointments-actions">
                        <a href="appointment.php" class="btn btn-primary">Book New Appointment</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="js/my_appointments.js"></script>
</body>
</html>

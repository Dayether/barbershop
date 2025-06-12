<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once 'includes/db_connection.php';

$user_id = $_SESSION['user']['user_id'];

// Handle appointment cancellation (set status to 'cancelled' instead of deleting)
if (isset($_GET['cancel_id']) && is_numeric($_GET['cancel_id'])) {
    $cancel_id = intval($_GET['cancel_id']);
    // Only allow cancelling user's own pending/confirmed appointments
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ? AND user_id = ? AND status IN ('pending', 'confirmed')");
    $stmt->bind_param("ii", $cancel_id, $user_id);
    $stmt->execute();
    $stmt->close();
    // Optionally, add to appointment_history
    // $conn->query("INSERT INTO appointment_history (appointment_id, action, notes, user_id) VALUES ($cancel_id, 'cancel', 'Cancelled by user', $user_id)");
    header("Location: my_appointments.php?cancelled=1");
    exit;
}

// Handle appointment deletion (user can delete cancelled appointments)
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Only allow deleting user's own cancelled appointments
    $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ? AND user_id = ? AND status = 'cancelled'");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $stmt->close();
    // Optionally, also delete related appointment_history entries
    // $conn->query("DELETE FROM appointment_history WHERE appointment_id = $delete_id");
    header("Location: my_appointments.php?deleted=1");
    exit;
}

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
$query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.notes, 
          s.name AS service_name, s.duration, s.price, s.service_id,
          b.name AS barber_name, b.barber_id
          FROM appointments a 
          LEFT JOIN services s ON a.service_id = s.service_id 
          LEFT JOIN barbers b ON a.barber_id = b.barber_id 
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
                <a href="orders.php" class="tab">
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
                                        <a href="reschedule_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline">
                                            <i class="fas fa-calendar-plus"></i> Reschedule
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger" onclick="confirmCancelAppointment(<?php echo $appointment['appointment_id']; ?>)">
                                            <i class="fas fa-times-circle"></i> Cancel
                                        </a>
                                    </div>
                                <?php elseif ($appointment['status'] == 'completed'): ?>
                                    <div class="appointment-actions">
                                        <a href="book_again.php?service_id=<?php echo $appointment['service_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-redo"></i> Book Again
                                        </a>
                                        <a href="review.php?appointment_id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-outline">
                                            <i class="fas fa-star"></i> Leave Review
                                        </a>
                                    </div>
                                <?php elseif ($appointment['status'] == 'cancelled'): ?>
                                    <div class="appointment-actions">
                                        <a href="#" class="btn btn-sm btn-danger" onclick="confirmDeleteAppointment(<?php echo $appointment['appointment_id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enable dropdown and quick filter functionality
        const statusFilter = document.getElementById('status-filter');
        const quickFilterBtns = document.querySelectorAll('.quick-filter-btn');
        const appointmentCards = document.querySelectorAll('.appointment-card');
        const filteredCount = document.getElementById('filtered-count');
        const totalCount = document.querySelector('.total-count');
        const today = new Date().toISOString().slice(0, 10);

        function filterAppointments(filter) {
            let count = 0;
            appointmentCards.forEach(card => {
                const status = card.querySelector('.appointment-status').textContent.trim().toLowerCase();
                const date = card.querySelector('.date-value').textContent;
                let show = false;

                if (filter === 'all') {
                    show = true;
                } else if (filter === 'upcoming') {
                    // Compare appointment date to today
                    const cardDate = new Date(card.querySelector('.date-value').textContent);
                    show = (status === 'pending' || status === 'confirmed') && cardDate >= new Date(today);
                } else {
                    show = status === filter;
                }

                card.style.display = show ? '' : 'none';
                if (show) count++;
            });
            filteredCount.textContent = count;
        }

        // Dropdown filter
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                const filter = this.value;
                quickFilterBtns.forEach(btn => btn.classList.toggle('active', btn.dataset.filter === filter));
                filterAppointments(filter);
            });
        }

        // Quick filter buttons
        quickFilterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                quickFilterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const filter = this.dataset.filter;
                if (statusFilter) statusFilter.value = filter;
                filterAppointments(filter);
            });
        });

        // Initialize filter to "all"
        filterAppointments('all');

        // Add confirmCancelAppointment function
        window.confirmCancelAppointment = function(appointmentId) {
            if (confirm('Are you sure you want to cancel this appointment? This action cannot be undone.')) {
                window.location.href = 'my_appointments.php?cancel_id=' + encodeURIComponent(appointmentId);
            }
        };

        // Add confirmDeleteAppointment function
        window.confirmDeleteAppointment = function(appointmentId) {
            if (confirm('Are you sure you want to permanently delete this cancelled appointment?')) {
                window.location.href = 'my_appointments.php?delete_id=' + encodeURIComponent(appointmentId);
            }
        };
    });
    </script>
    <?php if (isset($_GET['cancelled'])): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        iziToast.success({
            title: 'Cancelled',
            message: 'Appointment cancelled successfully.',
            icon: 'fas fa-check-circle'
        });
    });
    </script>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        iziToast.success({
            title: 'Deleted',
            message: 'Appointment deleted successfully.',
            icon: 'fas fa-check-circle'
        });
    });
    </script>
    <?php endif; ?>
</body>
</html>

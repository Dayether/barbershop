<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once 'database.php';

$success_message = '';
$error_message = '';
$password_success = '';
$password_error = '';

$db = new Database();
$user_id = $_SESSION['user']['user_id'];

// Always fetch the latest user data from the database after update
$user = $db->getUserById($user_id);

// Profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = $user['email']; // Email is not editable here

        $result = $db->updateUserProfileFull([
            'user_id' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone
        ]);
        if ($result['success']) {
            // Refresh user data from DB after update
            $user = $db->getUserById($user_id);
            $_SESSION['user']['first_name'] = $user['first_name'];
            $_SESSION['user']['last_name'] = $user['last_name'];
            $_SESSION['user']['phone'] = $user['phone'];
            $success_message = 'Profile updated successfully!';
        } else {
            $error_message = 'Failed to update profile: ' . $result['error_message'];
        }
    }

    // Password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        // Remove the method_exists() check and just call the method directly
        $result = $db->changeUserPassword($user_id, $current_password, $new_password, $confirm_password);
        if ($result['success']) {
            $password_success = 'Password changed successfully!';
        } else {
            $password_error = $result['error_message'];
        }
    }

    // Profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $result = $db->updateUserProfilePicture($user_id, $_FILES['profile_pic'], $_SESSION['user']['profile_pic'] ?? null);
        if ($result['success']) {
            $_SESSION['user']['profile_pic'] = $result['profile_pic'];
            $success_message = 'Profile picture updated successfully!';
        } else {
            $error_message = $result['error_message'];
        }
    }
}

$user = $_SESSION['user'];

// Fetch user appointments
$appointments = $db->getUserAppointments($user_id);

// Fetch user orders (with item count)
$orders = $db->getUserOrdersSummary($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/footer.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add iziToast CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1>My Profile</h1>
            <p>Manage your account and personal information</p>
        </div>
    </section>

    <section class="profile-page">
        <div class="container">
            <div class="profile-tabs">
                <div class="tab" data-tab="profile-info">
                    <i class="fas fa-user"></i> <span>Profile Information</span>
                </div>
                <div class="tab" data-tab="security">
                    <i class="fas fa-lock"></i> <span>Security</span>
                </div>
                <a href="my_appointments.php" class="tab">
                    <i class="fas fa-calendar-alt"></i> <span>My Appointments</span>
                </a>
                <a href="orders.php" class="tab" id="orders-tab">
                    <i class="fas fa-shopping-bag"></i> <span>My Orders</span>
                </a>
                <a href="my_messages.php" class="tab" data-tab="messages">
                    <i class="fas fa-envelope"></i> <span>My Messages</span>
                    
                </a>
            </div>
            
            <div class="profile-content">
                <!-- Profile Information Tab -->
                <div class="tab-content" id="profile-info-content">
                    <div class="profile-card">
                        <div class="card-header">
                            <h2>Profile Information</h2>
                            <p>Update your personal details and profile picture</p>
                        </div>
                        
                        <div class="profile-container">
                            <div class="profile-header">
                                <div class="profile-picture-container">
                                    <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile Picture" class="profile-picture" id="profile-pic-preview">
                                    <label for="profile_pic" class="edit-picture">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                </div>
                                <div class="profile-details">
                                    <h2>
                                        <?php
                                        if (!empty($user['first_name']) || !empty($user['last_name'])) {
                                            echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
                                        } else {
                                            echo 'No name set';
                                        }
                                        ?>
                                    </h2>
                                    <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>
                                    <p class="member-since">
                                        <i class="fas fa-clock"></i> Member since:
                                        <?php
                                        if (!empty($user['created_at'])) {
                                            $date = new DateTime($user['created_at']);
                                            echo $date->format('M d, Y');
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            
                            <form method="post" enctype="multipart/form-data" class="profile-form">
                                <input type="file" id="profile_pic" name="profile_pic" accept="image/*" style="display:none;" onchange="previewImage()">
                                
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-envelope input-icon"></i>
                                        <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                        <div class="input-note">Email cannot be changed</div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <div class="input-icon-wrapper">
                                        <i class="fas fa-phone input-icon"></i>
                                        <input type="tel" id="phone" name="phone" value="<?= isset($user['phone']) ? htmlspecialchars($user['phone']) : '' ?>">
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Security Tab -->
                <div class="tab-content" id="security-content">
                    <div class="profile-card">
                        <div class="card-header">
                            <h2>Security Settings</h2>
                            <p>Manage your password and account security</p>
                        </div>
                        
                        <form method="post" class="password-form">
                            <div class="form-group">
                                <label for="current-password">Current Password</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" id="current-password" name="current_password" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="new-password">New Password</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-key input-icon"></i>
                                    <input type="password" id="new-password" name="new_password" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm-password">Confirm New Password</label>
                                <div class="input-icon-wrapper">
                                    <i class="fas fa-key input-icon"></i>
                                    <input type="password" id="confirm-password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="password-requirements">
                                <h4>Password Requirements:</h4>
                                <ul>
                                    <li><i class="fas fa-check-circle"></i> At least 8 characters long</li>
                                    <li><i class="fas fa-check-circle"></i> Contain at least 1 uppercase letter</li>
                                    <li><i class="fas fa-check-circle"></i> Contain at least 1 number</li>
                                </ul>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </div>
                        </form>
                        
                        <div class="account-deletion">
                            <h3>Account Deletion</h3>
                            <p>Want to delete your account? This action cannot be undone.</p>
                            <a href="#" class="btn btn-danger" onclick="confirmDeletion()">
                                <i class="fas fa-trash"></i> Delete Account
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Appointments Tab -->
                <div class="tab-content" id="appointments-content">
                    <div class="profile-card">
                        <div class="card-header">
                            <h2>My Appointments</h2>
                            <p>View and manage your scheduled appointments</p>
                        </div>
                        <div class="appointments-container">
                            <?php if (empty($appointments)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-calendar-times"></i>
                                    </div>
                                    <h3>No Appointments Yet</h3>
                                    <p>You don't have any scheduled appointments at the moment.</p>
                                    <a href="appointment.php" class="btn btn-primary">Book an Appointment</a>
                                </div>
                            <?php else: ?>
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
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($appointment['notes'])): ?>
                                                <div class="appointment-notes">
                                                    <strong>Notes:</strong> <?php echo htmlspecialchars($appointment['notes']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($appointments)): ?>
                                <div class="appointments-actions">
                                    <a href="appointment.php" class="btn btn-primary">Book New Appointment</a>
                                    <a href="my_appointments.php" class="btn btn-secondary">View All Appointments</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Tab -->
                <div class="tab-content active" id="orders-content">
                    <div class="profile-card">
                        <div class="card-header">
                            <h2>My Orders</h2>
                            <p>View your order history and track current orders</p>
                        </div>
                        
                        <div class="orders-container">
                            <?php if (empty($orders)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-shopping-bag"></i>
                                    </div>
                                    <h3>No Orders Yet</h3>
                                    <p>You haven't placed any orders yet. Check out our shop for premium grooming products.</p>
                                    <a href="shop.php" class="btn btn-primary">Visit Shop</a>
                                </div>
                            <?php else: ?>
                                <div class="orders-filter">
                                    <div class="filter-label">Filter by:</div>
                                    <div class="filter-options">
                                        <button class="filter-btn active" data-filter="all">All Orders</button>
                                        <button class="filter-btn" data-filter="processing">Processing</button>
                                        <button class="filter-btn" data-filter="shipped">Shipped</button>
                                        <button class="filter-btn" data-filter="delivered">Delivered</button>
                                    </div>
                                </div>
                                
                                <div class="orders-list">
                                    <?php foreach ($orders as $order): ?>
                                        <div class="order-card" data-status="<?php echo strtolower($order['status']); ?>">
                                            <div class="order-header">
                                                <div class="order-id">
                                                    <h4>Order #<?php echo $order['order_id']; ?></h4>
                                                    <span class="order-date"><?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
                                                </div>
                                                <div class="order-status-badge <?php echo strtolower($order['status']); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="order-details">
                                                <div class="order-info">
                                                    <div class="info-item">
                                                        <span class="info-label"><i class="fas fa-box"></i> Items</span>
                                                        <span class="info-value"><?php echo $order['item_count']; ?></span>
                                                    </div>
                                                    <div class="info-item">
                                                        <span class="info-label"><i class="fas fa-money-bill-wave"></i> Total</span>
                                                        <span class="info-value">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                                    </div>
                                                    <?php if (!empty($order['tracking_number'])): ?>
                                                    <div class="info-item">
                                                        <span class="info-label"><i class="fas fa-truck"></i> Tracking</span>
                                                        <span class="info-value"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="order-actions">
                                                    <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                    <?php if ($order['status'] === 'shipped'): ?>
                                                    <a href="track_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-map-marker-alt"></i> Track Order
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if ($order['status'] === 'delivered'): ?>
                                                    <a href="reorder.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-redo"></i> Order Again
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="orders-pagination">
                                    <a href="orders.php" class="btn btn-secondary">View All Orders</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Messages Tab -->
                <div class="tab-content" id="messages-content">
                    <div class="profile-card">
                        <div class="card-header">
                            <h2>My Messages</h2>
                            <p>View your sent messages and see replies from our team.</p>
                        </div>
                        <div class="messages-list">
                            <?php
                            // Fetch user's contact messages (reuse logic from my_messages.php)
                            $stmt = $db->conn->prepare("SELECT * FROM contact_messages WHERE user_id = ? ORDER BY created_at DESC");
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $messages = [];
                            while ($row = $result->fetch_assoc()) {
                                $messages[] = $row;
                            }
                            $stmt->close();
                            ?>
                            <?php if (count($messages) === 0): ?>
                                <div class="empty-state">
                                    <i class="fas fa-envelope-open"></i>
                                    <p>You have not sent any messages yet.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="user-message-card" style="background:#fff;padding:20px;margin-bottom:20px;border-radius:8px;box-shadow:0 1px 5px rgba(0,0,0,0.05);">
                                        <div class="msg-header" style="display:flex;justify-content:space-between;align-items:center;">
                                            <div>
                                                <strong>Subject:</strong> <?php echo htmlspecialchars($msg['subject']); ?>
                                            </div>
                                            <span class="status-badge status-<?php echo $msg['status']; ?>">
                                                <?php echo ucfirst($msg['status']); ?>
                                            </span>
                                        </div>
                                        <div class="msg-meta" style="color:#888;font-size:0.9em;margin-bottom:10px;">
                                            <i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($msg['created_at'])); ?>
                                            <i class="fas fa-clock" style="margin-left:10px;"></i> <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                                        </div>
                                        <div class="msg-content" style="margin-bottom:10px;">
                                            <strong>Your Message:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        </div>
                                        <?php if (!empty($msg['reply'])): ?>
                                            <div class="msg-reply" style="background:#f6f6f6;padding:12px;border-radius:6px;margin-top:10px;">
                                                <strong>Reply from Admin/Employee:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($msg['reply'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="msg-reply" style="color:#888;margin-top:10px;">
                                                <em>No reply yet.</em>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Add iziToast JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize iziToast
            iziToast.settings({
                timeout: 5000,
                resetOnHover: true,
                position: 'topRight',
                transitionIn: 'flipInX',
                transitionOut: 'flipOutX',
            });
            
            <?php if ($success_message): ?>
            iziToast.success({
                title: 'Success',
                message: '<?= htmlspecialchars($success_message) ?>',
                icon: 'fas fa-check-circle'
            });
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            iziToast.error({
                title: 'Error',
                message: '<?= htmlspecialchars($error_message) ?>',
                icon: 'fas fa-exclamation-circle'
            });
            <?php endif; ?>
            
            <?php if ($password_success): ?>
            iziToast.success({
                title: 'Password Updated',
                message: '<?= htmlspecialchars($password_success) ?>',
                icon: 'fas fa-key'
            });
            <?php endif; ?>
            
            <?php if ($password_error): ?>
            iziToast.error({
                title: 'Password Error',
                message: '<?= htmlspecialchars($password_error) ?>',
                icon: 'fas fa-lock'
            });
            <?php endif; ?>
            
            // Tab functionality
            const tabs = document.querySelectorAll('.profile-tabs .tab:not([href])');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // Check if URL has a hash for direct navigation to specific tabs
            function activateTabFromHash() {
                if (window.location.hash) {
                    const hash = window.location.hash.substring(1); // Remove the # character
                    
                    // Remove active class from all tabs and contents
                    document.querySelectorAll('.profile-tabs .tab').forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Find and activate the targeted tab
                    const targetTab = document.querySelector(`.tab[data-tab="${hash}"]`);
                    if (targetTab) {
                        targetTab.classList.add('active');
                        const contentId = `${hash}-content`;
                        const content = document.getElementById(contentId);
                        if (content) {
                            content.classList.add('active');
                            
                            // Show toast notification for tab change
                            let toastTitle = 'Profile';
                            let toastIcon = 'fas fa-user';
                            
                            switch(hash) {
                                case 'security':
                                    toastTitle = 'Security Settings';
                                    toastIcon = 'fas fa-lock';
                                    break;
                                case 'appointments':
                                    toastTitle = 'My Appointments';
                                    toastIcon = 'fas fa-calendar-alt';
                                    break;
                                case 'orders':
                                    toastTitle = 'My Orders';
                                    toastIcon = 'fas fa-shopping-bag';
                                    break;
                            }
                            
                            iziToast.info({
                                title: toastTitle,
                                message: 'Viewing ' + toastTitle,
                                icon: toastIcon,
                                iconColor: '#2a9d8f'
                            });
                        }
                    }
                }
            }
            
            // Call on page load
            activateTabFromHash();
            
            // Listen for hash changes
            window.addEventListener('hashchange', activateTabFromHash);
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    // If this is a link (has href attribute), let the default behavior happen
                    if (this.hasAttribute('href')) {
                        return;
                    }
                    
                    e.preventDefault();
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    document.querySelectorAll('.profile-tabs .tab').forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to current tab and content
                    this.classList.add('active');
                    document.getElementById(tabId + '-content').classList.add('active');
                    
                    // Update URL hash without scrolling
                    history.pushState(null, null, '#' + tabId);
                    
                    // Show toast notification for tab change
                    let toastTitle = 'Profile Information';
                    let toastIcon = 'fas fa-user';
                    
                    switch(tabId) {
                        case 'security':
                            toastTitle = 'Security Settings';
                            toastIcon = 'fas fa-lock';
                            break;
                        case 'appointments':
                            toastTitle = 'My Appointments';
                            toastIcon = 'fas fa-calendar-alt';
                            break;
                        case 'orders':
                            toastTitle = 'My Orders';
                            toastIcon = 'fas fa-shopping-bag';
                            break;
                    }
                    
                    iziToast.info({
                        title: toastTitle,
                        message: 'Viewing ' + toastTitle,
                        icon: toastIcon,
                        iconColor: '#2a9d8f',
                        timeout: 2000
                    });
                });
            });
            
            // Fix for Orders tab navigation - ensure it's marked active before navigation
            const ordersTab = document.getElementById('orders-tab');
            if (ordersTab) {
                ordersTab.addEventListener('click', function(e) {
                    // Set active class immediately before navigation
                    document.querySelectorAll('.profile-tabs .tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Store active tab in localStorage so orders.php knows which tab was clicked
                    localStorage.setItem('activeTab', 'orders');
                    
                    iziToast.info({
                        title: 'My Orders',
                        message: 'Loading order details...',
                        icon: 'fas fa-shopping-bag',
                        iconColor: '#2a9d8f'
                    });
                });
            }
            
            // Initialize - select profile tab if no other tab is active
            if (!window.location.hash) {
                const profileTab = document.querySelector('.tab[data-tab="profile-info"]');
                if (profileTab) {
                    profileTab.click();
                }
            }
            
            // Image preview
            window.previewImage = function() {
                const file = document.getElementById('profile_pic').files[0];
                const preview = document.getElementById('profile-pic-preview');
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        
                        iziToast.info({
                            title: 'Profile Picture',
                            message: 'New image selected. Save changes to update your profile',
                            icon: 'fas fa-camera',
                            iconColor: '#2a9d8f'
                        });
                    }
                    reader.readAsDataURL(file);
                }
            };
            
            // Form submission handlers with iziToast notifications
            const profileForm = document.querySelector('.profile-form');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    // Show processing state
                    iziToast.info({
                        title: 'Processing',
                        message: 'Updating your profile...',
                        icon: 'fas fa-spinner fa-spin',
                        timeout: false,
                        id: 'profile-update-toast'
                    });
                });
            }
            
            const passwordForm = document.querySelector('.password-form');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    const newPassword = document.getElementById('new-password').value;
                    const confirmPassword = document.getElementById('confirm-password').value;
                    
                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        iziToast.error({
                            title: 'Password Error',
                            message: 'New passwords do not match',
                            icon: 'fas fa-times-circle'
                        });
                        return false;
                    }
                    
                    // Simple password strength validation
                    if (newPassword.length < 8) {
                        e.preventDefault();
                        iziToast.warning({
                            title: 'Weak Password',
                            message: 'Password should be at least 8 characters long',
                            icon: 'fas fa-exclamation-triangle'
                        });
                        return false;
                    }
                    
                    // Show processing state
                    iziToast.info({
                        title: 'Processing',
                        message: 'Updating your password...',
                        icon: 'fas fa-spinner fa-spin',
                        timeout: false,
                        id: 'password-update-toast'
                    });
                });
            }
            
            // Profile picture upload trigger
            const editPictureBtn = document.querySelector('.edit-picture');
            if (editPictureBtn) {
                editPictureBtn.addEventListener('click', function() {
                    document.getElementById('profile_pic').click();
                });
            }
            
            // Account deletion confirmation
            window.confirmDeletion = function() {
                iziToast.question({
                    timeout: 20000,
                    close: false,
                    overlay: true,
                    displayMode: 'once',
                    id: 'question',
                    zindex: 999,
                    title: 'Confirm Deletion',
                    message: 'Are you sure you want to delete your account? This action cannot be undone.',
                    position: 'center',
                    icon: 'fas fa-exclamation-triangle',
                    buttons: [
                        ['<button><b>Yes</b></button>', function (instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            
                            iziToast.info({
                                title: 'Account Deletion',
                                message: 'Please contact customer support to complete account deletion.',
                                icon: 'fas fa-info-circle',
                                position: 'center',
                                timeout: 8000
                            });
                        }, true],
                        ['<button>No</button>', function (instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        }],
                    ]
                });
            };
            
            // Appointment cancellation
            window.confirmCancelAppointment = function(appointmentId) {
                iziToast.question({
                    timeout: 20000,
                    close: false,
                    overlay: true,
                    displayMode: 'once',
                    id: 'question',
                    zindex: 999,
                    title: 'Cancel Appointment',
                    message: 'Are you sure you want to cancel this appointment?',
                    position: 'center',
                    buttons: [
                        ['<button><b>Yes, Cancel It</b></button>', function (instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                            
                            // Show loading toast
                            iziToast.info({
                                title: 'Processing',
                                message: 'Cancelling your appointment...',
                                icon: 'fas fa-spinner fa-spin',
                                timeout: false,
                                id: 'cancel-appointment-toast'
                            });
                            
                            // Redirect
                            window.location.href = 'cancel_appointment.php?id=' + appointmentId;
                        }, true],
                        ['<button>No, Keep It</button>', function (instance, toast) {
                            instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        }],
                    ]
                });
            };
            
            // Orders filtering with toast notifications
            const filterBtns = document.querySelectorAll('.filter-btn');
            const orderCards = document.querySelectorAll('.order-card');
            
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterBtns.forEach(b => b.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get filter value
                    const filterValue = this.getAttribute('data-filter');
                    
                    // Show toast about filtering
                    let filterText = 'All Orders';
                    let filterIcon = 'fas fa-filter';
                    
                    switch(filterValue) {
                        case 'processing':
                            filterText = 'Processing Orders';
                            filterIcon = 'fas fa-spinner';
                            break;
                        case 'shipped':
                            filterText = 'Shipped Orders';
                            filterIcon = 'fas fa-truck';
                            break;
                        case 'delivered':
                            filterText = 'Delivered Orders';
                            filterIcon = 'fas fa-box-open';
                            break;
                    }
                    
                    iziToast.info({
                        title: 'Filter Applied',
                        message: 'Showing ' + filterText,
                        icon: filterIcon,
                        iconColor: '#2a9d8f',
                        timeout: 2000
                    });
                    
                    // Show/hide order cards based on filter
                    let visibleCount = 0;
                    orderCards.forEach(card => {
                        if (filterValue === 'all' || card.getAttribute('data-status') === filterValue) {
                            card.style.display = 'block';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    // Show message if no orders match filter
                    if (visibleCount === 0 && filterValue !== 'all') {
                        iziToast.info({
                            title: 'No Orders Found',
                            message: 'No ' + filterText.toLowerCase() + ' to display',
                            icon: 'fas fa-info-circle',
                            timeout: 3000
                        });
                    }
                });
            });

            // Check URL parameters for success/error messages
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.get('password_reset') === 'success') {
                iziToast.success({
                    title: 'Password Reset',
                    message: 'Your password has been successfully reset',
                    icon: 'fas fa-key'
                });
            }
            
            if (urlParams.get('profile_updated') === 'success') {
                iziToast.success({
                    title: 'Profile Updated',
                    message: 'Your profile has been successfully updated',
                    icon: 'fas fa-user-check'
                });
            }
            
            if (urlParams.get('appointment_cancelled') === 'success') {
                iziToast.success({
                    title: 'Appointment Cancelled',
                    message: 'Your appointment has been successfully cancelled',
                    icon: 'fas fa-calendar-times'
                });
            }
        });
    </script>
</body>
</html>

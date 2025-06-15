<?php
require_once '../database.php';
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/notifications.php';

$db = new Database();
$user_id = $_SESSION['user']['user_id'];
$errors = [];
$successMsg = '';
$redirect_needed = false;

// Get user data
$user = $db->getUserById($user_id);

// Handle profile update
if (isset($_POST['update_profile'])) {
    $profileData = [
        'user_id' => $user_id,
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone'] ?? '')
    ];
    $result = $db->updateUserProfileFull($profileData);
    if ($result['success']) {
        $_SESSION['user']['first_name'] = $profileData['first_name'];
        $_SESSION['user']['last_name'] = $profileData['last_name'];
        $_SESSION['user']['email'] = $profileData['email'];
        setSuccessToast('Profile updated successfully!');
        header("Location: admin_index.php?page=profile");
        exit();
    } else {
        setErrorToast($result['error_message']);
    }
}

// Handle profile picture upload
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
    $result = $db->updateUserProfilePicture($user_id, $_FILES['profile_pic'], $user['profile_pic']);
    if ($result['success']) {
        $_SESSION['user']['profile_pic'] = $result['profile_pic'];
        $successMsg = 'Profile picture updated successfully!';
        $redirect_needed = true;
    } else {
        $errors[] = $result['error_message'];
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $result = $db->changeUserPassword($user_id, $_POST['current_password'], $_POST['new_password'], $_POST['confirm_password']);
    if ($result['success']) {
        setSuccessToast('Password changed successfully!');
        header("Location: admin_index.php?page=profile");
        exit();
    } else {
        setErrorToast($result['error_message']);
    }
}

// Handle profile picture delete
if (isset($_GET['delete_picture']) && $_GET['delete_picture'] === '1') {
    $result = $db->deleteUserProfilePicture($user_id, $user['profile_pic']);
    if ($result['success']) {
        $_SESSION['user']['profile_pic'] = $result['profile_pic'];
        $successMsg = 'Profile picture removed successfully!';
        $redirect_needed = true;
    } else {
        $errors[] = $result['error_message'];
    }
}

// Redirect if needed
if ($redirect_needed) {
    header("Location: admin_index.php?page=profile");
    exit();
}
?>

<!-- Profile Page Content -->
<div class="admin-content-header">
    <h2><i class="fas fa-user-circle"></i> My Profile</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin_index.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profile</li>
        </ol>
    </nav>
</div>

<!-- Admin Content Area -->
<div class="admin-content">
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="dismiss-alert"><i class="fas fa-times"></i></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($successMsg)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $successMsg; ?>
    </div>
    <?php endif; ?>

    <div class="profile-container">
        <div class="row">
            <div class="col-lg-4">
                <div class="profile-sidebar">
                    <div class="profile-image-container">
                        <div class="profile-image">
                            
                            <img src="<?php echo !empty($user['profile_pic']) ? '../' . $user['profile_pic'] : '../uploads/default-profile.jpg'; ?>" alt="Profile Picture">
                            
                        </div>
                    </div>
                    <div class="profile-details">
                        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p class="role"><span class="badge">Administrator</span></p>
                        <p class="email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                        <?php if (!empty($user['phone'])): ?>
                            <p class="phone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                        <?php endif; ?>
                        <div class="profile-meta">
                            <p><i class="fas fa-clock"></i> Member since: 
                                <?php 
                                $date = new DateTime($user['created_at'] ?? date('Y-m-d H:i:s'));
                                echo $date->format('M d, Y');
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="profile-tabs">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="edit-tab" data-toggle="tab" href="#edit" role="tab">
                                <i class="fas fa-user-edit"></i> Edit Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="security-tab" data-toggle="tab" href="#security" role="tab">
                                <i class="fas fa-lock"></i> Security
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- Edit Profile Tab -->
                        <div class="tab-pane fade show active" id="edit" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Edit Profile Information</h4>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="first_name">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="last_name">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="phone">Phone Number</label>
                                            <input type="text" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        </div>
                                        <div class="form-actions">
                                            <button type="submit" name="update_profile" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Profile
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Change Password</h4>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="current_password">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="new_password">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <small class="text-muted">Password must be at least 6 characters long</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="confirm_password">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        <div class="form-actions">
                                            <button type="submit" name="change_password" class="btn btn-danger">
                                                <i class="fas fa-key"></i> Change Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-body">
                                    <h4 class="card-title">Account Information</h4>
                                    <p><i class="fas fa-user-shield"></i> Account Type: 
                                        <?php echo ($user['account_type'] == '1') ? 'Administrator' : 'User'; ?>
                                    </p>
                                    <p><i class="fas fa-calendar-alt"></i> Registration Date: 
                                        <?php 
                                        $date = new DateTime($user['created_at']);
                                        echo $date->format('M d, Y h:i A'); 
                                        ?>
                                    </p>
                                    <p><i class="fas fa-desktop"></i> IP Address: <?php echo $_SERVER['REMOTE_ADDR']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when profile picture is selected
    document.getElementById('profile_pic').addEventListener('change', function() {
        document.getElementById('avatar-form').submit();
    });
    
    // Tab switching functionality
    const tabLinks = document.querySelectorAll('.nav-link');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and panes
            tabLinks.forEach(tab => tab.classList.remove('active'));
            tabPanes.forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // Add active class to current tab
            this.classList.add('active');
            
            // Get target pane and show it
            const targetId = this.getAttribute('href').substring(1);
            const targetPane = document.getElementById(targetId);
            targetPane.classList.add('show', 'active');
        });
    });
    
    // Dismiss alert
    const dismissBtns = document.querySelectorAll('.dismiss-alert');
    dismissBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const alert = this.parentElement;
            alert.style.opacity = '0';
            setTimeout(() => alert.style.display = 'none', 300);
        });
    });
    
    // Remove the custom IziToast code for the remove photo button
    // as it's now handled by the global admin.js implementation
});
</script>
<?php
// Flush the output buffer
ob_end_flush();
?>


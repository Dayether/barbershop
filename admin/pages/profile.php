<?php
/**
 * Admin Profile Page
 * Uses OOP and PDO for database operations
 */

// Output buffering is now handled in admin_index.php
// Do not use ob_start() here again

class UserProfile {
    private $db;
    private $user_id;
    private $errors = [];
    private $user;

    /**
     * Constructor - initialize database connection and user ID
     */
    public function __construct($user_id) {
        try {
            $this->db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
        
        $this->user_id = $user_id;
        $this->fetchUserData();
    }

    /**
     * Get current user data
     */
    private function fetchUserData() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $_SESSION['user']['user_id']);
            $stmt->execute();
            $this->user = $stmt->fetch();
            
            if (!$this->user) {
                die("User not found");
            }
        } catch (PDOException $e) {
            die("Error fetching user data: " . $e->getMessage());
        }
    }

    /**
     * Get user data
     */
    public function getUserData() {
        return $this->user;
    }

    /**
     * Update user profile information
     */
    public function updateProfile($data) {
        // Validate data
        $this->validateProfileData($data);
        
        if (!empty($this->errors)) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?");
            $result = $stmt->execute([
                $data['name'], 
                $data['email'], 
                $data['phone'] ?? '', 
                $this->user_id
            ]);
            
            if ($result) {
                // Update session data
                $_SESSION['user']['name'] = $data['name'];
                $_SESSION['user']['email'] = $data['email'];
                
                // Set success notification
                $this->setSuccessMessage('Profile updated successfully!');
                return true;
            }
            
            $this->errors[] = "Failed to update profile";
            return false;
        } catch (PDOException $e) {
            $this->errors[] = "Database error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Validate profile update data
     */
    private function validateProfileData($data) {
        // Name validation
        if (empty($data['name'])) {
            $this->errors[] = "Name is required";
        }
        
        // Email validation
        if (empty($data['email'])) {
            $this->errors[] = "Email is required";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid email format";
        } else {
            // Check if email is already in use by another user
            try {
                $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $stmt->execute([$data['email'], $this->user_id]);
                if ($stmt->rowCount() > 0) {
                    $this->errors[] = "Email already in use by another account";
                }
            } catch (PDOException $e) {
                $this->errors[] = "Database error: " . $e->getMessage();
            }
        }
    }

    /**
     * Upload and update profile picture
     */
    public function updateProfilePicture($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = "File upload error: " . $this->getUploadErrorMessage($file['error']);
            return false;
        }
        
        // Directory for profile pictures
        $uploadDir = '../uploads/profiles/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Check file type
        $fileInfo = getimagesize($file['tmp_name']);
        if ($fileInfo === false) {
            $this->errors[] = "Uploaded file is not an image";
            return false;
        }
        
        // Get file extension and generate new filename
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($fileExt, $allowedExt)) {
            $this->errors[] = "Invalid file format. Allowed formats: jpg, jpeg, png, gif";
            return false;
        }
        
        // Create unique filename
        $newFilename = 'profile_' . $this->user_id . '_' . time() . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFilename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $relativeUploadPath = 'uploads/profiles/' . $newFilename;
            
            try {
                $stmt = $this->db->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
                if ($stmt->execute([$relativeUploadPath, $this->user_id])) {
                    // Update session data
                    $_SESSION['user']['profile_pic'] = $relativeUploadPath;
                    
                    // Set success notification
                    $this->setSuccessMessage('Profile picture updated successfully!');
                    return true;
                }
                
                $this->errors[] = "Failed to update profile picture in database";
                return false;
            } catch (PDOException $e) {
                $this->errors[] = "Database error: " . $e->getMessage();
                return false;
            }
        } else {
            $this->errors[] = "Failed to move uploaded file";
            return false;
        }
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($error) {
        $phpFileUploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive specified in the HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        
        return isset($phpFileUploadErrors[$error]) ? $phpFileUploadErrors[$error] : 'Unknown upload error';
    }

    /**
     * Change user password
     */
    public function changePassword($data) {
        // Validate password data
        $this->validatePasswordData($data);
        
        if (!empty($this->errors)) {
            return false;
        }
        
        try {
            // Hash the new password
            $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
            
            // Update the password
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            if ($stmt->execute([$hashedPassword, $this->user_id])) {
                // Set success notification
                $this->setSuccessMessage('Password changed successfully!');
                return true;
            }
            
            $this->errors[] = "Failed to update password";
            return false;
        } catch (PDOException $e) {
            $this->errors[] = "Database error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Validate password change data
     */
    private function validatePasswordData($data) {
        // Current password validation
        if (empty($data['current_password'])) {
            $this->errors[] = "Current password is required";
            return;
        }
        
        // Verify current password
        try {
            $stmt = $this->db->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$this->user_id]);
            $userPass = $stmt->fetchColumn();
            
            if (!password_verify($data['current_password'], $userPass)) {
                $this->errors[] = "Current password is incorrect";
                return;
            }
        } catch (PDOException $e) {
            $this->errors[] = "Database error: " . $e->getMessage();
            return;
        }
        
        // New password validation
        if (empty($data['new_password'])) {
            $this->errors[] = "New password is required";
        } elseif (strlen($data['new_password']) < 6) {
            $this->errors[] = "New password must be at least 6 characters long";
        }
        
        // Password confirmation
        if ($data['new_password'] !== $data['confirm_password']) {
            $this->errors[] = "New passwords do not match";
        }
    }

    /**
     * Set success message for toast notification
     */
    private function setSuccessMessage($message) {
        $_SESSION['toast_type'] = 'success';
        $_SESSION['toast_title'] = 'Success';
        $_SESSION['toast_message'] = $message;
    }

    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }

    // Profile picture delete functionality
    public function deleteProfilePicture() {
        try {
            // Get the current profile picture path
            $currentPic = $this->user['profile_pic'];
            
            // Only proceed if there's a custom profile picture
            if ($currentPic && $currentPic !== 'uploads/default-profile.jpg') {
                // Delete the file
                $fullPath = '../' . $currentPic;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
                
                // Update the database to set default picture
                $stmt = $this->db->prepare("UPDATE users SET profile_pic = 'uploads/default-profile.jpg' WHERE user_id = ?");
                if ($stmt->execute([$this->user_id])) {
                    // Update session data
                    $_SESSION['user']['profile_pic'] = 'uploads/default-profile.jpg';
                    
                    // Set success notification
                    $this->setSuccessMessage('Profile picture removed successfully!');
                    return true;
                }
            }
            $this->errors[] = "No custom profile picture to remove";
            return false;
        } catch (PDOException $e) {
            $this->errors[] = "Database error: " . $e->getMessage();
            return false;
        }
    }
}

// Initialize profile handler
$profileHandler = new UserProfile($_SESSION['user']['user_id']); // FIX: use 'user_id' not 'id'
$user = $profileHandler->getUserData();

// Handle form submissions
$redirect_needed = false;

// Handle profile update
if (isset($_POST['update_profile'])) {
    $profileData = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone'] ?? '')
    ];
    
    if ($profileHandler->updateProfile($profileData)) {
        $redirect_needed = true;
    }
}

// Handle profile picture upload
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($profileHandler->updateProfilePicture($_FILES['profile_pic'])) {
        $redirect_needed = true;
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $passwordData = [
        'current_password' => $_POST['current_password'],
        'new_password' => $_POST['new_password'],
        'confirm_password' => $_POST['confirm_password']
    ];
    
    if ($profileHandler->changePassword($passwordData)) {
        $redirect_needed = true;
    }
}

// Handle profile picture delete
if (isset($_GET['delete_picture']) && $_GET['delete_picture'] === '1') {
    if ($profileHandler->deleteProfilePicture()) {
        $redirect_needed = true;
    }
}

// Get any validation errors
$errors = $profileHandler->getErrors();

// If we need to redirect, do it now while the output buffer is still active
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

    <div class="profile-container">
        <div class="row">
            <div class="col-lg-4">
                <div class="profile-sidebar">
                    <div class="profile-image-container">
                        <div class="profile-image">
                            <img src="<?php echo !empty($user['profile_pic']) ? '../' . $user['profile_pic'] : '../uploads/default-profile.jpg'; ?>" alt="Profile Picture">
                            <div class="image-overlay">
                                <form id="avatar-form" method="POST" enctype="multipart/form-data">
                                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*" style="display: none;">
                                    <label for="profile_pic" class="upload-btn">
                                        <i class="fas fa-camera"></i>
                                        <span>Change Photo</span>
                                    </label>
                                </form>
                                <?php if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'uploads/default-profile.jpg'): ?>
                                <a href="?page=profile&delete_picture=1" 
                                   class="delete-btn remove-photo-btn" 
                                   data-confirm="Are you sure you want to remove your profile picture? This will reset to the default image."
                                   data-confirm-title="Remove Profile Picture">
                                    <i class="fas fa-trash"></i>
                                    <span>Remove Photo</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="profile-details">
                        <h3><?php echo htmlspecialchars($user['name']); ?></h3>
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
                                            <label for="name">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
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

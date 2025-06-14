<?php
// Start output buffering at the very beginning
ob_start();
session_start();
require_once '../database.php';

$db = new Database();

// Use Database methods for authentication and admin check
if (!$db->isUserLoggedIn() || !$db->isUserAdmin($_SESSION['user']['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get current page from URL parameter
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Sanitize page parameter to prevent directory traversal
$page = preg_replace('/[^a-zA-Z0-9_]/', '', $page);

// Include header
include 'includes/header.php';
?>

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php
        switch ($page) {
            case 'dashboard':
                include 'pages/dashboard.php';
                break;
            case 'appointments':
                include 'pages/appointments.php';
                break;
            case 'products':
                include 'pages/products.php';
                break;
            case 'barbers':
                include 'pages/barbers.php';
                break;
            case 'messages':
                include 'pages/messages.php';
                break;
            case 'orders':
                include 'pages/orders.php';
                break;
            case 'profile':
                include 'pages/profile.php';
                break;
            case 'services':
                include 'pages/services.php';
                break;
            default:
                include 'pages/dashboard.php';
                break;
        }
        ?>
    </div>
</div>

<!-- IziToast Messages -->
<?php if(isset($_SESSION['toast_message'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    iziToast.<?php echo $_SESSION['toast_type']; ?>({
        title: '<?php echo $_SESSION['toast_title']; ?>',
        message: '<?php echo $_SESSION['toast_message']; ?>',
        position: 'topRight',
        timeout: 5000,
        progressBar: true,
        closeOnClick: true,
        overlay: false,
        displayMode: 'once'
    });
});
</script>
<?php
    // Clear the toast message after displaying it
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_type']);
    unset($_SESSION['toast_title']);
endif;
?>

<?php 
include 'includes/footer.php';
// Flush the output buffer at the end
ob_end_flush();
?>

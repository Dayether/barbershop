<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user']['user_id'];
$db = new Database();

// Fetch all messages sent by the user, including admin replies
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages - Tipuno Barbershop</title>
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/hamburger.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/contact.css">
    <link rel="stylesheet" href="css/banner-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
</head>
<body>
<?php include 'includes/header.php'; ?>

<section class="page-banner">
    <div class="container">
        <h1>My Messages</h1>
        <p>View and manage your sent messages.</p>
    </div>
</section>

<section class="orders-section">
    <div class="container">
        <!-- Add profile tabs navigation -->
        <div class="profile-tabs">
            <a href="profile.php#profile-info" class="tab">
                <i class="fas fa-user"></i> <span>Profile Information</span>
            </a>
            <a href="profile.php#security" class="tab">
                <i class="fas fa-lock"></i> <span>Security</span>
            </a>
            <a href="my_appointments.php" class="tab">
                <i class="fas fa-calendar-alt"></i> <span>My Appointments</span>
            </a>
            <a href="orders.php" class="tab">
                <i class="fas fa-shopping-bag"></i> <span>My Orders</span>
            </a>
            <a href="my_messages.php" class="tab active" data-tab="messages">
                <i class="fas fa-envelope"></i> <span>My Messages</span>
            </a>
        </div>
        <div class="profile-content">
            <div class="tab-content active" id="messages-content">
                <div class="profile-card">
                    <div class="card-header">
                        <h2>My Messages</h2>
                        <p>View your sent messages and see replies from our team.</p>
                    </div>
                    <div class="messages-list">
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
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script src="js/common.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            mirror: false
        });
    });
</script>
</body>
</html>

<?php
require_once '../database.php';
require_once 'includes/notifications.php';

$db = new Database();

$viewMode = 'list';
$message = null;
$errorMsg = '';
$successMsg = '';

// Update message status (mark as read/unread)
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['message_id'];
    $status = $_POST['status'];
    $result = $db->updateMessageStatus($id, $status);
    if ($result['success']) {
        setSuccessToast("Message marked as " . $status);
        // Redirect to the same view page to refresh status
        header("Location: ?page=messages&view=" . $id);
        exit();
    } else {
        setErrorToast($result['error_message'] ?? "Failed to update message status.");
    }
}

// Mark as read if viewing a new message
if (isset($_GET['view'])) {
    $viewMode = 'view';
    $id = (int)$_GET['view'];
    $message = $db->getMessageById($id);
    if ($message) {
        if ($message['status'] === 'new') {
            $db->updateMessageStatus($id, 'read');
            // Re-fetch to get updated status
            $message = $db->getMessageById($id);
        }
    } else {
        setErrorToast("Message not found.");
        $viewMode = 'list';
    }
}

// Delete message
if (isset($_GET['delete']) && isset($_GET['confirm_delete'])) {
    $id = (int)$_GET['delete'];
    $result = $db->deleteMessage($id);
    if ($result['success']) {
        setSuccessToast("Message deleted successfully!");
        header("Location: ?page=messages");
        exit();
    } else {
        setErrorToast($result['error_message'] ?? "Failed to delete message.");
        header("Location: ?page=messages");
        exit();
    }
}

// Get status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination for list view
if ($viewMode === 'list') {
    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $perPage = 10;
    $totalMessages = $db->countMessages($statusFilter);
    $totalPages = ceil($totalMessages / $perPage);
    $messages = $db->getMessages($page, $perPage, $statusFilter);
    $unreadCount = $db->countMessages('new');
}
?>

<?php if ($viewMode === 'view' && $message): ?>
<!-- VIEW MESSAGE DETAIL -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Message Details</h2>
        <div class="actions">
            <a href="?page=messages" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
            <form method="post" style="display: inline;">
                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                <?php if ($message['status'] == 'read'): ?>
                    <input type="hidden" name="status" value="new">
                    <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                        <i class="fas fa-envelope"></i> Mark as Unread
                    </button>
                <?php else: ?>
                    <input type="hidden" name="status" value="read">
                    <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                        <i class="fas fa-envelope-open"></i> Mark as Read
                    </button>
                <?php endif; ?>
            </form>
            <a href="?page=messages&delete=<?php echo $message['id']; ?>" 
               class="btn btn-accent btn-sm delete-btn"
               id="delete-message-<?php echo $message['id']; ?>"
               data-confirm="Are you sure you want to delete this message? This action cannot be undone."
               data-confirm-title="Delete Message"
               data-item-name="this message">
                <i class="fas fa-trash"></i> Delete
            </a>
        </div>
    </div>
    <div class="admin-card-body">
        <div class="message-container">
            <div class="message-header">
                <div class="message-subject">
                    <h3><?php echo htmlspecialchars($message['subject']); ?></h3>
                    <span class="status-badge status-<?php echo $message['status']; ?>">
                        <?php echo ucfirst($message['status']); ?>
                    </span>
                </div>
                <div class="message-meta">
                    <div class="message-date">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo date('M d, Y', strtotime($message['created_at'])); ?>
                    </div>
                    <div class="message-time">
                        <i class="fas fa-clock"></i>
                        <?php echo date('h:i A', strtotime($message['created_at'])); ?>
                    </div>
                </div>
            </div>
            
            <div class="message-sender-info">
                <div class="message-sender">
                    <div class="sender-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="sender-details">
                        <h4><?php echo htmlspecialchars($message['name']); ?></h4>
                        <div class="sender-contacts">
                            <div class="sender-email">
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>"><?php echo htmlspecialchars($message['email']); ?></a>
                            </div>
                            <?php if(!empty($message['phone'])): ?>
                            <div class="sender-phone">
                                <i class="fas fa-phone"></i>
                                <a href="tel:<?php echo htmlspecialchars($message['phone']); ?>"><?php echo htmlspecialchars($message['phone']); ?></a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="message-actions">
                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=RE: <?php echo htmlspecialchars($message['subject']); ?>" class="btn btn-primary">
                        <i class="fas fa-reply"></i> Reply by Email
                    </a>
                    <?php if(!empty($message['phone'])): ?>
                    <a href="tel:<?php echo htmlspecialchars($message['phone']); ?>" class="btn btn-outline">
                        <i class="fas fa-phone"></i> Call
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="message-content">
                <div class="message-text">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- LIST MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Messages Management</h2>
        <div class="actions">
            <a href="?page=messages" class="btn btn-outline btn-sm <?php echo empty($statusFilter) ? 'active' : ''; ?>">All</a>
            <a href="?page=messages&status=new" class="btn btn-outline btn-sm <?php echo $statusFilter === 'new' ? 'active' : ''; ?>">
                New 
                <?php 
                // Count unread messages
                $unreadCount = $db->countMessages('new');
                if ($unreadCount > 0) {
                    echo '<span class="badge">' . $unreadCount . '</span>';
                }
                ?>
            </a>
            <a href="?page=messages&status=read" class="btn btn-outline btn-sm <?php echo $statusFilter === 'read' ? 'active' : ''; ?>">Read</a>
        </div>
    </div>
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Sender</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $message): ?>
                            <tr class="<?php echo $message['status'] === 'new' ? 'unread-row' : ''; ?>">
                                <td>
                                    <div class="sender-info">
                                        <div class="sender-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="sender-details">
                                            <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                                            <span class="email"><?php echo htmlspecialchars($message['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="?page=messages&view=<?php echo $message['id']; ?>" class="message-subject-link">
                                        <?php echo htmlspecialchars($message['subject']); ?>
                                        <span class="message-preview"><?php echo htmlspecialchars(substr($message['message'], 0, 50)) . (strlen($message['message']) > 50 ? '...' : ''); ?></span>
                                    </a>
                                </td>
                                <td>
                                    <div class="message-date">
                                        <div class="date"><?php echo date('M d, Y', strtotime($message['created_at'])); ?></div>
                                        <div class="time"><?php echo date('h:i A', strtotime($message['created_at'])); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <form method="post" id="status-form-<?php echo $message['id']; ?>">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $message['status'] === 'new' ? 'read' : 'new'; ?>">
                                        <button type="submit" name="update_status" class="status-badge status-<?php echo $message['status']; ?>">
                                            <?php if ($message['status'] === 'new'): ?>
                                                <i class="fas fa-envelope"></i> New
                                            <?php else: ?>
                                                <i class="fas fa-envelope-open"></i> Read
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="actions">
                                    <div class="action-buttons">
                                        <a href="?page=messages&view=<?php echo $message['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?page=messages&delete=<?php echo $message['id']; ?>" 
                                           class="btn btn-accent btn-sm delete-btn"
                                           id="delete-message-<?php echo $message['id']; ?>"
                                           data-confirm="Are you sure you want to delete this message? This action cannot be undone."
                                           data-confirm-title="Delete Message"
                                           data-item-name="this message">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-envelope-open"></i>
                                    <p>No messages found</p>
                                    <small>Messages will appear here once users contact you through the website</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li><a href="?page=messages<?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?>&p=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                <?php endif; ?>
                
                <?php 
                // Display a limited number of pages with ellipsis for better UX
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<li><a href="?page=messages' . (!empty($statusFilter) ? '&status=' . $statusFilter : '') . '&p=1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                }
                
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <a href="?page=messages<?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; 
                
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                    echo '<li><a href="?page=messages' . (!empty($statusFilter) ? '&status=' . $statusFilter : '') . '&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <?php if ($page < $totalPages): ?>
                    <li><a href="?page=messages<?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?>&p=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<style>
/* Messages specific styling */
.message-container {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-light);
}

.message-subject {
    display: flex;
    align-items: center;
    gap: 15px;
}

.message-subject h3 {
    margin: 0;
    font-size: 1.5rem;
}

.message-meta {
    display: flex;
    gap: 15px;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.message-meta i {
    margin-right: 5px;
}

.message-sender-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: var(--light-bg);
    padding: 20px;
    border-radius: 8px;
}

.message-sender {
    display: flex;
    align-items: center;
    gap: 15px;
}

.sender-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: var(--secondary-color);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.sender-details h4 {
    margin: 0 0 5px 0;
    font-size: 1.2rem;
}

.sender-contacts {
    display: flex;
    gap: 15px;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.sender-email, .sender-phone {
    display: flex;
    align-items: center;
    gap: 5px;
}

.message-content {
    padding: 20px;
    background-color: var(--white);
    border-radius: 8px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05);
}

.message-text {
    line-height: 1.8;
    white-space: pre-line;
}

/* Table Styling */
.sender-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sender-info .sender-avatar {
    width: 40px;
    height: 40px;
    font-size: 1.2rem;
}

.sender-info .sender-details {
    display: flex;
    flex-direction: column;
}

.sender-info .sender-details strong {
    margin-bottom: 2px;
    color: var(--secondary-color);
}

.sender-info .sender-details .email {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.message-subject-link {
    display: block;
    color: var(--secondary-color);
    text-decoration: none;
}

.message-preview {
    transition: color 0.3s;
}

.sender-info .sender-details strong {
    color: var(--text-muted);
    margin-bottom: 2px;
}

.sender-info .sender-details .email {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.message-date .date {
    font-weight: 600;
    color: var(--secondary-color);
}

.message-date .time {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.status-badge.status-new {
    background-color: rgba(233, 30, 99, 0.1);
    color: #E91E63;
    border-left: 4px solid #E91E63;
}

.status-badge.status-read {
    background-color: rgba(158, 158, 158, 0.1);
    color: #9E9E9E;
    border-left: 4px solid #9E9E9E;
}

.unread-row {
    background-color: rgba(233, 30, 99, 0.03);
    font-weight: 500;
}

@media (max-width: 992px) {
    .message-sender-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
}




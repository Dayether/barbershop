<?php
// Include Message model
require_once 'models/Message.php';
require_once 'includes/notifications.php';

// Database connection
$db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Instantiate Message object
$messageObj = new Message($db);

// Set default view mode to list
$viewMode = 'list';
$message = null;

// Check if view mode is requested
if (isset($_GET['view'])) {
    $viewMode = 'view';
    $id = $_GET['view'];
    
    // Get message details using the model
    $messageObj->id = $id;
    if ($messageObj->readSingle()) {
        $message = [
            'id' => $messageObj->id,
            'name' => $messageObj->name,
            'email' => $messageObj->email,
            'phone' => $messageObj->phone,
            'subject' => $messageObj->subject,
            'message' => $messageObj->message,
            'status' => $messageObj->status,
            'created_at' => $messageObj->created_at
        ];
        
        // Mark as read if it's new
        if ($message['status'] == 'new') {
            $messageObj->status = 'read';
            $messageObj->updateStatus();
        }
    } else {
        setErrorToast("Message not found.");
        $viewMode = 'list';
    }
}

// Delete message - Remove the inline JavaScript approach
if (isset($_GET['delete']) && !isset($_GET['confirm_delete'])) {
    // Don't output anything here - we'll handle this with client-side JavaScript
}

// Process confirmed deletion
if (isset($_GET['delete']) && isset($_GET['confirm_delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // Get message details first
        $messageObj->id = $id;
        if (!$messageObj->readSingle()) {
            throw new Exception("Message not found.");
        }
        
        // Delete the message
        if ($messageObj->delete()) {
            setSuccessToast("Message deleted successfully!");
        } else {
            setErrorToast("Failed to delete message.");
        }
    } catch (PDOException $e) {
        error_log("Error deleting message: " . $e->getMessage());
        setErrorToast("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        setErrorToast($e->getMessage());
    }
}

// Update message status (mark as read/unread)
if (isset($_POST['update_status'])) {
    try {
        $messageObj->id = $_POST['message_id'];
        $messageObj->status = $_POST['status'];
        
        if ($messageObj->updateStatus()) {
            setSuccessToast("Message marked as " . $messageObj->status);
        } else {
            setErrorToast("Failed to update message status.");
        }
    } catch (PDOException $e) {
        setErrorToast("Database error: " . $e->getMessage());
    }
}

// Get status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination for list view
if ($viewMode === 'list') {
    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $perPage = 10;
    
    // Get total count
    if(!empty($statusFilter)) {
        $totalMessages = $messageObj->count($statusFilter);
    } else {
        $totalMessages = $messageObj->count();
    }
    $totalPages = ceil($totalMessages / $perPage);
    
    // Get messages for current page
    $stmt = $messageObj->read($page, $perPage, $statusFilter);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                $unreadCount = $messageObj->count('new');
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
}.message-meta 
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click handler for unread message rows to view message
    const messageRows = document.querySelectorAll('tr[class*="unread-row"]');
    messageRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't trigger when clicking buttons, links, or forms
            if (
                e.target.tagName !== 'BUTTON' &&
                e.target.tagName !== 'A' &&
                !e.target.closest('button') &&
                !e.target.closest('a') &&
                !e.target.closest('form')
            ) {
                const viewLink = row.querySelector('a[href*="view="]');
                if (viewLink) {
                    window.location = viewLink.href;
                }
            }
        });
    });

    // Add custom delete handler for message delete buttons
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const deleteUrl = this.getAttribute('href');
            const confirmMessage = this.getAttribute('data-confirm') || 'Are you sure you want to delete this message? This action cannot be undone.';
            const confirmTitle = this.getAttribute('data-confirm-title') || 'Delete Message';

            iziToast.question({
                timeout: false,
                close: false,
                overlay: true,
                displayMode: 'once',
                id: 'question',
                zindex: 999,
                title: confirmTitle,
                message: confirmMessage,
                position: 'center',
                buttons: [
                    ['<button><b>Yes, Delete</b></button>', function (instance, toast) {
                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        iziToast.info({
                            message: 'Deleting message...',
                            timeout: 1000,
                            position: 'center'
                        });
                        setTimeout(function() {
                            window.location.href = deleteUrl + '&confirm_delete=1';
                        }, 1000);
                    }, true],
                    ['<button>Cancel</button>', function (instance, toast) {
                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                    }]
                ]
            });
        });
    });
});
</script>






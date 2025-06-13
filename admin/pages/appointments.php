<?php
require_once '../database.php';
$db = new Database();

$viewMode = 'list';
$appointment = null;
$services = [];
$barbers = [];
$appointments = [];
$history = [];
$errorMsg = '';
$successMsg = '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 10;

// Edit mode
if (isset($_GET['edit'])) {
    $viewMode = 'edit';
    $id = (int)$_GET['edit'];
    $appointment = $db->getAppointmentById($id);
    if (!$appointment) {
        $errorMsg = "Appointment not found.";
        $viewMode = 'list';
    } else {
        $services = $db->getUniqueActiveServices();
        $barbers = $db->getActiveBarbers();
        $history = $db->getAppointmentHistory($id);
    }
}

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment'])) {
    $updateData = [
        'appointment_id' => $_POST['appointment_id'],
        'service_id' => $_POST['service'],
        'appointment_date' => $_POST['appointment_date'],
        'appointment_time' => $_POST['appointment_time'],
        'barber_id' => $_POST['barber'],
        'client_name' => $_POST['client_name'],
        'client_email' => $_POST['client_email'],
        'client_phone' => $_POST['client_phone'],
        'notes' => $_POST['notes'],
        'status' => $_POST['status']
    ];
    $result = $db->updateAppointment($updateData);
    if ($result['success']) {
        $successMsg = "Appointment updated successfully!";
        $appointment = $db->getAppointmentById($updateData['appointment_id']);
        $history = $db->getAppointmentHistory($updateData['appointment_id']);
    } else {
        $errorMsg = $result['error_message'];
    }
}

// Handle status update from list view
if (isset($_POST['update_status']) && !isset($_POST['update_appointment'])) {
    $result = $db->updateAppointmentStatus($_POST['appointment_id'], $_POST['status']);
    if ($result['success']) {
        $successMsg = "Status updated to " . ucfirst($_POST['status']);
    } else {
        $errorMsg = $result['error_message'];
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['confirm_delete'])) {
    $id = (int)$_GET['delete'];
    $result = $db->deleteAppointment($id);
    if ($result['success']) {
        $successMsg = "Appointment deleted successfully!";
    } else {
        $errorMsg = $result['error_message'];
    }
}

// Pagination and list view
if ($viewMode === 'list') {
    $totalAppointments = $db->countAppointments($statusFilter);
    $totalPages = ceil($totalAppointments / $perPage);
    $offset = ($page - 1) * $perPage;
    $appointments = $db->getAppointments($statusFilter, $perPage, $offset);
    $barberMap = $db->getBarberMap();
    $serviceMap = $db->getServiceMap();
}
?>

<?php if ($viewMode === 'edit' && $appointment): ?>
<!-- EDIT MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Edit Appointment</h2>
        <div class="actions">
            <a href="?page=appointments" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
    <div class="admin-card-body">
        <form method="post" class="admin-form">
            <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['appointment_id']); ?>">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="service">Service</label>
                        <select name="service" id="service" class="form-control" required>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service['service_id']); ?>"
                                    <?php echo ($appointment['service_id'] == $service['service_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="barber">Barber</label>
                        <select name="barber" id="barber" class="form-control" required>
                            <option value="">Select a barber</option>
                            <?php foreach ($barbers as $barber): ?>
                                <option value="<?php echo htmlspecialchars($barber['barber_id']); ?>"
                                    <?php echo ($appointment['barber_id'] == $barber['barber_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($barber['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="appointment_date">Appointment Date</label>
                        <input type="date" name="appointment_date" id="appointment_date" class="form-control" value="<?php echo htmlspecialchars($appointment['appointment_date']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="appointment_time">Appointment Time</label>
                        <input type="time" name="appointment_time" id="appointment_time" class="form-control" value="<?php echo htmlspecialchars($appointment['appointment_time']); ?>" required>
                    </div>
                </div>
            </div>
            <hr>
            <h3>Client Information</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="client_name">Client Name</label>
                        <input type="text" name="client_name" id="client_name" class="form-control" value="<?php echo htmlspecialchars($appointment['client_name']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="client_email">Client Email</label>
                        <input type="email" name="client_email" id="client_email" class="form-control" value="<?php echo htmlspecialchars($appointment['client_email']); ?>" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="client_phone">Client Phone</label>
                <input type="text" name="client_phone" id="client_phone" class="form-control" value="<?php echo htmlspecialchars($appointment['client_phone']); ?>" required>
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea name="notes" id="notes" class="form-control" rows="4"><?php echo htmlspecialchars($appointment['notes']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control status-select status-<?php echo htmlspecialchars($appointment['status']); ?>" required>
                    <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="form-actions text-right">
                <button type="submit" name="update_appointment" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Appointment
                </button>
                <a href="?page=appointments" class="btn btn-outline">Cancel</a>
            </div>
        </form>

        <!-- Appointment History -->
        <?php if (!empty($history)): ?>
            <hr>
            <h3>Appointment History</h3>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Notes</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $entry): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($entry['created_at'])); ?></td>
                                <td><?php echo ucfirst($entry['action']); ?></td>
                                <td><?php echo htmlspecialchars($entry['notes']); ?></td>
                                <td>
                                    <?php
                                    if ($entry['staff_id']) {
                                        echo htmlspecialchars($entry['staff_name'] ?? 'Admin');
                                    } elseif ($entry['user_id']) {
                                        echo htmlspecialchars($entry['user_name'] ?? 'Customer');
                                    } else {
                                        echo 'System';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add iziToast notification for edit mode feedback -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($successMsg)): ?>
        iziToast.success({
            title: 'Success',
            message: '<?php echo addslashes($successMsg); ?>',
            position: 'topRight',
            icon: 'fas fa-check-circle'
        });
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        iziToast.error({
            title: 'Error',
            message: '<?php echo addslashes($errorMsg); ?>',
            position: 'topRight',
            icon: 'fas fa-exclamation-circle'
        });
    <?php endif; ?>
});
</script>
<?php else: ?>
<!-- LIST MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Appointment Management</h2>
        <div class="actions">
            <a href="?page=appointments" class="btn btn-outline btn-sm <?php echo empty($statusFilter) ? 'active' : ''; ?>">All</a>
            <a href="?page=appointments&status=pending" class="btn btn-outline btn-sm <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?page=appointments&status=confirmed" class="btn btn-outline btn-sm <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
            <a href="?page=appointments&status=completed" class="btn btn-outline btn-sm <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">Completed</a>
            <a href="?page=appointments&status=cancelled" class="btn btn-outline btn-sm <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
        </div>
    </div>
    <div class="admin-card-body">
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
        <?php endif; ?>
        
        <div class="table-responsive appointments-table">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Date & Time</th>
                        <th>Barber</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($appointments) > 0): ?>
                        <?php
                        // Build barber id=>name map
                        $barberMap = $db->getBarberMap(); // Implement in Database.php (returns [barber_id => name])
                        // Build service id=>name map
                        $serviceMap = $db->getServiceMap(); // Implement in Database.php (returns [service_id => name])
                        ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td>
                                    <div class="booking-reference"><?php echo htmlspecialchars($appointment['booking_reference'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <div class="client-info">
                                        <strong>
                                            <?php
                                            if (!empty($appointment['first_name']) && !empty($appointment['last_name'])) {
                                                echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']);
                                            } else {
                                                echo htmlspecialchars($appointment['client_name'] ?? '');
                                            }
                                            ?>
                                        </strong>
                                        <span class="email"><?php echo htmlspecialchars($appointment['client_email'] ?? ''); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="service-name">
                                        <?php
                                        if (!empty($appointment['service_name'])) {
                                            echo htmlspecialchars($appointment['service_name']);
                                        } elseif (!empty($appointment['service'])) {
                                            // If 'service' is numeric, try to map to name
                                            if (is_numeric($appointment['service']) && isset($serviceMap[$appointment['service']])) {
                                                echo htmlspecialchars($serviceMap[$appointment['service']]);
                                            } else {
                                                echo htmlspecialchars($appointment['service']);
                                            }
                                        } elseif (!empty($appointment['service_id']) && isset($serviceMap[$appointment['service_id']])) {
                                            echo htmlspecialchars($serviceMap[$appointment['service_id']]);
                                        } elseif (!empty($appointment['service_id'])) {
                                            echo 'Service #' . htmlspecialchars($appointment['service_id']);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="appointment-time">
                                        <div class="date"><?php echo isset($appointment['appointment_date']) ? date('M d, Y', strtotime($appointment['appointment_date'])) : ''; ?></div>
                                        <div class="time"><?php echo isset($appointment['appointment_time']) ? date('h:i A', strtotime($appointment['appointment_time'])) : ''; ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="barber-name">
                                        <?php
                                        if (!empty($appointment['barber_name'])) {
                                            echo htmlspecialchars($appointment['barber_name']);
                                        } elseif (!empty($appointment['barber'])) {
                                            if (is_numeric($appointment['barber']) && isset($barberMap[$appointment['barber']])) {
                                                echo htmlspecialchars($barberMap[$appointment['barber']]);
                                            } else {
                                                echo htmlspecialchars($appointment['barber']);
                                            }
                                        } elseif (!empty($appointment['barber_id']) && isset($barberMap[$appointment['barber_id']])) {
                                            echo htmlspecialchars($barberMap[$appointment['barber_id']]);
                                        } elseif (!empty($appointment['barber_id'])) {
                                            echo 'Barber #' . htmlspecialchars($appointment['barber_id']);
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($appointment['status'] ?? ''); ?>">
                                        <?php echo ucfirst($appointment['status'] ?? ''); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <div class="action-buttons">
                                        <a href="?page=appointments&edit=<?php echo $appointment['id'] ?? $appointment['appointment_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?page=appointments&delete=<?php echo $appointment['id'] ?? $appointment['appointment_id']; ?>" class="btn btn-accent btn-sm delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No appointments found</p>
                                    <small>Appointments will appear here once they are scheduled</small>
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
                    <li><a href="?page=appointments<?php echo !empty($statusFilter) ? '&status=' . urlencode((string)$statusFilter) : ''; ?>&p=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                <?php endif; ?>
                
                <?php 
                // Display a limited number of pages with ellipsis for better UX
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                if ($startPage > 1) {
                    echo '<li><a href="?page=appointments' . (!empty($statusFilter) ? '&status=' . urlencode((string)$statusFilter) : '') . '&p=1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                }

                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <a href="?page=appointments<?php echo !empty($statusFilter) ? '&status=' . urlencode((string)$statusFilter) : ''; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; 

                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                    echo '<li><a href="?page=appointments' . (!empty($statusFilter) ? '&status=' . urlencode((string)$statusFilter) : '') . '&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <?php if ($page < $totalPages): ?>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Add iziToast notification for delete feedback in list mode -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($successMsg)): ?>
        iziToast.success({
            title: 'Success',
            message: '<?php echo addslashes($successMsg); ?>',
            position: 'topRight',
            icon: 'fas fa-check-circle'
        });
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        iziToast.error({
            title: 'Error',
            message: '<?php echo addslashes($errorMsg); ?>',
            position: 'topRight',
            icon: 'fas fa-exclamation-circle'
        });
    <?php endif; ?>
});
</script>
<?php endif; ?>

<style>
/* Enhanced appointment table styling */
.appointments-table {
    margin-top: 20px;
    border-radius: 10px;
}

.admin-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 30px rgba(0, 0, 0, 0.05);
    width: 100%;
    border-spacing: 0;
}

.admin-table thead th {
    background: linear-gradient(45deg, var(--secondary-color), #32302c);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    padding: 18px 25px;
    font-size: 14px;
    letter-spacing: 1px;
    border-bottom: 3px solid var(--primary-color);
}

.admin-table tbody td {
    padding: 18px 25px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    vertical-align: middle;
}

.admin-table tbody tr:last-child td {
    border-bottom: none;
}

/* Booking reference styling */
.booking-reference {
    display: inline-block;
    padding: 6px 12px;
    background-color: rgba(200, 166, 86, 0.1);
    border-radius: 6px;
    font-weight: 600;
    color: var(--primary-color);
    font-family: monospace;
    letter-spacing: 1px;
    border-left: 3px solid var(--primary-color);
}

/* Client info styling */
.client-info {
    display: flex;
    flex-direction: column;
}

.client-info strong {
    color: var(--secondary-color);
    margin-bottom: 3px;
}

.client-info .email {
    color: var(--text-muted);
    font-size: 13px;
}

/* Service styling */
.service-name {
    position: relative;
    padding-left: 24px;
    font-weight: 500;
    color: var(--secondary-color);
}

.service-name:before {
    content: "\f5e7"; /* scissors icon */
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary-color);
}

/* Appointment time styling */
.appointment-time {
    display: flex;
    flex-direction: column;
}

.appointment-time .date {
    font-weight: 600;
    margin-bottom: 3px;
    color: var(--secondary-color);
}

.appointment-time .time {
    color: var(--text-medium);
    font-size: 13px;
}

/* Barber styling */
.barber-name {
    font-weight: 500;
    color: var(--secondary-color);
}

/* Improved status badge and dropdown */
.status-form {
    position: relative;
}

.status-badge {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    border-radius: 50px;
    min-width: 130px;
    cursor: pointer;
    font-weight: 600;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.status-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
}

.status-badge i {
    font-size: 12px;
    margin-left: 8px;
    transition: transform 0.3s ease;
}

.status-badge.active i {
    transform: rotate(180deg);
}

.status-dropdown {
    position: absolute;
    top: calc(100% + 5px);
    left: 0;
    width: 150px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.3s ease;
    z-index: 100;
    overflow: hidden;
}

.status-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.status-option {
    padding: 10px 16px;
    cursor: pointer;
    transition: background 0.3s ease;
    font-weight: 500;
}

.status-option:hover {
    background: rgba(0, 0, 0, 0.05);
}

.status-option.current {
    position: relative;
}

.status-option.current:after {
    content: "\f00c"; /* check icon */
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
}

/* Status colors */
.status-badge.status-pending,
.status-option.status-pending {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ff9800;
    border-left: 4px solid #ff9800;
}

.status-badge.status-confirmed,
.status-option.status-confirmed {
    background-color: rgba(33, 150, 243, 0.1);
    color: #2196F3;
    border-left: 4px solid #2196F3;
}

.status-badge.status-completed,
.status-option.status-completed {
    background-color: rgba(76, 175, 80, 0.1);
    color: #4CAF50;
    border-left: 4px solid #4CAF50;
}

.status-badge.status-cancelled,
.status-option.status-cancelled {
    background-color: rgba(244, 67, 54, 0.1);
    color: #F44336;
    border-left: 4px solid #F44336;
}

/* Action buttons */
.action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.action-buttons .btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    padding: 0;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 3px 5px rgba(0, 0, 0, 0.1);
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
}

/* Empty state */
.empty-state {
    padding: 40px 20px;
    text-align: center;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    color: rgba(0, 0, 0, 0.1);
}

.empty-state p {
    font-size: 18px;
    margin-bottom: 5px;
}

.empty-state small {
    font-size: 14px;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .admin-table thead th,
    .admin-table tbody td {
        padding: 15px;
    }
    
    .status-badge {
        min-width: 110px;
        padding: 8px 12px;
    }
}

@media (max-width: 768px) {
    .admin-table {
        display: block;
        overflow-x: auto;
    }
    
    .booking-reference,
    .service-name,
    .client-info strong,
    .barber-name {
        font-size: 14px;
    }
    
    .client-info .email,
    .appointment-time .time {
        font-size: 12px;
    }
}
</style>

<script>
// Status dropdown functionality
function toggleStatusDropdown(id) {
    // Close all other dropdowns first
    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
        if (dropdown.id !== 'status-dropdown-' + id) {
            dropdown.classList.remove('show');
        }
    });
    
    // Toggle the clicked dropdown
    const dropdown = document.getElementById('status-dropdown-' + id);
    dropdown.classList.toggle('show');
    
    // Toggle active class on badge
    const badge = dropdown.previousElementSibling;
    badge.classList.toggle('active');
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function closeDropdown(e) {
        if (!e.target.closest('.status-form')) {
            dropdown.classList.remove('show');
            badge.classList.remove('active');
            document.removeEventListener('click', closeDropdown);
        }
    });
}

// Update status function
function updateStatus(id, status) {
    // Update hidden input value
    document.getElementById('status-input-' + id).value = status;
    
    // Update badge text and class
    const badge = document.querySelector('#status-form-' + id + ' .status-badge');
    badge.className = 'status-badge status-' + status;
    badge.querySelector('.status-text').textContent = status.charAt(0).toUpperCase() + status.slice(1);
    
    // Hide dropdown
    document.getElementById('status-dropdown-' + id).classList.remove('show');
    
    // Show loading state
    badge.innerHTML = '<span class="status-text"><i class="fas fa-spinner fa-spin"></i> Updating...</span>';
    
    // Submit the form
    document.getElementById('status-form-' + id).submit();
}

// Add IziToast confirmation for delete buttons
document.addEventListener('DOMContentLoaded', function() {
    // Target all delete buttons
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const deleteUrl = this.getAttribute('href');
            
            iziToast.question({
                timeout: false,
                close: false,
                overlay: true,
                displayMode: 'once',
                id: 'question',
                zindex: 999,
                title: 'Confirm Deletion',
                message: 'Are you sure you want to delete this record? This action cannot be undone.',
                position: 'center',
                buttons: [
                    ['<button><b>Yes, Delete</b></button>', function (instance, toast) {
                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        window.location.href = deleteUrl + '&confirm_delete=1';
                    }, true],
                    ['<button>Cancel</button>', function (instance, toast) {
                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                    }]
                ],
                onClosing: function(instance, toast, closedBy){
                    console.log('Closing | closedBy: ' + closedBy);
                },
                onClosed: function(instance, toast, closedBy){
                    console.log('Closed | closedBy: ' + closedBy);
                }
            });
        });
    });
});

// Close any open dropdowns when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.status-form')) {
            document.querySelectorAll('.status-dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
            document.querySelectorAll('.status-badge').forEach(badge => {
                badge.classList.remove('active');
            });
        }
    });
});
</script>

<script>
// Status dropdown functionality
function toggleStatusDropdown(id) {
    // Close all other dropdowns first
    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
        if (dropdown.id !== 'status-dropdown-' + id) {
            dropdown.classList.remove('show');
        }
    });
    
    // Toggle the clicked dropdown
    const dropdown = document.getElementById('status-dropdown-' + id);
    dropdown.classList.toggle('show');
    
    // Toggle active class on badge
    const badge = dropdown.previousElementSibling;
    badge.classList.toggle('active');
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function closeDropdown(e) {
        if (!e.target.closest('.status-form')) {
            dropdown.classList.remove('show');
            badge.classList.remove('active');
            document.removeEventListener('click', closeDropdown);
        }
    });
}

// Update status function
function updateStatus(id, status) {
    // Update hidden input value
    document.getElementById('status-input-' + id).value = status;
    
    // Update badge text and class
    const badge = document.querySelector('#status-form-' + id + ' .status-badge');
    badge.className = 'status-badge status-' + status;
    badge.querySelector('.status-text').textContent = status.charAt(0).toUpperCase() + status.slice(1);
    
    // Hide dropdown
    document.getElementById('status-dropdown-' + id).classList.remove('show');
    
    // Show loading state
    badge.innerHTML = '<span class="status-text"><i class="fas fa-spinner fa-spin"></i> Updating...</span>';
    
    // Submit the form
    document.getElementById('status-form-' + id).submit();
}

// Add IziToast confirmation for delete buttons
document.addEventListener('DOMContentLoaded', function() {
    // Target all delete buttons
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const deleteUrl = this.getAttribute('href');
            
            iziToast.question({
                timeout: false,
                close: false,
                overlay: true,
                displayMode: 'once',
                id: 'question',
                zindex: 999,
                title: 'Confirm Deletion',
                message: 'Are you sure you want to delete this record? This action cannot be undone.',
                position: 'center',
                buttons: [
                    ['<button><b>Yes, Delete</b></button>', function (instance, toast) {
                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        window.location.href = deleteUrl + '&confirm_delete=1';
                    }, true],
                    ['<button>Cancel</button>', function (instance, toast) {
                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                    }]
                ],
                onClosing: function(instance, toast, closedBy){
                    console.log('Closing | closedBy: ' + closedBy);
                },
                onClosed: function(instance, toast, closedBy){
                    console.log('Closed | closedBy: ' + closedBy);
                }
            });
        });
    });
});

// Close any open dropdowns when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.status-form')) {
            document.querySelectorAll('.status-dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
            document.querySelectorAll('.status-badge').forEach(badge => {
                badge.classList.remove('active');
            });
        }
    });
});
</script>


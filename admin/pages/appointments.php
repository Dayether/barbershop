<?php
// Include Appointment model
require_once 'models/Appointment.php';
require_once 'includes/notifications.php';

// Database connection
$db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Instantiate Appointment object
$appointmentObj = new Appointment($db);

// Set default view mode to list
$viewMode = 'list';
$appointment = null;

// Check if edit mode is requested
if (isset($_GET['edit'])) {
    $viewMode = 'edit';
    $id = $_GET['edit'];
    
    // Get appointment details using the model
    $appointmentObj->id = $id;
    if ($appointmentObj->readSingle()) {
        $appointment = [
            'id' => $appointmentObj->id,
            'booking_reference' => $appointmentObj->booking_reference,
            'user_id' => $appointmentObj->user_id,
            'service' => $appointmentObj->service,
            'appointment_date' => $appointmentObj->appointment_date,
            'appointment_time' => $appointmentObj->appointment_time,
            'barber' => $appointmentObj->barber,
            'client_name' => $appointmentObj->client_name,
            'client_email' => $appointmentObj->client_email,
            'client_phone' => $appointmentObj->client_phone,
            'notes' => $appointmentObj->notes,
            'status' => $appointmentObj->status,
            'created_at' => $appointmentObj->created_at
        ];
    } else {
        setErrorToast("Appointment not found.");
        $viewMode = 'list';
    }
    
    // Get appointment history if in edit mode
    if ($viewMode === 'edit') {
        try {
            $historyStmt = $appointmentObj->getHistory();
            $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $history = [];
        }
    }
}

// Get status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
if (is_array($statusFilter)) {
    $statusFilter = isset($statusFilter[0]) ? $statusFilter[0] : '';
}

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment'])) {
    try {
        // Collect form data and sanitize
        $appointmentObj->id = $_POST['appointment_id'];
        $appointmentObj->service = htmlspecialchars($_POST['service']);
        $appointmentObj->appointment_date = htmlspecialchars($_POST['appointment_date']);
        $appointmentObj->appointment_time = htmlspecialchars($_POST['appointment_time']);
        $appointmentObj->barber = htmlspecialchars($_POST['barber']);
        $appointmentObj->client_name = htmlspecialchars($_POST['client_name']);
        $appointmentObj->client_email = htmlspecialchars($_POST['client_email']);
        $appointmentObj->client_phone = htmlspecialchars($_POST['client_phone']);
        $appointmentObj->notes = htmlspecialchars($_POST['notes']);
        $appointmentObj->status = htmlspecialchars($_POST['status']);
        
        // Update the appointment using model
        if ($appointmentObj->update()) {
            // Add to appointment history
            $history_notes = "Appointment details updated by admin";
            $staffId = $_SESSION['user']['id'];
            $appointmentObj->addHistory('update', $history_notes, null, $staffId);
            
            setSuccessToast("Appointment updated successfully!");
            $viewMode = 'list'; // Switch back to list view after update
        } else {
            setErrorToast("Failed to update appointment.");
        }
    } catch (PDOException $e) {
        setErrorToast("Database error: " . $e->getMessage());
    }
}

// Handle status update from list view
if (isset($_POST['update_status']) && !isset($_POST['update_appointment'])) {
    try {
        $appointmentObj->id = $_POST['appointment_id'];
        $appointmentObj->status = $_POST['status'];
        
        // Update the status using model
        if ($appointmentObj->updateStatus()) {
            // Add to appointment history
            $notes = "Status changed to " . ucfirst($appointmentObj->status) . " by admin";
            $staffId = $_SESSION['user']['id'];
            $appointmentObj->addHistory($appointmentObj->status, $notes, null, $staffId);
            
            setSuccessToast("Status updated to " . ucfirst($appointmentObj->status));
        } else {
            setErrorToast("Failed to update status.");
        }
    } catch (PDOException $e) {
        setErrorToast("Database error: " . $e->getMessage());
    }
}

// Delete appointment - Remove the old confirmation code
if (isset($_GET['delete']) && !isset($_GET['confirm_delete'])) {
    $id = $_GET['delete'];
    // We'll handle this with IziToast instead of the old JavaScript confirm
    // The actual deletion will still happen when confirm_delete is set
}

// Process confirmed deletion
if (isset($_GET['delete']) && isset($_GET['confirm_delete'])) {
    $id = $_GET['delete'];
    
    try {
        // Get appointment details first for the record
        $appointmentObj->id = $id;
        $appointmentObj->readSingle();
        $refNumber = $appointmentObj->booking_reference;
        
        // Delete the appointment using model
        if ($appointmentObj->delete()) {
            setSuccessToast("Appointment #$refNumber deleted successfully!");
        } else {
            setErrorToast("Failed to delete appointment.");
        }
    } catch (PDOException $e) {
        setErrorToast("Database error: " . $e->getMessage());
    }
}

// Get services and barbers for dropdown menus (used in edit mode)
if ($viewMode === 'edit') {
    try {
        $stmt = $db->query("SELECT DISTINCT name FROM services WHERE active = 1 ORDER BY name ASC");
        $services = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stmt = $db->query("SELECT id, name FROM barbers WHERE active = 1 ORDER BY name ASC");
        $barbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $services = [];
        $barbers = [];
    }
}

// Pagination for list view
if ($viewMode === 'list') {
    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $perPage = 10;
    
    // Get total count using model
    if (!empty($statusFilter)) {
        // This would need a new method in the model for filtered count
        try {
            $countQuery = "SELECT COUNT(*) as total FROM appointments WHERE status = :status";
            $stmt = $db->prepare($countQuery);
            $stmt->bindParam(':status', $statusFilter, PDO::PARAM_STR);
            $stmt->execute();
            $totalAppointments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            $totalAppointments = 0;
        }
    } else {
        $totalAppointments = $appointmentObj->count();
    }
    
    $totalPages = ceil($totalAppointments / $perPage);
    
    // Get appointments for current page
    // This would need a custom method in the model to support filtering and pagination
    // For now, we'll keep using the direct PDO approach
    $offset = ($page - 1) * $perPage;

    // Count total appointments with filter
    try {
        $query = "SELECT a.*, u.name as user_name FROM appointments a 
                LEFT JOIN users u ON a.user_id = u.id";
        if (!empty($statusFilter)) {
            $query .= " WHERE a.status = :status";
        }
        $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($query);
        
        // Bind parameters with their correct types
        if (!empty($statusFilter)) {
            $stmt->bindParam(':status', $statusFilter, PDO::PARAM_STR);
        }
        
        // IMPORTANT: Bind limit and offset with PDO::PARAM_INT
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
        $appointments = [];
        $totalPages = 0;
    }
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
        <?php if (isset($successMsg)): ?>
            <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        
        <?php if (isset($errorMsg)): ?>
            <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
        <?php endif; ?>
        
        <form method="post" class="admin-form">
            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="booking_reference">Booking Reference</label>
                        <input type="text" id="booking_reference" class="form-control" value="<?php echo htmlspecialchars($appointment['booking_reference']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control status-select status-<?php echo $appointment['status']; ?>" required>
                            <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="service">Service</label>
                        <select name="service" id="service" class="form-control" required>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service); ?>" <?php echo $appointment['service'] === $service ? 'selected' : ''; ?>><?php echo htmlspecialchars($service); ?></option>
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
                                <option value="<?php echo htmlspecialchars($barber['name']); ?>" <?php echo $appointment['barber'] === $barber['name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($barber['name']); ?></option>
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
                <button type="submit" name="update_appointment" class="btn btn-primary"><i class="fas fa-save"></i> Update Appointment</button>
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
        <?php if (isset($successMsg)): ?>
            <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        
        <?php if (isset($errorMsg)): ?>
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
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td>
                                    <div class="booking-reference"><?php echo htmlspecialchars($appointment['booking_reference']); ?></div>
                                </td>
                                <td>
                                    <div class="client-info">
                                        <strong><?php echo htmlspecialchars($appointment['client_name']); ?></strong>
                                        <span class="email"><?php echo htmlspecialchars($appointment['client_email']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="service-name"><?php echo htmlspecialchars($appointment['service']); ?></div>
                                </td>
                                <td>
                                    <div class="appointment-time">
                                        <div class="date"><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></div>
                                        <div class="time"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="barber-name"><?php echo htmlspecialchars($appointment['barber']); ?></div>
                                </td>
                                <td>
                                    <form method="post" id="status-form-<?php echo $appointment['id']; ?>" class="status-form">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <div class="status-badge status-<?php echo $appointment['status']; ?>" 
                                             onclick="toggleStatusDropdown(<?php echo $appointment['id']; ?>)">
                                            <span class="status-text"><?php echo ucfirst($appointment['status']); ?></span>
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
                                        <div class="status-dropdown" id="status-dropdown-<?php echo $appointment['id']; ?>">
                                            <div class="status-option status-pending <?php echo $appointment['status'] === 'pending' ? 'current' : ''; ?>" 
                                                onclick="updateStatus(<?php echo $appointment['id']; ?>, 'pending')">
                                                Pending
                                            </div>
                                            <div class="status-option status-confirmed <?php echo $appointment['status'] === 'confirmed' ? 'current' : ''; ?>" 
                                                onclick="updateStatus(<?php echo $appointment['id']; ?>, 'confirmed')">
                                                Confirmed
                                            </div>
                                            <div class="status-option status-completed <?php echo $appointment['status'] === 'completed' ? 'current' : ''; ?>" 
                                                onclick="updateStatus(<?php echo $appointment['id']; ?>, 'completed')">
                                                Completed
                                            </div>
                                            <div class="status-option status-cancelled <?php echo $appointment['status'] === 'cancelled' ? 'current' : ''; ?>" 
                                                onclick="updateStatus(<?php echo $appointment['id']; ?>, 'cancelled')">
                                                Cancelled
                                            </div>
                                        </div>
                                        <input type="hidden" name="status" id="status-input-<?php echo $appointment['id']; ?>" value="<?php echo $appointment['status']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td class="actions">
                                    <div class="action-buttons">
                                        <a href="?page=appointments&edit=<?php echo $appointment['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?page=appointments&delete=<?php echo $appointment['id']; ?>" class="btn btn-accent btn-sm delete-btn">
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

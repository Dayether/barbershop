<?php
// Include Order model
require_once 'models/Order.php';
require_once 'includes/notifications.php';

// Database connection
$db = new PDO('mysql:host=localhost;dbname=barbershop', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Instantiate Order object
$orderObj = new Order($db);

// Set default view mode to list
$viewMode = 'list';
$order = null;
$orderItems = null;

// Check if edit mode is requested
if (isset($_GET['edit'])) {
    $viewMode = 'edit';
    $id = $_GET['edit'];
    
    // Get order details using the model
    $orderObj->id = $id;
    if ($orderObj->readSingle()) {
        $order = [
            'id' => $orderObj->id,
            'order_reference' => $orderObj->order_reference,
            'user_id' => $orderObj->user_id,
            'total_amount' => $orderObj->total_amount,
            'status' => $orderObj->status,
            'created_at' => $orderObj->created_at,
            'first_name' => $orderObj->first_name,
            'last_name' => $orderObj->last_name,
            'email' => $orderObj->email,
            'address' => $orderObj->address,
            'city' => $orderObj->city,
            'zip' => $orderObj->zip,
            'country' => $orderObj->country,
            'phone' => $orderObj->phone
        ];
        
        // Get order items
        $orderItemsStmt = $orderObj->getOrderItems();
        $orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        setErrorToast("Order not found.");
        $viewMode = 'list';
    }
}

// Get status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
if (is_array($statusFilter)) {
    $statusFilter = isset($statusFilter[0]) ? $statusFilter[0] : '';
}

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    try {
        // Collect form data and sanitize
        $orderObj->id = $_POST['order_id'];
        $orderObj->first_name = htmlspecialchars($_POST['first_name']);
        $orderObj->last_name = htmlspecialchars($_POST['last_name']);
        $orderObj->email = htmlspecialchars($_POST['email']);
        $orderObj->address = htmlspecialchars($_POST['address']);
        $orderObj->city = htmlspecialchars($_POST['city']);
        $orderObj->zip = htmlspecialchars($_POST['zip']);
        $orderObj->country = htmlspecialchars($_POST['country']);
        $orderObj->phone = htmlspecialchars($_POST['phone']);
        $orderObj->status = htmlspecialchars($_POST['status']);
        
        // Update the order using model
        if ($orderObj->update()) {
            setSuccessToast("Order updated successfully!");
            $viewMode = 'list'; // Switch back to list view after update
        } else {
            setErrorToast("Failed to update order.");
        }
    } catch (PDOException $e) {
        setErrorToast("Database error: " . $e->getMessage());
    }
}

// Handle status update from list view
if (isset($_POST['update_status']) && !isset($_POST['update_order'])) {
    try {
        $orderObj->id = $_POST['order_id'];
        $orderObj->status = $_POST['status'];
        
        // Update the status using model
        if ($orderObj->updateStatus()) {
            setSuccessToast("Status updated to " . ucfirst($orderObj->status));
        } else {
            setErrorToast("Failed to update status.");
        }
    } catch (PDOException $e) {
        setErrorToast("Database error: " . $e->getMessage());
    }
}

// Delete order
if (isset($_GET['delete']) && !isset($_GET['confirm_delete'])) {
    $id = $_GET['delete'];
    // Show confirmation modal through JavaScript
    echo "<script>
        if (confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
            window.location.href = '?page=orders&delete={$id}&confirm_delete=1';
        } else {
            window.location.href = '?page=orders';
        }
    </script>";
}

// Process confirmed deletion
if (isset($_GET['delete']) && isset($_GET['confirm_delete'])) {
    $id = $_GET['delete'];
    
    try {
        // Get order details first for the record
        $orderObj->id = $id;
        $orderObj->readSingle();
        $refNumber = $orderObj->order_reference;
        
        // Delete the order using model
        if ($orderObj->delete()) {
            setSuccessToast("Order #$refNumber deleted successfully!");
        } else {
            setErrorToast("Failed to delete order.");
        }
    } catch (PDOException $e) {
        setErrorToast("Database error: " . $e->getMessage());
    }
}

// Handle order item changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_items'])) {
    try {
        $orderObj->id = $_POST['order_id'];
        $success = true;
        
        // Process removed items first
        if (isset($_POST['remove_item']) && is_array($_POST['remove_item'])) {
            foreach ($_POST['remove_item'] as $itemId) {
                if (!$orderObj->removeItem($itemId)) {
                    $success = false;
                }
            }
        }
        
        // Process updated quantities
        if (isset($_POST['item_id']) && is_array($_POST['item_id'])) {
            foreach ($_POST['item_id'] as $index => $itemId) {
                $quantity = $_POST['quantity'][$index];
                if (!$orderObj->updateItem($itemId, $quantity)) {
                    $success = false;
                }
            }
        }
        
        // Recalculate order total after changes
        if ($success && $orderObj->recalculateTotal()) {
            setSuccessToast("Order items updated successfully!");
            // Refresh the page to show updated items
            header("Location: ?page=orders&edit={$orderObj->id}");
            exit();
        } else {
            setErrorToast("Some items could not be updated.");
        }
    } catch (PDOException $e) {
        setErrorToast("Database error: " . $e->getMessage());
    }
}

// Pagination for list view
if ($viewMode === 'list') {
    $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
    $perPage = 10;
    
    // Get total count using model
    $totalOrders = empty($statusFilter) ? $orderObj->count() : $orderObj->count($statusFilter);
    $totalPages = ceil($totalOrders / $perPage);
    
    // Get orders for current page
    $ordersStmt = $orderObj->read($page, $perPage, $statusFilter);
    $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get country list for dropdown
function getCountryList() {
    return [
        'US' => 'United States',
        'CA' => 'Canada',
        'UK' => 'United Kingdom',
        'AU' => 'Australia',
        'DE' => 'Germany',
        'FR' => 'France',
        'JP' => 'Japan',
        'IN' => 'India'
    ];
}
$countries = getCountryList();
?>

<?php if ($viewMode === 'edit' && $order): ?>
<!-- EDIT MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Edit Order #<?php echo htmlspecialchars($order['order_reference']); ?></h2>
        <div class="actions">
            <a href="?page=orders" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
    <div class="admin-card-body">
        <div class="order-summary-panel">
            <div class="order-reference">
                <span class="label">Reference:</span>
                <span class="value"><?php echo htmlspecialchars($order['order_reference']); ?></span>
            </div>
            <div class="order-date">
                <span class="label">Date:</span>
                <span class="value"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="order-total">
                <span class="label">Total:</span>
                <span class="value">$<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
            <div class="order-status">
                <span class="label">Status:</span>
                <span class="value status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
            </div>
        </div>
        
        <form method="post" class="admin-form">
            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
            
            <div class="form-section">
                <h3>Customer Details</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control" value="<?php echo htmlspecialchars($order['first_name']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" value="<?php echo htmlspecialchars($order['last_name']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($order['email']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($order['phone']); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Shipping Address</h3>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" name="address" id="address" class="form-control" value="<?php echo htmlspecialchars($order['address']); ?>" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" name="city" id="city" class="form-control" value="<?php echo htmlspecialchars($order['city']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="zip">ZIP Code</label>
                            <input type="text" name="zip" id="zip" class="form-control" value="<?php echo htmlspecialchars($order['zip']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="country">Country</label>
                    <select name="country" id="country" class="form-control" required>
                        <?php foreach ($countries as $code => $name): ?>
                            <option value="<?php echo $code; ?>" <?php echo $order['country'] === $code ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Order Status</h3>
                <div class="form-group">
                    <select name="status" id="status" class="form-control status-select status-<?php echo $order['status']; ?>" required>
                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" name="update_order" class="btn btn-primary"><i class="fas fa-save"></i> Update Order</button>
                <a href="?page=orders" class="btn btn-outline">Cancel</a>
            </div>
        </form>
        
        <!-- Order Items Section -->
        <div class="form-section">
            <h3>Order Items</h3>
            <form method="post" id="order-items-form">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <div class="table-responsive">
                    <table class="admin-table order-items-table">
                        <thead>
                            <tr>
                                <th width="60">Image</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th width="100">Quantity</th>
                                <th>Subtotal</th>
                                <th width="60">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($orderItems) > 0): ?>
                                <?php foreach ($orderItems as $index => $item): ?>
                                    <tr>
                                        <td>
                                            <div class="product-image">
                                                <img src="<?php echo htmlspecialchars($item['product_image'] ?? '../images/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="product-name">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            $<?php echo number_format($item['price'], 2); ?>
                                        </td>
                                        <td>
                                            <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="quantity[]" min="1" value="<?php echo $item['quantity']; ?>" class="form-control item-quantity">
                                        </td>
                                        <td>
                                            <div class="subtotal">
                                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <label class="checkbox-container">
                                                <input type="checkbox" name="remove_item[]" value="<?php echo $item['id']; ?>">
                                                <span class="checkmark"></span>
                                                <span class="remove-text">Remove</span>
                                            </label>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                    <td colspan="2"><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No items in this order</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($orderItems) > 0): ?>
                    <div class="form-group text-right">
                        <button type="submit" name="update_items" class="btn btn-primary btn-sm">
                            <i class="fas fa-sync-alt"></i> Update Items
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php else: ?>
<!-- LIST MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Order Management</h2>
        <div class="actions">
            <a href="?page=orders" class="btn btn-outline btn-sm <?php echo empty($statusFilter) ? 'active' : ''; ?>">All</a>
            <a href="?page=orders&status=pending" class="btn btn-outline btn-sm <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?page=orders&status=processing" class="btn btn-outline btn-sm <?php echo $statusFilter === 'processing' ? 'active' : ''; ?>">Processing</a>
            <a href="?page=orders&status=completed" class="btn btn-outline btn-sm <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">Completed</a>
            <a href="?page=orders&status=cancelled" class="btn btn-outline btn-sm <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
        </div>
    </div>
    <div class="admin-card-body">
        <div class="table-responsive orders-table">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $row): ?>
                            <tr>
                                <td>
                                    <div class="order-reference"><?php echo htmlspecialchars($row['order_reference']); ?></div>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong>
                                        <span class="email"><?php echo htmlspecialchars($row['email']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-date">
                                        <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                        <div class="time"><?php echo date('h:i A', strtotime($row['created_at'])); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-amount">$<?php echo number_format($row['total_amount'], 2); ?></div>
                                </td>
                                <td>
                                    <form method="post" id="status-form-<?php echo $row['id']; ?>" class="status-form">
                                        <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                        <div class="status-badge status-<?php echo $row['status']; ?>" 
                                             onclick="toggleStatusDropdown(<?php echo $row['id']; ?>)">
                                            <span class="status-text"><?php echo ucfirst($row['status']); ?></span>
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
                                        <div class="status-dropdown" id="status-dropdown-<?php echo $row['id']; ?>">
                                            <div class="status-option status-pending <?php echo $row['status'] === 'pending' ? 'current' : ''; ?>" 
                                                onclick="updateStatus(<?php echo $row['id']; ?>, 'pending')">
                                                Pending
                                            </div>
                                            <div class="status-option status-processing <?php echo $row['status'] === 'processing' ? 'current' : ''; ?>" 
                                                onclick="updateStatus(<?php echo $row['id']; ?>, 'processing')">
                                                Processing
                                            </div>
                                            <div class="status-option status-completed <?php echo $row['status'] === 'completed' ? 'current' : ''; ?>" 
                                                onclick="updateStatus(<?php echo $row['id']; ?>, 'completed')">
                                                Completed
                                            </div>
                                            <div class="status-option status-cancelled <?php echo $row['status'] === 'cancelled' ? 'current' : ''; ?>" 
                                                onclick="updateStatus(<?php echo $row['id']; ?>, 'cancelled')">
                                                Cancelled
                                            </div>
                                        </div>
                                        <input type="hidden" name="status" id="status-input-<?php echo $row['id']; ?>" value="<?php echo $row['status']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td class="actions">
                                    <div class="action-buttons">
                                        <a href="?page=orders&edit=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?page=orders&delete=<?php echo $row['id']; ?>" class="btn btn-accent btn-sm delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-shopping-cart"></i>
                                    <p>No orders found</p>
                                    <small>Orders will appear here after customers make purchases</small>
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
                    <li><a href="?page=orders<?php echo !empty($statusFilter) ? '&status=' . urlencode((string)$statusFilter) : ''; ?>&p=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                <?php endif; ?>
                
                <?php 
                // Display a limited number of pages with ellipsis for better UX
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                if ($startPage > 1) {
                    echo '<li><a href="?page=orders' . (!empty($statusFilter) ? '&status=' . urlencode((string)$statusFilter) : '') . '&p=1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                }

                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <a href="?page=orders<?php echo !empty($statusFilter) ? '&status=' . urlencode((string)$statusFilter) : ''; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; 

                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                    echo '<li><a href="?page=orders' . (!empty($statusFilter) ? '&status=' . urlencode((string)$statusFilter) : '') . '&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <?php if ($page < $totalPages): ?>
                    <li><a href="?page=orders<?php echo !empty($statusFilter) ? '&status=' . urlencode((string)$statusFilter) : ''; ?>&p=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<style>
/* Order Management Styling */
.orders-table {
    margin-top: 20px;
    border-radius: 10px;
}

/* Order Reference styling */
.order-reference {
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

/* Customer info styling */
.customer-info {
    display: flex;
    flex-direction: column;
}

.customer-info strong {
    color: var(--secondary-color);
    margin-bottom: 3px;
}

.customer-info .email {
    color: var(--text-muted);
    font-size: 13px;
}

/* Order date styling */
.order-date {
    font-weight: 500;
    color: var(--secondary-color);
}

.order-date .time {
    color: var(--text-muted);
    font-size: 13px;
}

/* Order amount styling */
.order-amount {
    font-weight: 600;
    color: var(--secondary-color);
}

/* Status styling for orders */
.status-badge.status-processing,
.status-option.status-processing {
    background-color: rgba(33, 150, 243, 0.1);
    color: #2196F3;
    border-left: 4px solid #2196F3;
}

/* Order Edit Page Specific Styles */
.order-summary-panel {
    display: flex;
    flex-wrap: wrap;
    background: linear-gradient(to right, #f9f9f9, #f1f1f1);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 30px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.order-summary-panel > div {
    margin-right: 30px;
    margin-bottom: 10px;
}

.order-summary-panel .label {
    display: block;
    font-size: 12px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 5px;
}

.order-summary-panel .value {
    font-size: 18px;
    font-weight: 600;
    color: var(--secondary-color);
}

/* Form section styling */
.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.form-section h3 {
    font-size: 18px;
    color: var(--secondary-color);
    margin-bottom: 15px;
    padding-left: 10px;
    border-left: 4px solid var(--primary-color);
}

/* Order Items Table */
.order-items-table {
    margin-bottom: 20px;
}

.order-items-table .product-image {
    width: 50px;
    height: 50px;
    overflow: hidden;
    border-radius: 4px;
}

.order-items-table .product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.order-items-table .product-name {
    font-weight: 500;
    color: var(--secondary-color);
}

.order-items-table .item-quantity {
    width: 70px;
    text-align: center;
    margin: 0 auto;
}

.order-items-table .subtotal {
    font-weight: 600;
}

.order-items-table .total-row {
    background: rgba(0,0,0,0.02);
}

.order-items-table .total-row td {
    padding-top: 12px;
    padding-bottom: 12px;
}

/* Checkbox container for remove item */
.checkbox-container {
    display: flex;
    align-items: center;
    position: relative;
    cursor: pointer;
    user-select: none;
}

.checkbox-container input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.checkmark {
    position: relative;
    display: inline-block;
    height: 18px;
    width: 18px;
    background-color: #fff;
    border: 2px solid #ccc;
    border-radius: 3px;
    margin-right: 5px;
    transition: all 0.3s ease;
}

.checkbox-container:hover input ~ .checkmark {
    border-color: #aaa;
}

.checkbox-container input:checked ~ .checkmark {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
}

.checkbox-container input:checked ~ .checkmark:after {
    display: block;
}

.checkbox-container .checkmark:after {
    left: 5px;
    top: 1px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.checkbox-container .remove-text {
    color: var(--accent-color);
    font-size: 12px;
    font-weight: 500;
}
</style>

<script>
// Status dropdown functionality for orders
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

// Update status function for orders
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

// Update subtotals when quantity changes
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to quantity inputs
    const quantityInputs = document.querySelectorAll('.item-quantity');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Get the price from the row
            const row = this.closest('tr');
            const price = parseFloat(row.querySelector('td:nth-child(3)').innerText.replace('$', ''));
            const quantity = parseInt(this.value);
            
            // Update subtotal
            const subtotal = price * quantity;
            row.querySelector('.subtotal').innerText = '$' + subtotal.toFixed(2);
        });
    });
    
    // Close any open dropdowns when clicking outside
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
</script>

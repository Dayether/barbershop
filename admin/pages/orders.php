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
        // Use null coalescing operator to avoid undefined index warnings
        $order = [
            'id' => $orderObj->id ?? '',
            'order_reference' => $orderObj->order_reference ?? '',
            'user_id' => $orderObj->user_id ?? '',
            'total_amount' => $orderObj->total_amount ?? 0,
            'status' => $orderObj->status ?? '',
            'created_at' => $orderObj->created_at ?? '',
            'first_name' => $orderObj->first_name ?? '',
            'last_name' => $orderObj->last_name ?? '',
            'email' => $orderObj->email ?? '',
            'address' => $orderObj->address ?? '',
            'city' => $orderObj->city ?? '',
            'zip' => $orderObj->zip ?? '',
            'country' => $orderObj->country ?? '',
            'phone' => $orderObj->phone ?? ''
        ];

        // Get order items
        $orderItemsStmt = $orderObj->getOrderItems();
        $orderItems = $orderItemsStmt ? $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
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

// Handle edit form submission (update all fields, like products/barbers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    try {
        $orderObj->id = $_POST['order_id'];
        $orderObj->status = $_POST['status'];
        $orderObj->first_name = $orderObj->first_name ?? '';
        $orderObj->last_name = $orderObj->last_name ?? '';
        $orderObj->email = $orderObj->email ?? '';
        $orderObj->address = $orderObj->address ?? '';
        $orderObj->city = $orderObj->city ?? '';
        $orderObj->zip = $orderObj->zip ?? '';
        $orderObj->country = $orderObj->country ?? '';
        $orderObj->phone = $orderObj->phone ?? '';

        // Always update all fields, even if only status is editable
        // (This ensures the update() method works like products/barbers)
        $success = $orderObj->update();

        if ($success) {
            setSuccessToast("Order updated successfully!");
            header("Location: ?page=orders&edit={$orderObj->id}");
            exit();
        } else {
            setErrorToast("Failed to update order.");
        }
    } catch (PDOException $e) {
        setErrorToast("Database error: " . $e->getMessage());
    }
}

// Delete order (from action section or anywhere)
if (isset($_GET['delete']) && !isset($_GET['confirm_delete'])) {
    $id = $_GET['delete'];
    // Remove the JavaScript confirm alert, just redirect to confirmation step
    header("Location: ?page=orders&delete={$id}&confirm_delete=1");
    exit;
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
    // Redirect to orders list after deletion to avoid resubmission
    header("Location: ?page=orders");
    exit;
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
            <?php
                // Calculate subtotal, tax, shipping, and total
                $subtotal = 0;
                if (is_array($orderItems)) {
                    foreach ($orderItems as $item) {
                        $subtotal += $item['price'] * $item['quantity'];
                    }
                }
                $shipping = 5.00;
                $tax = $subtotal * 0.08;
                $total = $subtotal + $shipping + $tax;
            ?>
            <div class="order-total">
                <span class="label">Subtotal:</span>
                <span class="value">$<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="order-total">
                <span class="label">Shipping:</span>
                <span class="value">$<?php echo number_format($shipping, 2); ?></span>
            </div>
            <div class="order-total">
                <span class="label">Tax (8%):</span>
                <span class="value">$<?php echo number_format($tax, 2); ?></span>
            </div>
            <div class="order-total" style="font-weight:bold;">
                <span class="label">Total:</span>
                <span class="value">$<?php echo number_format($total, 2); ?></span>
            </div>
            <div class="order-status">
                <span class="label">Status:</span>
                <span class="value status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
            </div>
        </div>

        <!-- Only allow editing status, but include hidden fields for all customer info -->
        <form method="post" class="admin-form" id="order-edit-form">
            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
            <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($order['first_name'] ?? ''); ?>">
            <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($order['last_name'] ?? ''); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($order['email'] ?? ''); ?>">
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($order['phone'] ?? ''); ?>">
            <input type="hidden" name="address" value="<?php echo htmlspecialchars($order['address'] ?? ''); ?>">
            <input type="hidden" name="city" value="<?php echo htmlspecialchars($order['city'] ?? ''); ?>">
            <input type="hidden" name="zip" value="<?php echo htmlspecialchars($order['zip'] ?? ''); ?>">
            <input type="hidden" name="country" value="<?php echo htmlspecialchars($order['country'] ?? ''); ?>">

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

            <div class="form-group text-right">
                <button type="submit" name="update_order" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
                <a href="?page=orders" class="btn btn-outline">Cancel</a>
                <a href="?page=orders&delete=<?php echo $order['id']; ?>" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Order
                </a>
            </div>
        </form>

        <!-- Show customer details as read-only -->
        <div class="form-section">
            <h3>Customer Details</h3>
            <div><strong>Name:</strong> <?php echo htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')); ?></div>
            <div><strong>Email:</strong> <?php echo htmlspecialchars($order['email'] ?? ''); ?></div>
            <div><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?? ''); ?></div>
            <div><strong>Address:</strong> <?php echo htmlspecialchars($order['address'] ?? ''); ?>, <?php echo htmlspecialchars($order['city'] ?? ''); ?>, <?php echo htmlspecialchars($order['zip'] ?? ''); ?>, <?php echo htmlspecialchars($order['country'] ?? ''); ?></div>
        </div>

        <!-- Show order items as read-only -->
        <div class="form-section">
            <h3>Order Items</h3>
            <div class="table-responsive">
                <table class="admin-table order-items-table">
                    <thead>
                        <tr>
                            <th width="60">Image</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th width="100">Quantity</th>
                            <th>Subtotal</th>
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
                                        <?php echo (int)$item['quantity']; ?>
                                    </td>
                                    <td>
                                        <div class="subtotal">
                                            $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                                <td><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="4" class="text-right"><strong>Shipping:</strong></td>
                                <td><strong>$<?php echo number_format($shipping, 2); ?></strong></td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="4" class="text-right"><strong>Tax (8%):</strong></td>
                                <td><strong>$<?php echo number_format($tax, 2); ?></strong></td>
                            </tr>
                            <tr class="total-row" style="font-weight:bold;">
                                <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No items in this order</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- LIST MODE -->
<div class="admin-card">
    <div class="admin-card-header"></div>
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
                            <?php
                                // Calculate subtotal from order_items for this order
                                $order_id = $row['order_id'];
                                $subtotal = 0;
                                $orderItemsStmt = $orderObj->getOrderItemsByOrderId($order_id);
                                $orderItems = $orderItemsStmt ? $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
                                foreach ($orderItems as $item) {
                                    $subtotal += $item['price'] * $item['quantity'];
                                }
                                $shipping = 5.00;
                                $tax = $subtotal * 0.08;
                                $total = $subtotal + $shipping + $tax;
                            ?>
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
                                    <div class="order-amount">
                                        $<?php echo number_format($total, 2); ?>
                                        <div class="order-amount-breakdown" style="font-size:12px;color:#888;">
                                            <span>Subtotal: $<?php echo number_format($subtotal, 2); ?></span><br>
                                            <span>Shipping: $<?php echo number_format($shipping, 2); ?></span><br>
                                            <span>Tax: $<?php echo number_format($tax, 2); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($row['status']); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <div class="action-buttons">
                                        <a href="?page=orders&edit=<?php echo $row['order_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?page=orders&delete=<?php echo $row['order_id']; ?>" class="btn btn-accent btn-sm delete-btn">
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

.order-summary_panel .label {
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

<?php
// Add this helper method to your Order model if not present:
if (!method_exists($orderObj, 'getOrderItemsByOrderId')) {
    /**
     * Get order items for a specific order_id
     */
    $orderObj->getOrderItemsByOrderId = function($order_id) use ($db) {
        $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        return $stmt;
    };
}
?>

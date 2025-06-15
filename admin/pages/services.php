<?php
require_once '../database.php';
require_once 'includes/notifications.php';

$db = new Database();

$viewMode = 'list';
$service = null;
$errorMsg = '';
$successMsg = '';
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 10;

// Edit mode
if (isset($_GET['edit'])) {
    $viewMode = 'edit';
    $id = (int)$_GET['edit'];
    $service = $db->getServiceById($id);
    if (!$service) {
        setErrorToast("Service not found.");
        $viewMode = 'list';
    }
}

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $updateData = [
        'service_id' => $_POST['service_id'],
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'duration' => $_POST['duration'],
        'price' => $_POST['price'],
        'active' => isset($_POST['active']) ? 1 : 0,
        'image' => $_FILES['image'] ?? null
    ];
    $result = $db->updateService($updateData);
    if ($result['success']) {
        setSuccessToast("Service updated successfully!");
        $viewMode = 'list';
    } else {
        setErrorToast($result['error_message']);
    }
}

// Handle new service creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_service'])) {
    $createData = [
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'duration' => $_POST['duration'],
        'price' => $_POST['price'],
        'active' => isset($_POST['active']) ? 1 : 0,
        'image' => $_FILES['image'] ?? null
    ];
    $result = $db->createService($createData);
    if ($result['success']) {
        setSuccessToast("Service created successfully!");
    } else {
        setErrorToast($result['error_message']);
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['confirm_delete'])) {
    $id = (int)$_GET['delete'];
    $result = $db->deleteService($id);
    if ($result['success']) {
        setSuccessToast("Service deleted successfully!");
        header("Location: ?page=services");
        exit;
    } else {
        setErrorToast($result['error_message']);
    }
}

// Handle toggle active status
if (isset($_POST['toggle_active'])) {
    $id = (int)$_POST['service_id'];
    $currentStatus = $_POST['current_status'] == 1 ? 1 : 0;
    $newStatus = $currentStatus == 1 ? 0 : 1;
    $result = $db->updateServiceStatus($id, $newStatus);
    if ($result['success']) {
        // Status updated successfully
    } else {
        setErrorToast($result['error_message']);
    }
}

// Pagination for list view
if ($viewMode === 'list') {
    $totalServices = $db->countServices(); // <-- fix: call as method
    $totalPages = ceil($totalServices / $perPage);
    $offset = ($page - 1) * $perPage;
    $services = $db->getServices($perPage, $offset);
}

// If we're in "new" mode, set viewMode
if (isset($_GET['new'])) {
    $viewMode = 'new';
}
?>

<?php if ($viewMode === 'edit' && $service): ?>
<!-- EDIT MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Edit Service</h2>
        <div class="actions">
            <a href="?page=services" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
    <div class="admin-card-body">
        <form method="post" class="admin-form" enctype="multipart/form-data">
            <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name">Service Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($service['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($service['description']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="duration">Duration (minutes)</label>
                                <input type="number" id="duration" name="duration" class="form-control" value="<?php echo (int)$service['duration']; ?>" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="price">Price ($)</label>
                                <input type="number" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($service['price']); ?>" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="active" name="active" class="form-check-input" <?php echo $service['active'] ? 'checked' : ''; ?>>
                            <label for="active" class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="image">Service Image</label>
                        <div class="image-upload-container">
                            <?php if (!empty($service['image'])): ?>
                                <div class="current-image">
                                    <img src="<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" id="imagePreview">
                                </div>
                            <?php else: ?>
                                <div class="no-image" id="imagePreview">
                                    <i class="fas fa-image"></i>
                                    <p>No image available</p>
                                </div>
                            <?php endif; ?>
                            <input type="file" id="image" name="image" class="form-control-file image-upload" accept="image/*" data-preview="imagePreview">
                            <label for="image" class="btn btn-outline btn-sm mt-2">
                                <i class="fas fa-upload"></i> Change Image
                            </label>
                            <small class="form-text text-muted">Leave empty to keep current image. Recommended size: 600x600px.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group mt-4">
                <button type="submit" name="update_service" class="btn btn-primary"><i class="fas fa-save"></i> Update Service</button>
                <a href="?page=services" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($viewMode === 'new'): ?>
<!-- NEW SERVICE MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Add New Service</h2>
        <div class="actions">
            <a href="?page=services" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
    <div class="admin-card-body">
        <form method="post" class="admin-form" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name">Service Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="duration">Duration (minutes)</label>
                                <input type="number" id="duration" name="duration" class="form-control" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="price">Price ($)</label>
                                <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="active" name="active" class="form-check-input" checked>
                            <label for="active" class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="image">Service Image</label>
                        <div class="image-upload-container">
                            <div class="no-image" id="imagePreview">
                                <i class="fas fa-image"></i>
                                <p>No image selected</p>
                            </div>
                            <input type="file" id="image" name="image" class="form-control-file image-upload" accept="image/*" data-preview="imagePreview">
                            <label for="image" class="btn btn-outline btn-sm mt-2">
                                <i class="fas fa-upload"></i> Select Image
                            </label>
                            <small class="form-text text-muted">Recommended size: 600x600px.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group mt-4">
                <button type="submit" name="create_service" class="btn btn-primary"><i class="fas fa-plus"></i> Add Service</button>
                <a href="?page=services" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- LIST MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Service Management</h2>
        <div class="actions">
            <a href="?page=services&new=1" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Service</a>
        </div>
    </div>
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>
                                    <div class="product-image">
                                        <img src="<?php echo htmlspecialchars($service['image'] ?: 'images/service-placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>">
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                <td><?php echo htmlspecialchars($service['description']); ?></td>
                                <td><?php echo (int)$service['duration']; ?> min</td>
                                <td>$<?php echo number_format($service['price'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $service['active'] == 1 ? 'active' : 'inactive'; ?>">
                                        <?php echo $service['active'] == 1 ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <div class="action-buttons">
                                        <a href="?page=services&edit=<?php echo $service['service_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?page=services&delete=<?php echo $service['service_id']; ?>" class="btn btn-accent btn-sm delete-btn">
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
                                    <i class="fas fa-concierge-bell"></i>
                                    <p>No services found</p>
                                    <small>Add your first service to get started</small>
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
                    <li><a href="?page=services&p=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                <?php endif; ?>
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                if ($startPage > 1) {
                    echo '<li><a href="?page=services&p=1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                }
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <a href="?page=services&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor;
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                    echo '<li><a href="?page=services&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                }
                ?>
                <?php if ($page < $totalPages): ?>
                    <li><a href="?page=services&p=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image preview for upload
    const imageInputs = document.querySelectorAll('.image-upload');
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (preview.classList.contains('no-image')) {
                        preview.innerHTML = '';
                        preview.classList.remove('no-image');
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        preview.appendChild(img);
                    } else {
                        const img = preview.querySelector('img') || document.createElement('img');
                        img.src = e.target.result;
                        if (!preview.contains(img)) {
                            preview.appendChild(img);
                        }
                    }
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Delete button confirmation
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
                title: 'Delete Service',
                message: 'Are you sure you want to delete this service?',
                position: 'center',
                buttons: [
                    ['<button><b>Yes, Delete</b></button>', function (instance, toast) {
                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        window.location.href = deleteUrl + '&confirm_delete=1';
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
<style>
.product-image {
    width: 60px;
    height: 60px;
    border-radius: 6px;
    overflow: hidden;
    background-color: var(--light-bg);
    display: flex;
    align-items: center;
    justify-content: center;
}
.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>

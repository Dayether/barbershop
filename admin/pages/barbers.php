<?php
require_once '../database.php';
require_once 'includes/notifications.php'; // <-- Add this if not already present
$db = new Database();

$viewMode = 'list';
$barber = null;
$barbers = [];
$errorMsg = '';
$successMsg = '';
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 10;

// Edit mode
if (isset($_GET['edit'])) {
    $viewMode = 'edit';
    $id = (int)$_GET['edit'];
    $barber = $db->getBarberById($id);
    if (!$barber) {
        $errorMsg = "Barber not found.";
        $viewMode = 'list';
    }
}

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_barber'])) {
    $updateData = [
        'barber_id' => $_POST['barber_id'],
        'name' => $_POST['name'],
        'bio' => $_POST['bio'],
        'active' => isset($_POST['active']) ? 1 : 0,
        'image' => $_FILES['image'] ?? null
    ];
    $result = $db->updateBarber($updateData);
    if ($result['success']) {
        setSuccessToast("Barber updated successfully!");
        $barber = $db->getBarberById($updateData['barber_id']);
        $viewMode = 'list';
        header("Location: ?page=barbers");
        exit();
    } else {
        setErrorToast($result['error_message']);
    }
}

// Handle new barber creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_barber'])) {
    $createData = [
        'name' => $_POST['name'],
        'bio' => $_POST['bio'],
        'active' => isset($_POST['active']) ? 1 : 0,
        'image' => $_FILES['image'] ?? null
    ];
    $result = $db->createBarber($createData);
    if ($result['success']) {
        setSuccessToast("Barber added successfully!");
        header("Location: ?page=barbers");
        exit();
    } else {
        setErrorToast($result['error_message']);
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['confirm_delete'])) {
    $id = (int)$_GET['delete'];
    $result = $db->deleteBarber($id);
    if ($result['success']) {
        setSuccessToast("Barber deleted successfully!");
    } else {
        setErrorToast($result['error_message']);
    }
    header("Location: ?page=barbers");
    exit();
}

// Handle toggle active status
if (isset($_POST['toggle_active'])) {
    $id = (int)$_POST['barber_id'];
    $result = $db->toggleBarberActive($id);
    if ($result['success']) {
        $successMsg = "Barber status updated.";
    } else {
        $errorMsg = $result['error_message'];
    }
}

// Pagination for list view
if ($viewMode === 'list') {
    $totalBarbers = $db->countBarbers();
    $totalPages = ceil($totalBarbers / $perPage);
    $offset = ($page - 1) * $perPage;
    $barbers = $db->getBarbers($perPage, $offset);
}

// If we're in "new" mode, set viewMode
if (isset($_GET['new'])) {
    $viewMode = 'new';
}
?>

<?php if ($viewMode === 'edit' && $barber): ?>
<!-- EDIT MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Edit Barber</h2>
        <div class="actions">
            <a href="?page=barbers" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
    <div class="admin-card-body">
        <form method="post" class="admin-form" enctype="multipart/form-data">
            <input type="hidden" name="barber_id" value="<?php echo $barber['id']; ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name">Barber Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($barber['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio/Description</label>
                        <textarea id="bio" name="bio" class="form-control" rows="4" required><?php echo htmlspecialchars($barber['bio']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="active" name="active" class="form-check-input" <?php echo $barber['active'] ? 'checked' : ''; ?>>
                            <label for="active" class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="image">Barber Image</label>
                        <div class="image-upload-container">
                            <?php if (!empty($barber['image'])): ?>
                                <div class="current-image">
                                    <img src="<?php echo htmlspecialchars($barber['image']); ?>" alt="<?php echo htmlspecialchars($barber['name']); ?>" id="imagePreview">
                                </div>
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-user"></i>
                                    <p>No image available</p>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" id="image" name="image" class="form-control-file image-upload" accept="image/*" data-preview="imagePreview">
                            <label for="image" class="btn btn-outline btn-sm mt-2">
                                <i class="fas fa-upload"></i> Change Image
                            </label>
                            <small class="form-text text-muted">Leave empty to keep current image. Recommended size: 600x800px.</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" name="update_barber" class="btn btn-primary"><i class="fas fa-save"></i> Update Barber</button>
                <a href="?page=barbers" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($viewMode === 'new'): ?>
<!-- NEW BARBER MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Add New Barber</h2>
        <div class="actions">
            <a href="?page=barbers" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
    <div class="admin-card-body">
        <form method="post" class="admin-form" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name">Barber Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio/Description</label>
                        <textarea id="bio" name="bio" class="form-control" rows="4" required></textarea>
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
                        <label for="image">Barber Image</label>
                        <div class="image-upload-container">
                            <div class="no-image" id="imagePreview">
                                <i class="fas fa-user"></i>
                                <p>No image selected</p>
                            </div>
                            
                            <input type="file" id="image" name="image" class="form-control-file image-upload" accept="image/*" data-preview="imagePreview">
                            <label for="image" class="btn btn-outline btn-sm mt-2">
                                <i class="fas fa-upload"></i> Select Image
                            </label>
                            <small class="form-text text-muted">Recommended size: 600x800px.</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" name="create_barber" class="btn btn-primary"><i class="fas fa-plus"></i> Add Barber</button>
                <a href="?page=barbers" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- LIST MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Barber Management</h2>
        <div class="actions">
            <a href="?page=barbers&new=1" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Barber</a>
        </div>
    </div>
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Bio</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($barbers) > 0): ?>
    <?php foreach ($barbers as $row): ?>
        <tr>
            <td>
                <div class="barber-image">
                    <img src="<?php echo htmlspecialchars($row['image'] ?: 'images/barber-placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                </div>
            </td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['bio']); ?></td>
            <td>
                <span class="status-badge status-<?php echo $row['active'] == 1 ? 'active' : 'inactive'; ?>">
                    <?php echo $row['active'] == 1 ? 'Active' : 'Inactive'; ?>
                </span>
            </td>
            <td class="actions">
                <div class="action-buttons">
                    <a href="?page=barbers&edit=<?php echo $row['barber_id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="?page=barbers&delete=<?php echo $row['barber_id']; ?>" class="btn btn-accent btn-sm delete-btn">
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
                                    <i class="fas fa-user-slash"></i>
                                    <p>No barbers found</p>
                                    <small>Add your first barber to get started</small>
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
                    <li><a href="?page=barbers&p=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                <?php endif; ?>
                
                <?php 
                // Display a limited number of pages with ellipsis for better UX
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<li><a href="?page=barbers&p=1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                }
                
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <a href="?page=barbers&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; 
                
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                    echo '<li><a href="?page=barbers&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <?php if ($page < $totalPages): ?>
                    <li><a href="?page=barbers&p=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a></li>
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
                    // If there's a "no-image" div, replace it with an img
                    if (preview.classList.contains('no-image')) {
                        preview.innerHTML = '';
                        preview.classList.remove('no-image');
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        preview.appendChild(img);
                    } else {
                        // If there's already an img, just update the src
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
});

// Function to submit status toggle form
function submitStatusForm(id) {
    const form = document.getElementById('status-form-' + id);
    const statusBadge = form.querySelector('.status-badge');
    
    // Show loading state
    const originalHTML = statusBadge.innerHTML;
    statusBadge.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    // Submit the form
    form.submit();
}
</script>

<style>
/* Barber-specific styling */
.barber-image {
    border-radius: 6px !important;  /* Override the product image styling */
}

.product-info.description {
    color: var(--text-muted);
    font-size: 0.85rem;
    line-height: 1.4;
}

/* Image upload container for barbers */
.image-upload-container .current-image {
    border-radius: 6px;
}

.image-upload-container .no-image {
    border-radius: 6px;
}
</style>
    

<style>
/* Barber-specific styling */
.barber-image {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    background-color: var(--light-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.barber-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image-small {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 1.5rem;
}

.barber-name-cell {
    font-weight: 600;
    color: var(--secondary-color);
}

.barber-bio {
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.4;
}

/* Image upload container for barbers */
.image-upload-container .current-image {
    border-radius: 50%;
}

.image-upload-container .no-image {
    border-radius: 50%;
}
</style>
                        
          
</script>

<style>
/* Barber-specific styling */
.barber-image {
    border-radius: 6px !important;  /* Override the product image styling */
}

.product-info.description {
    color: var(--text-muted);
    font-size: 0.85rem;
    line-height: 1.4;
}

/* Image upload container for barbers */
.image-upload-container .current-image {
    border-radius: 6px;
}

.image-upload-container .no-image {
    border-radius: 6px;
}
</style>
    

<style>
/* Barber-specific styling */
.barber-image {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    background-color: var(--light-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.barber-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image-small {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 1.5rem;
}

.barber-name-cell {
    font-weight: 600;
    color: var(--secondary-color);
}

.barber-bio {
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.4;
}

/* Image upload container for barbers */
.image-upload-container .current-image {
    border-radius: 50%;
}

.image-upload-container .no-image {
    border-radius: 50%;
}
</style>

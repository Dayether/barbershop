<?php
require_once '../database.php';
require_once 'includes/notifications.php';

$db = new Database();

$viewMode = 'list';
$product = null;
$errorMsg = '';
$successMsg = '';
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 10;

// Edit mode
if (isset($_GET['edit'])) {
    $viewMode = 'edit';
    $id = (int)$_GET['edit'];
    $product = $db->getProductById($id);
    if (!$product) {
        setErrorToast("Product not found.");
        $viewMode = 'list';
    }
}

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $updateData = [
        'product_id' => $_POST['product_id'],
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'stock' => $_POST['stock'],
        'active' => isset($_POST['active']) ? 1 : 0,
        'image' => $_FILES['image'] ?? null
    ];
    $result = $db->updateProduct($updateData);
    if ($result['success']) {
        setSuccessToast("Product updated successfully!");
        $viewMode = 'list';
    } else {
        setErrorToast($result['error_message']);
    }
}

// Handle new product creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_product'])) {
    $createData = [
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'stock' => $_POST['stock'],
        'active' => isset($_POST['active']) ? 1 : 0,
        'image' => $_FILES['image'] ?? null
    ];
    $result = $db->createProduct($createData);
    if ($result['success']) {
        setSuccessToast("Product created successfully!");
    } else {
        setErrorToast($result['error_message']);
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['confirm_delete'])) {
    $id = (int)$_GET['delete'];
    $result = $db->deleteProduct($id);
    if ($result['success']) {
        setSuccessToast("Product deleted successfully!");
    } else {
        setErrorToast($result['error_message']);
    }
}

// Handle toggle active status
if (isset($_POST['toggle_active'])) {
    $id = (int)$_POST['product_id'];
    $result = $db->toggleProductActive($id);
    if ($result['success']) {
        setSuccessToast("Product status updated.");
    } else {
        setErrorToast($result['error_message']);
    }
}

// Pagination for list view
if ($viewMode === 'list') {
    $totalProducts = $db->countProducts();
    $totalPages = ceil($totalProducts / $perPage);
    $offset = ($page - 1) * $perPage;
    $products = $db->getProducts($perPage, $offset);
}

// If we're in "new" mode, set viewMode
if (isset($_GET['new'])) {
    $viewMode = 'new';
}
?>

<?php if ($viewMode === 'edit' && $product): ?>
<!-- EDIT MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Edit Product</h2>
        <div class="actions">
            <a href="?page=products" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
    <div class="admin-card-body">
        <form method="post" class="admin-form" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="price">Price ($)</label>
                                <input type="number" id="price" name="price" class="form-control" value="<?php echo htmlspecialchars($product['price']); ?>" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="stock">Stock</label>
                                <input type="number" id="stock" name="stock" class="form-control" value="<?php echo htmlspecialchars($product['stock']); ?>" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="active" name="active" class="form-check-input" <?php echo $product['active'] ? 'checked' : ''; ?>>
                            <label for="active" class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <div class="image-upload-container">
                            <?php if (!empty($product['image'])): ?>
                                <div class="current-image">
                                    <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="imagePreview">
                                </div>
                            <?php else: ?>
                                <div class="no-image">
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
                <button type="submit" name="update_product" class="btn btn-primary"><i class="fas fa-save"></i> Update Product</button>
                <a href="?page=products" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($viewMode === 'new'): ?>
<!-- NEW PRODUCT MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Add New Product</h2>
        <div class="actions">
            <a href="?page=products" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
    <div class="admin-card-body">
        <form method="post" class="admin-form" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="price">Price ($)</label>
                                <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="stock">Stock</label>
                                <input type="number" id="stock" name="stock" class="form-control" min="0" value="0" required>
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
                        <label for="image">Product Image</label>
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
                <button type="submit" name="create_product" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</button>
                <a href="?page=products" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- LIST MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Product Management</h2>
        <div class="actions">
            <a href="?page=products&new=1" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Product</a>
        </div>
    </div>
    <div class="admin-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
    <?php foreach ($products as $row): ?>
        <tr>
            <td>
                <div class="product-image">
                    <img src="<?php echo htmlspecialchars($row['image'] ?: 'images/product-placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                </div>
            </td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td>$<?php echo number_format($row['price'], 2); ?></td>
            <td><?php echo (int)$row['stock']; ?></td>
            <td>
                <span class="status-badge status-<?php echo $row['active'] == 1 ? 'active' : 'inactive'; ?>">
                    <?php echo $row['active'] == 1 ? 'Active' : 'Inactive'; ?>
                </span>
            </td>
            <td class="actions">
                <div class="action-buttons">
                    <a href="?page=products&edit=<?php echo $row['product_id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="?page=products&delete=<?php echo $row['product_id']; ?>" class="btn btn-accent btn-sm delete-btn">
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
                                    <i class="fas fa-box-open"></i>
                                    <p>No products found</p>
                                    <small>Add your first product to get started</small>
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
                    <li><a href="?page=products&p=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                <?php endif; ?>
                
                <?php 
                // Display a limited number of pages with ellipsis for better UX
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<li><a href="?page=products&p=1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                }
                
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <a href="?page=products&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; 
                
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                    echo '<li><a href="?page=products&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <?php if ($page < $totalPages): ?>
                    <li><a href="?page=products&p=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a></li>
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
    
    // Custom delete button handler for products page
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Delete button clicked');
            
            const deleteUrl = this.getAttribute('href');
            const productName = this.getAttribute('data-item-name') || 'this product';
            
            iziToast.question({
                timeout: false,
                close: false,
                overlay: true,
                displayMode: 'once',
                id: 'question',
                zindex: 999,
                title: 'Delete Product',
                message: 'Are you sure you want to delete ' + productName + '?',
                position: 'center',
                buttons: [
                    ['<button><b>Yes, Delete</b></button>', function (instance, toast) {
                        instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
                        
                        // Show loading message
                        iziToast.info({
                            message: 'Deleting product...',
                            timeout: 1000
                        });
                        
                        // Redirect with confirmation parameter
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
/* Product-specific styling */
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
.no-image-small {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 1.5rem;
}
.product-info {
    display: flex;
    flex-direction: column;
}
.product-info strong {
    color: var(--secondary-color);
    margin-bottom: 5px;
}
.product-info .description {
    color: var(--text-muted);
    font-size: 0.85rem;
    line-height: 1.4;
}
.product-price {
    font-weight: 600;
    color: var(--primary-color);
}
.product-stock {
    display: flex;
    flex-direction: column;
}
.product-stock.low-stock {
    color: #F44336;
}
.stock-warning {
    font-size: 0.7rem;
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 3px;
}
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 50px;
    font-weight: 600;
    gap: 5px;
    border: none;
    background: none;
    cursor: pointer;
    transition: transform 0.2s ease;
}
.status-badge:hover {
    transform: translateY(-2px);
}
.status-badge.status-active {
    background-color: rgba(76, 175, 76, 0.1);
    color: #4CAF50;
    border-left: 4px solid #4CAF50;
}
.status-badge.status-inactive {
    background-color: rgba(158, 158, 158, 0.1);
    color: #9E9E9E;
    border-left: 4px solid #9E9E9E;
}

/* Image upload styling */
.image-upload-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.current-image {
    width: 200px;
    height: 200px;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 10px;
    border: 1px solid var(--border-light);
}

.current-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    width: 200px;
    height: 200px;
    border-radius: 8px;
    background-color: var(--light-bg);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    margin-bottom: 10px;
    border: 1px dashed var(--border-light);
}

.no-image i {
    font-size: 3rem;
}

.form-control-file {
    position: absolute;
    opacity: 0;
    width: 0.1px;
    height: 0.1px;
}

@media (max-width: 768px) {
    .product-image {
        width: 40px;
        height: 40px;
    }
}

</style>

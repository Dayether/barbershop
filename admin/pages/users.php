<?php
require_once '../database.php';

// Only allow Super Admins
if (!isset($_SESSION['user']) || !(new Database())->isUserSuperAdmin($_SESSION['user']['user_id'])) {
    header('Location: ../admin_index.php');
    exit;
}

$db = new Database();
$viewMode = 'list';
$user = null;
$errorMsg = '';
$successMsg = '';
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 15;

// Edit mode
if (isset($_GET['edit'])) {
    $viewMode = 'edit';
    $id = (int)$_GET['edit'];
    $user = $db->conn->query("SELECT * FROM users WHERE user_id = $id")->fetch_assoc();
    if (!$user) {
        $errorMsg = "User not found.";
        $viewMode = 'list';
    }
}

// New user mode
if (isset($_GET['new']) && $_GET['new'] == 1) {
    $viewMode = 'new';
}

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $account_type = (int)$_POST['account_type'];
    $stmt = $db->conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, account_type=? WHERE user_id=?");
    $stmt->bind_param("sssii", $first_name, $last_name, $email, $account_type, $user_id);
    $success = $stmt->execute();
    $stmt->close();
    if ($success) {
        $successMsg = "User updated successfully!";
        $viewMode = 'list';
    } else {
        $errorMsg = "Failed to update user.";
    }
}

// Handle new user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $account_type = (int)$_POST['account_type'];
    // Password validation
    $passwordErrors = [];
    if (strlen($password) < 8) {
        $passwordErrors[] = 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $passwordErrors[] = 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $passwordErrors[] = 'Password must contain at least one number.';
    }
    if (!empty($passwordErrors)) {
        $errorMsg = implode(' ', $passwordErrors);
        $viewMode = 'new';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->conn->prepare("INSERT INTO users (first_name, last_name, email, password, account_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $hashed_password, $account_type);
        $success = $stmt->execute();
        $stmt->close();
        if ($success) {
            $successMsg = "User created successfully!";
            $viewMode = 'list';
        } else {
            $errorMsg = "Failed to create user. Email may already exist.";
            $viewMode = 'new';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['confirm_delete'])) {
    $id = (int)$_GET['delete'];
    $db->conn->query("DELETE FROM users WHERE user_id = $id");
    $successMsg = "User deleted successfully!";
}

// Pagination for list view
if ($viewMode === 'list') {
    $result = $db->conn->query("SELECT COUNT(*) as cnt FROM users");
    $row = $result->fetch_assoc();
    $totalUsers = $row['cnt'];
    $totalPages = ceil($totalUsers / $perPage);
    $offset = ($page - 1) * $perPage;
    $users = $db->conn->query("SELECT user_id, first_name, last_name, email, account_type FROM users LIMIT $perPage OFFSET $offset");
}

function getRoleLabel($type) {
    if ($type == 2) return 'Super Admin';
    if ($type == 1) return 'Admin';
    return 'User';
}
?>

<?php if ($viewMode === 'edit' && $user): ?>
<!-- EDIT MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Edit User</h2>
        <div class="actions">
            <a href="?page=users" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
    <div class="admin-card-body">
        <form method="post" class="admin-form">
            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="account_type">Role</label>
                <select id="account_type" name="account_type" class="form-control">
                    <option value="0" <?php if($user['account_type']==0) echo 'selected'; ?>>User</option>
                    <option value="1" <?php if($user['account_type']==1) echo 'selected'; ?>>Admin</option>
                    <?php
                    // Only show Super Admin option if there is no other super admin or this user is already super admin
                    $superAdminCount = $db->conn->query("SELECT COUNT(*) as cnt FROM users WHERE account_type = 2")->fetch_assoc()['cnt'];
                    if ($superAdminCount == 0 || $user['account_type'] == 2): ?>
                        <option value="2" <?php if($user['account_type']==2) echo 'selected'; ?>>Super Admin</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group mt-4">
                <button type="submit" name="update_user" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>
                <a href="?page=users" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($viewMode === 'new'): ?>
<!-- NEW USER MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Add New User</h2>
        <div class="actions">
            <a href="?page=users" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>
    <div class="admin-card-body">
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
        <?php elseif (!empty($successMsg)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <form method="post" class="admin-form">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="account_type">Role</label>
                <select id="account_type" name="account_type" class="form-control">
                    <option value="0">User</option>
                    <option value="1">Admin</option>
                    <?php
                    // Only show Super Admin option if there is no other super admin
                    $superAdminCount = $db->conn->query("SELECT COUNT(*) as cnt FROM users WHERE account_type = 2")->fetch_assoc()['cnt'];
                    if ($superAdminCount == 0): ?>
                        <option value="2">Super Admin</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group mt-4">
                <button type="submit" name="create_user" class="btn btn-primary"><i class="fas fa-plus"></i> Add User</button>
                <a href="?page=users" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- LIST MODE -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>User Management</h2>
        <div class="actions">
            <a href="?page=users&new=1" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Add New User</a>
        </div>
    </div>
    <div class="admin-card-body">
        <?php if ($errorMsg): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>
        <?php if ($successMsg): ?><div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $user['user_id'] ?></td>
                        <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><span class="role-badge role-<?= $user['account_type'] ?>"><?= getRoleLabel($user['account_type']) ?></span></td>
                        <td class="actions">
                            <div class="action-buttons">
                                <a href="?page=users&edit=<?= $user['user_id'] ?>" class="btn btn-primary btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="?page=users&delete=<?= $user['user_id'] ?>&confirm_delete=1" class="btn btn-danger btn-sm delete-btn" title="Delete" onclick="return confirm('Delete this user?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li><a href="?page=users&p=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                <?php endif; ?>
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                if ($startPage > 1) {
                    echo '<li><a href="?page=users&p=1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                }
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <a href="?page=users&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor;
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li><span class="pagination-ellipsis">...</span></li>';
                    }
                    echo '<li><a href="?page=users&p=' . $totalPages . '">' . $totalPages . '</a></li>';
                }
                ?>
                <?php if ($page < $totalPages): ?>
                    <li><a href="?page=users&p=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<style>
.role-badge { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 0.95em; font-weight: 500; }
.role-2 { background: var(--warning-color, #FFBA08); color: #b8860b; }
.role-1 { background: var(--success-color, #43CF7C); color: #fff; }
.role-0 { background: var(--primary-color, #4C84FF); color: #fff; }
.action-buttons { display: flex; gap: 6px; }
.btn { display: inline-block; padding: 7px 16px; border-radius: 8px; font-size: 1em; text-decoration: none; transition: all 0.2s; border: none; cursor: pointer; }
.btn-primary { background: var(--primary-color, #4C84FF); color: #fff; }
.btn-primary:hover { background: #2a5dcc; }
.btn-outline { background: #fff; color: var(--primary-color, #4C84FF); border: 1px solid var(--primary-color, #4C84FF); }
.btn-outline:hover { background: var(--primary-color, #4C84FF); color: #fff; }
.btn-accent { background: var(--secondary-color, #43CF7C); color: #fff; }
.btn-accent:hover { background: #2e9e5e; }
.btn-secondary { background: var(--info-color, #00CFE8); color: #fff; }
.btn-secondary:hover { background: #0097a7; }
.btn-danger { background: var(--danger-color, #FF6B6B); color: #fff; }
.btn-danger:hover { background: #d32f2f; }
.btn-sm { font-size: 0.95em; padding: 5px 10px; }
.alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; }
.alert-danger { background: #ffeaea; color: #c62828; }
.alert-success { background: #e3fcec; color: #388e3c; }
.pagination { display: flex; gap: 4px; list-style: none; padding: 0; margin: 18px 0 0 0; }
.pagination li { display: inline-block; }
.pagination li.active a { background: var(--primary-color, #4C84FF); color: #fff; border-radius: 6px; }
.pagination a { display: block; padding: 6px 12px; color: var(--primary-color, #4C84FF); text-decoration: none; border-radius: 6px; transition: background 0.2s; }
.pagination a:hover { background: var(--secondary-color, #43CF7C); color: #fff; }
.pagination-ellipsis { padding: 6px 10px; color: #aaa; }
@media (max-width: 700px) {
    .admin-card { padding: 16px 4vw; }
    .admin-card-header { flex-direction: column; gap: 12px; align-items: flex-start; }
    .admin-table th, .admin-table td { padding: 10px 6px; font-size: 0.98em; }
}
</style>

<?php
session_start();
require_once '../../database.php';

// Only allow Super Admins
$db = new Database();
if (!isset($_SESSION['user']) || !$db->isUserSuperAdmin($_SESSION['user']['user_id'])) {
    header('Location: users.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: users.php');
    exit;
}
$user_id = intval($_GET['id']);
$user = $db->conn->query("SELECT * FROM users WHERE user_id = $user_id")->fetch_assoc();
if (!$user) {
    header('Location: users.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $account_type = intval($_POST['account_type']);
    // Only allow one Super Admin (UI enforcement)
    if ($account_type == 2) {
        $result = $db->conn->query("SELECT COUNT(*) as cnt FROM users WHERE account_type = 2 AND user_id != $user_id");
        $row = $result->fetch_assoc();
        if ($row['cnt'] > 0) {
            $error = 'There can only be one Super Admin.';
        }
    }
    if (!$error) {
        $stmt = $db->conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, account_type = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $account_type, $user_id);
        $success = $stmt->execute();
        $stmt->close();
        if ($success) {
            header('Location: users.php?success=User updated successfully!');
            exit;
        } else {
            $error = 'Error updating user.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - Super Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <h1>Edit User</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <label>First Name: <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required></label><br>
        <label>Last Name: <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required></label><br>
        <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></label><br>
        <label>Role:
            <select name="account_type">
                <option value="0" <?= $user['account_type']==0?'selected':'' ?>>User</option>
                <option value="1" <?= $user['account_type']==1?'selected':'' ?>>Admin/Employee</option>
                <option value="2" <?= $user['account_type']==2?'selected':'' ?>>Super Admin</option>
            </select>
        </label><br>
        <button type="submit">Update User</button>
    </form>
    <a href="users.php">Back to User Management</a>
</body>
</html>

<?php
session_start();
require_once '../../database.php';

// Only allow Super Admins
$db = new Database();
if (!isset($_SESSION['user']) || !$db->isUserSuperAdmin($_SESSION['user']['user_id'])) {
    header('Location: users.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $account_type = intval($_POST['account_type']);
    // Only allow one Super Admin (UI enforcement)
    if ($account_type == 2) {
        $result = $db->conn->query("SELECT COUNT(*) as cnt FROM users WHERE account_type = 2");
        $row = $result->fetch_assoc();
        if ($row['cnt'] > 0) {
            $error = 'There can only be one Super Admin.';
        }
    }
    if (!$error) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->conn->prepare("INSERT INTO users (first_name, last_name, email, password, account_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $hashed_password, $account_type);
        $success = $stmt->execute();
        $stmt->close();
        if ($success) {
            header('Location: users.php?success=User created successfully!');
            exit;
        } else {
            $error = 'Error creating user.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create User - Super Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <h1>Create New User</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <label>First Name: <input type="text" name="first_name" required></label><br>
        <label>Last Name: <input type="text" name="last_name" required></label><br>
        <label>Email: <input type="email" name="email" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <label>Role:
            <select name="account_type">
                <option value="0">User</option>
                <option value="1">Admin/Employee</option>
                <option value="2">Super Admin</option>
            </select>
        </label><br>
        <button type="submit">Create User</button>
    </form>
    <a href="users.php">Back to User Management</a>
</body>
</html>

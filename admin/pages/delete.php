<?php
session_start();
require_once '../../database.php';

if (!isset($_SESSION['user']) || !(new Database())->isUserSuperAdmin($_SESSION['user']['user_id'])) {
    header('Location: users.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$user_id = intval($_GET['id']);
$db = new Database();

$user = $db->conn->query("SELECT account_type FROM users WHERE user_id = $user_id")->fetch_assoc();
if ($user && $user['account_type'] == 2) {
    $result = $db->conn->query("SELECT COUNT(*) as cnt FROM users WHERE account_type = 2");
    $row = $result->fetch_assoc();
    if ($row['cnt'] <= 1) {
        header('Location: users.php?error=Cannot delete the only Super Admin.');
        exit;
    }
}
$db->conn->query("DELETE FROM users WHERE user_id = $user_id");
header('Location: users.php?success=User deleted successfully!');
exit;
?>

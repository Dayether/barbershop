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
$db->conn->query("UPDATE users SET account_type = account_type + 1 WHERE user_id = $user_id AND account_type < 2");
header('Location: users.php');
exit;
?>

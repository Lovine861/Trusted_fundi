<?php
include "session.php";
include "db.php";

if (!is_admin_session()) {
    header("Location: login.php");
    exit();
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$status = isset($_POST['status']) ? strtolower(trim($_POST['status'])) : '';
$allowedStatus = ['pending', 'approved', 'rejected'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    header("Location: view_users.php");
    exit();
}

if ($id <= 0 || !in_array($status, $allowedStatus, true)) {
    header("Location: view_users.php");
    exit();
}

$stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: view_users.php");
exit();
?>

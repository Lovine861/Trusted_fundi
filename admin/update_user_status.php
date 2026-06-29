<?php
$baseDir = dirname(__DIR__);
require_once $baseDir . "/includes/session.php";
require_once $baseDir . "/includes/db.php";
require_once $baseDir . "/includes/notification_helper.php";

if (!is_admin_session()) {
    header("Location: ../login.php");
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
$next = basename((string) ($_GET['next'] ?? 'view_users.php'));
$allowedNext = ['view_users.php', 'view_fundi_requests.php'];
if (!in_array($next, $allowedNext, true)) {
    $next = 'view_users.php';
}
$allowedStatus = ['pending', 'approved', 'rejected'];
$updated = false;
$changed = false;
$documentsComplete = true;

if ($id <= 0 || !in_array($status, $allowedStatus, true)) {
    header("Location: " . $next);
    exit();
}

$roleStmt = $conn->prepare("SELECT role, status, fullname FROM users WHERE id = ? LIMIT 1");
$role = '';
$oldStatus = '';
$fundiName = 'Fundi';
if ($roleStmt) {
    $roleStmt->bind_param("i", $id);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    $user = $roleResult ? $roleResult->fetch_assoc() : null;
    $role = strtolower(trim((string) ($user['role'] ?? '')));
    $oldStatus = strtolower(trim((string) ($user['status'] ?? '')));
    $fundiName = trim((string) ($user['fullname'] ?? 'Fundi'));
    $roleStmt->close();
}

if ($role !== 'fundi') {
    header("Location: " . $next);
    exit();
}

if ($status === 'approved') {
    $docStmt = $conn->prepare("SELECT id_document, certificate_document, cv_document FROM fundis WHERE user_id = ? LIMIT 1");
    if ($docStmt) {
        $docStmt->bind_param("i", $id);
        $docStmt->execute();
        $docResult = $docStmt->get_result();
        $fundiDocs = $docResult ? $docResult->fetch_assoc() : null;
        $docStmt->close();

        $documentsComplete = !empty($fundiDocs['id_document'])
            && !empty($fundiDocs['certificate_document'])
            && !empty($fundiDocs['cv_document']);
    } else {
        $documentsComplete = false;
    }
}

if ($status === 'approved' && !$documentsComplete) {
    $redirectTarget = $next . (strpos($next, '?') === false ? '?' : '&') . 'message=missing_documents';
    header("Location: " . $redirectTarget);
    exit();
}

$stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("si", $status, $id);
    $updated = $stmt->execute();
    $changed = $stmt->affected_rows > 0;
    $stmt->close();
}

// Keep optional fundis directory table aligned with approved fundi users.
if ($status === 'approved') {
    $insertFundiStmt = $conn->prepare(
        "INSERT INTO fundis (user_id, service_category, location, verification_status)
         VALUES (?, 'General Service', 'Not set', 'verified')
         ON DUPLICATE KEY UPDATE verification_status = VALUES(verification_status)"
    );

    if ($insertFundiStmt) {
        $insertFundiStmt->bind_param("i", $id);
        $insertFundiStmt->execute();
        $insertFundiStmt->close();
    }
} else {
    $updateFundiStmt = $conn->prepare("UPDATE fundis SET verification_status = ? WHERE user_id = ?");
    if ($updateFundiStmt) {
        $updateFundiStmt->bind_param("si", $status, $id);
        $updateFundiStmt->execute();
        $updateFundiStmt->close();
    }
}

if (!empty($updated) && !empty($changed) && $oldStatus !== $status) {
    $decisionText = '';
    $emailSubject = 'Fundi Application Status Update';
    if ($status === 'approved') {
        $decisionText = 'accepted';
        $emailSubject = 'Account Approved';
    } elseif ($status === 'rejected') {
        $decisionText = 'rejected';
        $emailSubject = 'Account Application Status';
    } elseif ($status === 'pending') {
        $decisionText = 'set back to pending';
    }

    if ($decisionText !== '') {
        if ($status === 'approved') {
            $notifyMsg = "Congratulations {$fundiName}, your fundi account has been approved by admin.";
        } else {
            $notifyMsg = "Hello {$fundiName}, your fundi account request was {$decisionText} by admin.";
        }
        send_notification_with_email($conn, $id, $notifyMsg, $emailSubject);
    }
}

header("Location: " . $next);
exit();
?>

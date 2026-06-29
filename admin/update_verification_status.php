<?php
$baseDir = dirname(__DIR__);
require_once $baseDir . "/includes/session.php";
require_once $baseDir . "/includes/db.php";
require_once $baseDir . "/includes/notification_helper.php";

if (!is_admin_session()) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verification_requests.php?message=error');
    exit();
}

$userId = (int) ($_POST['user_id'] ?? 0);
$decision = strtolower(trim((string) ($_POST['decision'] ?? '')));
$adminComment = trim((string) ($_POST['admin_comment'] ?? ''));

if ($userId <= 0 || !in_array($decision, ['verified', 'rejected'], true)) {
    header('Location: verification_requests.php?message=error');
    exit();
}

if ($decision === 'rejected' && $adminComment === '') {
    header('Location: verification_requests.php?message=error');
    exit();
}

$commentValue = $decision === 'rejected' ? $adminComment : null;

$updateFundiSql = "UPDATE fundis
                   SET verification_status = ?,
                       admin_comment = ?,
                       verification_updated_at = CURRENT_TIMESTAMP
                   WHERE user_id = ?";
$updateFundiStmt = $conn->prepare($updateFundiSql);
if (!$updateFundiStmt) {
    header('Location: verification_requests.php?message=error');
    exit();
}

$updateFundiStmt->bind_param('ssi', $decision, $commentValue, $userId);
$fundiUpdated = $updateFundiStmt->execute();
$updateFundiStmt->close();

if (!$fundiUpdated) {
    header('Location: verification_requests.php?message=error');
    exit();
}

$userStatus = $decision === 'verified' ? 'approved' : 'rejected';
$updateUserStmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
if ($updateUserStmt) {
    $updateUserStmt->bind_param('si', $userStatus, $userId);
    $updateUserStmt->execute();
    $updateUserStmt->close();
}

$fullName = 'Fundi';
$nameStmt = $conn->prepare("SELECT fullname FROM users WHERE id = ? LIMIT 1");
if ($nameStmt) {
    $nameStmt->bind_param('i', $userId);
    $nameStmt->execute();
    $nameResult = $nameStmt->get_result();
    $nameRow = $nameResult ? $nameResult->fetch_assoc() : null;
    if ($nameRow && !empty($nameRow['fullname'])) {
        $fullName = trim((string) $nameRow['fullname']);
    }
    $nameStmt->close();
}

if ($decision === 'verified') {
    $notifySubject = 'Trusted Fundi - Verification Approved';
    $notifyMessage = "Dear {$fullName},\n\n";
    $notifyMessage .= "Your verification documents have been reviewed and approved by our admin team.\n\n";
    $notifyMessage .= "You can now continue using your Fundi account normally.\n\n";
    $notifyMessage .= "Thank you for working with Trusted Fundi.";
} else {
    $notifySubject = 'Trusted Fundi - Verification Rejected';
    $notifyMessage = "Dear {$fullName},\n\n";
    $notifyMessage .= "We have reviewed your verification documents, but your application was not approved at this time.\n";
    if ($adminComment !== '') {
        $notifyMessage .= "Reason: {$adminComment}\n";
    }
    $notifyMessage .= "\nPlease upload corrected documents and submit your application again.\n\n";
    $notifyMessage .= "If you need help, contact Trusted Fundi support.";
}

send_notification_with_email($conn, $userId, $notifyMessage, $notifySubject);

header('Location: verification_requests.php?message=updated');
exit();

<?php
$baseDir = dirname(__DIR__);
require_once $baseDir . "/includes/session.php";
require_once $baseDir . "/includes/db.php";
require_once $baseDir . "/includes/notification_helper.php";

if (!is_admin_session()) {
    header("Location: ../login.php");
    exit();
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $note = trim((string) ($_POST['note'] ?? 'This is a test notification email from Trusted Fundi.'));

    if ($userId <= 0 || $note === '') {
        $message = "Enter valid user id and message.";
    } else {
        $res = send_notification_with_email($conn, $userId, $note, 'Test Notification Email');
        $message = 'DB saved=' . ($res['db_saved'] ? 'yes' : 'no')
            . ', Email sent=' . ($res['email_sent'] ? 'yes' : 'no')
            . ', Target=' . ($res['email_target'] !== '' ? $res['email_target'] : 'n/a')
            . ($res['email_error'] !== '' ? ', Error=' . $res['email_error'] : '');
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Notification Email</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f1ea; padding: 24px; }
        .card { max-width: 680px; margin: 0 auto; background: #fff; padding: 18px; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 10px; margin: 8px 0 12px; border: 1px solid #ddd; border-radius: 8px; }
        button { padding: 10px 14px; border: 0; border-radius: 8px; background: #5c4b43; color: #fff; cursor: pointer; }
        .msg { margin: 10px 0; padding: 10px; border-radius: 8px; background: #eef7ff; color: #204060; }
        a { color: #5c4b43; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Test Notification + Email</h2>
        <?php if ($message !== ''): ?>
            <div class="msg"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST">
            <label>User ID</label>
            <input type="number" name="user_id" required>
            <label>Message</label>
            <textarea name="note" rows="4">This is a test notification email from Trusted Fundi.</textarea>
            <button type="submit">Send Test</button>
        </form>
        <p><a href="admin_dashboard.php">⬅️ Back to Dashboard</a></p>
    </div>
</body>
</html>

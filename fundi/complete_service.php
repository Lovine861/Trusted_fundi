<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/db.php";

if (strtolower(trim((string) ($_SESSION['role'] ?? ''))) !== 'fundi') {
    header("Location: ../login.php");
    exit();
}

$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ok = false;
$message = '';

if ($bookingId <= 0) {
    $message = 'Invalid booking id.';
} else {
    $fundiUserId = (int) $_SESSION['user_id'];
    $fundiProfileId = 0;

    $profileStmt = $conn->prepare("SELECT id FROM fundis WHERE user_id = ? LIMIT 1");
    if ($profileStmt) {
        $profileStmt->bind_param("i", $fundiUserId);
        $profileStmt->execute();
        $profileResult = $profileStmt->get_result();
        $profileRow = $profileResult ? $profileResult->fetch_assoc() : null;
        if ($profileRow) {
            $fundiProfileId = (int) $profileRow['id'];
        }
        $profileStmt->close();
    }

    if ($fundiProfileId <= 0) {
        $message = 'Fundi profile not found.';
    } else {
        $bookingCheckStmt = $conn->prepare("SELECT status FROM bookings WHERE id = ? AND fundi_id = ? LIMIT 1");
        if ($bookingCheckStmt) {
            $bookingCheckStmt->bind_param("ii", $bookingId, $fundiProfileId);
            $bookingCheckStmt->execute();
            $bookingCheckResult = $bookingCheckStmt->get_result();
            $bookingRow = $bookingCheckResult ? $bookingCheckResult->fetch_assoc() : null;
            $bookingCheckStmt->close();

            if (!$bookingRow) {
                $message = 'No matching booking found for your account.';
            } else {
                $currentStatus = strtolower((string) ($bookingRow['status'] ?? ''));

                if ($currentStatus === 'completed') {
                    $ok = true;
                    $message = 'This service is already marked as completed.';
                } else {
                    $stmt = $conn->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND fundi_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("ii", $bookingId, $fundiProfileId);
                        if ($stmt->execute()) {
                            $ok = true;
                            $message = 'Service marked as completed.';
                        } else {
                            $message = 'Update failed: ' . $conn->error;
                        }
                        $stmt->close();
                    } else {
                        $message = 'Could not prepare update request.';
                    }
                }
            }
        } else {
            $message = 'Could not validate booking ownership.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Service</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f4efe7; padding: 24px; }
        .card { max-width: 620px; margin: 0 auto; background: #fff; border: 1px solid #e4dfd7; border-radius: 12px; padding: 18px; box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        .msg { padding: 12px; border-radius: 8px; margin: 12px 0; }
        .msg.ok { background: #e8f7ee; color: #1f7a3f; }
        .msg.err { background: #fbe8e8; color: #8f2b2b; }
        a { color: #146c63; text-decoration: none; }
        .actions { margin-top: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Complete Service</h2>
        <div class="msg <?php echo $ok ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <div class="actions">
            <a href="view_requests.php">⬅️ Back to Requests</a>
        </div>
    </div>
</body>
</html>
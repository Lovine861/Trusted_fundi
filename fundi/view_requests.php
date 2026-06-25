<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/db.php";

if (strtolower(trim((string) ($_SESSION['role'] ?? ''))) !== 'fundi') {
    header("Location: ../login.php");
    exit();
}

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

$stmt = $conn->prepare(
    "SELECT bookings.id,
            bookings.client_id,
            bookings.booking_date,
            bookings.status,
            users.fullname AS client_name
     FROM bookings
     LEFT JOIN users ON users.id = bookings.client_id
     WHERE bookings.fundi_id = ?
     ORDER BY bookings.booking_date DESC"
);

$result = false;
if ($stmt) {
    $stmt->bind_param("i", $fundiProfileId);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Service Requests</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4efe7; margin: 0; padding: 20px; }
        .shell { max-width: 980px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e4dfd7; padding: 10px; text-align: left; }
        th { background: #1b8a7f; color: #fff; }
        a { color: #146c63; text-decoration: none; }
        .top { margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="top"><a href="fundi_dashboard.php">⬅️ Back to Dashboard</a></div>
        <h2>Service Requests</h2>

        <table>
            <tr>
                <th>Client</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) ($row['client_name'] ?: ('Client #' . $row['client_id']))); ?></td>
                        <td><?php echo htmlspecialchars($row['booking_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td>
                            <a href="update_booking.php?id=<?php echo (int) $row['id']; ?>&action=accept">✅ Accept</a> |
                            <a href="update_booking.php?id=<?php echo (int) $row['id']; ?>&action=reject">❌ Reject</a> |
                            <a href="complete_service.php?id=<?php echo (int) $row['id']; ?>">✔️ Complete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No requests found yet.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>

<?php
if ($stmt) {
    $stmt->close();
}
?>
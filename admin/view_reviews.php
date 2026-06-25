<?php
$baseDir = dirname(__DIR__);
require_once $baseDir . "/includes/db.php";
require_once $baseDir . "/includes/session.php";

if (!is_admin_session()) {
    header("Location: ../login.php");
    exit();
}

$sql = "SELECT reviews.id,
               reviews.rating,
               reviews.review,
               reviews.created_at,
               client.fullname AS client_name,
               fundi_user.fullname AS fundi_name
        FROM reviews
        LEFT JOIN users AS client ON client.id = reviews.client_id
        LEFT JOIN fundis ON fundis.id = reviews.fundi_id
        LEFT JOIN users AS fundi_user ON fundi_user.id = fundis.user_id
        ORDER BY reviews.created_at DESC";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Could not load reviews: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Reviews</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4efe7; margin: 0; padding: 20px; }
        .shell { max-width: 980px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e4dfd7; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #1b8a7f; color: #fff; }
        a { color: #146c63; text-decoration: none; }
        .top { margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="top"><a href="admin_dashboard.php">⬅️ Back to Dashboard</a></div>
        <h2>Client Reviews</h2>

        <table>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Fundi</th>
                <th>Rating</th>
                <th>Review</th>
                <th>Date</th>
            </tr>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo (int) $row['id']; ?></td>
                        <td><?php echo htmlspecialchars((string) ($row['client_name'] ?: 'Unknown')); ?></td>
                        <td><?php echo htmlspecialchars((string) ($row['fundi_name'] ?: 'Unknown')); ?></td>
                        <td><?php echo htmlspecialchars((string) ($row['rating'] ?? '')); ?></td>
                        <td><?php echo nl2br(htmlspecialchars((string) $row['review'])); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['created_at']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No reviews submitted yet.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>

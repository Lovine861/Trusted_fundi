<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/db.php";

$currentRole = strtolower(trim((string) ($_SESSION['role'] ?? '')));
if ($currentRole !== "fundi") {
    header("Location: ../login.php");
    exit();
}

$fundiUserId = (int) ($_SESSION['user_id'] ?? 0);

$notifications = null;
$stmt = $conn->prepare(
    "SELECT message, created_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY id DESC"
);

if ($stmt) {
    $stmt->bind_param("i", $fundiUserId);
    $stmt->execute();
    $notifications = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fundi Notifications</title>
    <style>
        body {
            background: #f7f1ea;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 820px;
            margin: 20px auto;
        }

        h2 {
            text-align: center;
            color: #5c4b43;
            margin-bottom: 20px;
        }

        .card {
            background: #fff;
            padding: 18px;
            margin-bottom: 12px;
            border-radius: 12px;
            box-shadow: 0 5px 14px rgba(0,0,0,0.08);
        }

        .message {
            color: #3d322d;
            font-size: 15px;
        }

        .date {
            color: #7a7a7a;
            font-size: 12px;
            margin-top: 8px;
        }

        .back {
            display: inline-block;
            margin-top: 10px;
            color: #146c63;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>My Notifications</h2>

        <?php if ($notifications && $notifications->num_rows > 0): ?>
            <?php while ($row = $notifications->fetch_assoc()): ?>
                <div class="card">
                    <div class="message"><?php echo htmlspecialchars((string) $row['message']); ?></div>
                    <div class="date"><?php echo htmlspecialchars((string) $row['created_at']); ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card">
                <div class="message">No notifications available yet.</div>
            </div>
        <?php endif; ?>

        <a class="back" href="fundi_dashboard.php">⬅️ Back to Dashboard</a>
    </div>
</body>
</html>

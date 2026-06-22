<?php
include "session.php";

if (is_admin_session()) {
    header("Location: admin_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>User Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars((string) ($_SESSION['email'] ?? 'User')); ?></p>
    <p>Your account is logged in successfully.</p>
    <a href="logout.php">Logout</a>
</body>
</html>

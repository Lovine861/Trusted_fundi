<?php
include "session.php";
include "db.php";

// allow only admin
if (!is_admin_session()) {
    header("Location: login.php");
    exit();
}

// counts (CHANGE 'users' if your table name is different)
$total = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$total_users = mysqli_fetch_assoc($total)['total'];

$approved = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status='approved'");
$approved_users = mysqli_fetch_assoc($approved)['total'];

$pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status='pending'");
$pending_users = mysqli_fetch_assoc($pending)['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h1>Admin Dashboard</h1>

    <p>Welcome, <?php echo $_SESSION['email']; ?></p>

    <hr>

    <h3>System Overview</h3>

<p>Total Users: <?php echo $total_users; ?></p>
<p>Approved Users: <?php echo $approved_users; ?></p>
<p>Pending Users: <?php echo $pending_users; ?></p>

<hr>

    <h3>Admin Controls</h3>

    <a href="view_users.php">View Users</a>
    <a href="add_user.php">Add User</a>
    <a href="logout.php" class="logout">Logout</a>

</div>

</body>
</html> 
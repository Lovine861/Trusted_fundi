<?php
include "session.php";
include "db.php";

// allow only admin
if ($_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}
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

    <h3>Admin Controls</h3>

    <a href="view_users.php">View Users</a>
    <a href="add_user.php">Add User</a>
    <a href="logout.php" class="logout">Logout</a>

</div>

</body>
</html> 
 <?php
require_once __DIR__ . "/../includes/session.php";

$currentRole = strtolower(trim((string) ($_SESSION['role'] ?? '')));
if ($currentRole !== 'client') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Client Dashboard</title>

<style>
body{
    margin:0;
    font-family:Arial;
    background:#f7f1ea;
}

/* SIDEBAR */
.sidebar{
    width:220px;
    height:100vh;
    background:#5c4b43;
    position:fixed;
    top:0;
    left:0;
    padding-top:20px;
}

.sidebar h2{
    color:white;
    text-align:center;
    margin-bottom:30px;
}

.sidebar a{
    display:block;
    color:white;
    padding:15px;
    text-decoration:none;
    transition:0.3s;
}

.sidebar a:hover{
    background:#e89cae;
}

/* MAIN CONTENT */
.main{
    margin-left:220px;
    padding:20px;
}

/* TOP BAR */
.topbar{
    background:white;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
    margin-bottom:20px;
}

/* CARD */
.card{
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
</style>

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <h2>CLIENT</h2>

    <a href="services.php">🔍 Search Fundi</a>

    <a href="my bookings.php">📋 My Bookings</a>

    <!-- PAYMENT ADDED HERE -->
    <a href="my bookings.php">💳 Price & Payment</a>

    <a href="reviews.php">⭐ Reviews</a>

    <a href="complaints.php">⚠ Complaints</a>

    <a href="notifications.php">🔔 Notifications</a>

    <a href="../change_password.php">🔐 Change Password</a>

    <a href="../logout.php">🚪 Logout</a>

</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <h2>Welcome to Your Dashboard</h2>
        <p>Manage your services, bookings, and fundis easily.</p>
    </div>

    <div class="card">
        <h3>Quick Access</h3>
        <p>Use the sidebar to navigate through your system.</p>
    </div>

</div>

</body>
</html>
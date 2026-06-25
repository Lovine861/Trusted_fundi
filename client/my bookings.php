 <?php
session_start();
include "../includes/db.php";
include "../includes/session.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['user_id'];

$sql = "SELECT bookings.*, fundis.service_category
        FROM bookings
        JOIN fundis ON bookings.fundi_id = fundis.id
        WHERE bookings.client_id = '$client_id'";

$result = mysqli_query($conn,$sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>My Bookings</title>

<style>
body{
    margin:0;
    font-family:Arial;
    background:#F7F1EA;
}

.nav{
    background:#E8A0BF;
    padding:12px;
    text-align:center;
}

.nav a{
    color:white;
    text-decoration:none;
    margin:15px;
    font-weight:bold;
}

.container{
    padding:30px;
    max-width:900px;
    margin:auto;
}

h1{
    text-align:center;
    color:#5C4B43;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

th{
    background:#E8A0BF;
    color:white;
    padding:12px;
}

td{
    padding:12px;
    text-align:center;
    border-bottom:1px solid #eee;
}
</style>

</head>

<body>

<div class="nav">
    <a href="client_dashboard.php">🏠 Dashboard</a>
    <a href="services.php">🔍 Services</a>
    <a href="my bookings.php">📋 My Bookings</a>
    <a href="reviews.php">⭐ Reviews</a>
    <a href="complaints.php">⚠️ Complaints</a>
    <a href="notifications.php">🔔 Notifications</a>
    <a href="../logout.php">🚪 Logout</a>
</div>

<div class="container">

<h1>My Bookings</h1>

<table>

<tr>
    <th>ID</th>
    <th>Service</th>
    <th>Date</th>
    <th>Status</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['service_category']; ?></td>
    <td><?php echo $row['booking_date']; ?></td>
    <td><?php echo $row['status']; ?></td>
</tr>

<?php } ?>

</table>

</div>

</body>
</html>
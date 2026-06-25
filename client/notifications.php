<?php
session_start();
include "../includes/db.php";
include "../includes/session.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM notifications
        WHERE user_id='$user_id'
        ORDER BY created_at DESC";

$result = mysqli_query($conn,$sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Notifications</title>

<style>
body{
    background:#F7F1EA;
    font-family:Arial,sans-serif;
}

.container{
    width:800px;
    margin:50px auto;
}

h2{
    text-align:center;
    color:#5C4B43;
    margin-bottom:20px;
}

.card{
    background:white;
    padding:20px;
    margin-bottom:15px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

.message{
    color:#5C4B43;
    font-size:16px;
}

.date{
    color:gray;
    font-size:13px;
    margin-top:10px;
}

a{
    color:#E8A0BF;
    text-decoration:none;
}
</style>

</head>
<body>

<div class="container">

<h2>My Notifications</h2>

<?php
if(mysqli_num_rows($result) > 0){

while($row = mysqli_fetch_assoc($result)){
?>

<div class="card">

<div class="message">
<?php echo $row['message']; ?>
</div>

<div class="date">
<?php echo $row['created_at']; ?>
</div>

</div>

<?php
}
}
else{
echo "<p>No notifications available.</p>";
}
?>

<a href="client_dashboard.php">
⬅️ Back to Dashboard
</a>

</div>

</body>
</html>
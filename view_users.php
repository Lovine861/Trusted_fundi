 <?php
include "session.php";
include "db.php";

if ($_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

$sql = "SELECT * FROM users";
$result = $conn->query($sql);
?>

<link rel="stylesheet" href="style.css">

<h1>All Users</h1>

<table border="1">
<tr>
    <th>ID</th>
    <th>Email</th>
    <th>Role</th>
    <th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()) { ?>
<tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['email']; ?></td>
    <td><?php echo $row['role']; ?></td>
    <td>
        <a href="delete_user.php?id=<?php echo $row['id']; ?>"
           onclick="return confirm('Delete this user?')">
           Delete
        </a>
    </td>
</tr>
<?php } ?>

</table>

<br>
<a href="admin_dashboard.php">Back</a>
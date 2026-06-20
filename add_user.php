<?php
include "session.php";
include "db.php";

if ($_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $sql = "INSERT INTO users (email, password, role)
            VALUES ('$email', '$password', '$role')";

    if ($conn->query($sql)) {
        echo "User added successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<link rel="stylesheet" href="style.css">

<h1>Add User</h1>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>

    <select name="role">
        <option value="admin">Admin</option>
        <option value="fundi">Fundi</option>
        <option value="client">Client</option>
    </select><br><br>

    <button type="submit">Add User</button>
</form>

<br>
<a href="admin_dashboard.php">Back</a> 
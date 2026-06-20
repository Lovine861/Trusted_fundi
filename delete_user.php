<?php
include "session.php";
include "db.php";

if ($_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];

$conn->query("DELETE FROM users WHERE id=$id");

header("Location: view_users.php");
exit();
?>
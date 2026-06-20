 <?php
$conn = new mysqli("localhost", "root", "", "trusted_fundi");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<?php
include "session.php";
include "db.php";

if (!is_admin_session()) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $message = "Invalid request token. Please try again.";
    } else {
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = strtolower(trim($_POST['role'] ?? 'client'));
        $status = strtolower(trim($_POST['status'] ?? 'pending'));

        $allowedRoles = ['admin', 'fundi', 'client'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'client';
        }

        $allowedStatus = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'pending';
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status) VALUES (?, ?, ?, ?, ?)");

        if ($stmt) {
            $stmt->bind_param("sssss", $fullname, $email, $passwordHash, $role, $status);
        }

        if ($stmt && $stmt->execute()) {
            $message = "User added successfully!";
        } else {
            if ($conn->errno === 1062) {
                $message = "That email already exists.";
            } else {
                $message = "Error: " . $conn->error;
            }
        }

        if ($stmt) {
            $stmt->close();
        }
    }
}
?>

<link rel="stylesheet" href="style.css">

<?php if ($message !== ""): ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<h1>Add User</h1>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <input type="text" name="fullname" placeholder="Full Name" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>

    <select name="role">
        <option value="admin">Admin</option>
        <option value="fundi">Fundi</option>
        <option value="client">Client</option>
    </select><br><br>

    <select name="status">
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
    </select><br><br>

    <button type="submit">Add User</button>
</form>

<br>
<a href="admin_dashboard.php">Back</a> 
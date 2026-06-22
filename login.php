 <?php
session_start();
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, fullname, email, password, role FROM users WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;

        $isValid = false;
        if ($user) {
            $storedPassword = (string) $user['password'];

            // Backward compatibility for legacy plain-text passwords.
            if (password_verify($password, $storedPassword)) {
                $isValid = true;
            } elseif ($password === $storedPassword) {
                $isValid = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upgradeStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($upgradeStmt) {
                    $userId = (int) $user['id'];
                    $upgradeStmt->bind_param("si", $newHash, $userId);
                    $upgradeStmt->execute();
                    $upgradeStmt->close();
                }
            }
        }

        if ($isValid) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['role'] = strtolower(trim((string) $user['role']));
            $_SESSION['email'] = (string) $user['email'];

            if ($_SESSION['role'] === "admin") {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }

            exit();
        }

        $stmt->close();
        $message = "Invalid email or password!";
    } else {
        $message = "Unable to process login right now.";
    }
}
?>

<link rel="stylesheet" href="style.css">

<?php if ($message !== ""): ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<h2>Login</h2>

<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
</form>
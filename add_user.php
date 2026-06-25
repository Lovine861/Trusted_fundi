<?php
$baseDir = __DIR__;
require_once $baseDir . "/includes/session.php";
require_once $baseDir . "/includes/db.php";
require_once $baseDir . "/includes/notification_helper.php";
require_once $baseDir . "/send_email.php";

if (!is_admin_session()) {
    header("Location: login.php");
    exit();
}

$message = "";
$messageType = "";
$emailDebug = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $message = "Invalid request token. Please try again.";
        $messageType = "error";
    } else {
        $fullname = trim($_POST['fullname']);
        $email = strtolower(trim($_POST['email']));
        $password = $_POST['password'];
        $role = strtolower(trim($_POST['role'] ?? 'client'));
        $status = strtolower(trim($_POST['status'] ?? 'pending'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $messageType = "error";
        } else {

        $allowedRoles = ['admin', 'fundi', 'client'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'client';
        }

        $allowedStatus = ['pending', 'approved', 'rejected'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'pending';
        }

        $existingStmt = $conn->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
        if (!$existingStmt) {
            $message = "Error adding user: " . $conn->error;
            $messageType = "error";
        } else {
             $existingStmt->bind_param("s", $email);
$existingStmt->execute();
$existingStmt->store_result();

if ($existingStmt->num_rows > 0) {
                $message = "That email already exists.";
                $messageType = "error";
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status) VALUES (?, ?, ?, ?, ?)");

                if (!$stmt) {
                    $message = "Error adding user: " . $conn->error;
                    $messageType = "error";
                } else {
                    $stmt->bind_param("sssss", $fullname, $email, $passwordHash, $role, $status);

                  if ($stmt->execute()) {
    $newUserId = (int) $stmt->insert_id;
    $message = "User added successfully!";
    $messageType = "success";
        $body = "<h2>Welcome to Trusted Fundi</h2><p>Hello,</p><p>Your account has been successfully created on the Trusted Fundi platform.</p><p>You can now log in using your registered email address and password.</p><p>Thank you for using Trusted Fundi.</p><hr><p><strong>Trusted Fundi Team</strong></p>";
    $mailSent = sendEmail(
                $email,
                "Welcome to Trusted Fundi",
                                $body
            );

            if ($mailSent) {
                $emailDebug = "Email trigger: SENT to " . $email;
            } else {
                $emailDebug = "Email trigger: FAILED.";
            }
                    } else {
                        if ((int) $stmt->errno === 1062) {
                            $message = "That email already exists.";
                            $messageType = "error";
                        } else {
                            $stmtError = trim((string) $stmt->error);
                            $message = "Error adding user: " . ($stmtError !== '' ? $stmtError : 'Unknown database error.');
                            $messageType = "error";
                        }
                    }

                    $stmt->close();
                }
            }

            $existingStmt->close();
        }
        }
    }
}
?>

<link rel="stylesheet" href="style.css">
<style>
    .notice {
        max-width: 700px;
        margin-bottom: 12px;
        border: 1px solid transparent;
    }

    .notice.success {
        background: #e8f7ee;
        color: #1f7a3f;
        border-color: #bfe5cc;
    }

    .notice.error {
        background: #fbe8e8;
        color: #8f2b2b;
        border-color: #e9b8b8;
    }

    .email-debug {
        max-width: 700px;
        background: #eef7ff;
        color: #1c4f76;
        border: 1px solid #c9dff1;
        margin-bottom: 12px;
        padding: 12px;
        border-radius: 8px;
    }
</style>

<?php if ($message !== ""): ?>
    <p class="notice <?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<?php if ($emailDebug !== ""): ?>
    <div class="email-debug"><?php echo htmlspecialchars($emailDebug); ?></div>
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
<a href="admin/admin_dashboard.php">⬅️ Back to Dashboard</a>
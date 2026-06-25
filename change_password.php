 <?php
session_start();
include "includes/db.php";

if (!isset($_SESSION['user_id'])) {
    die("You must login first.");
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (
        strlen($newPassword) < 8 ||
        !preg_match('/[A-Z]/', $newPassword) ||
        !preg_match('/[a-z]/', $newPassword) ||
        !preg_match('/[0-9]/', $newPassword)
    ) {
        $message = "Password must contain at least 8 characters, one uppercase letter, one lowercase letter and one number.";
        $messageType = "error";
    }

    elseif ($newPassword !== $confirmPassword) {
        $message = "New passwords do not match.";
        $messageType = "error";
    }

    else {

        $userId = $_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!password_verify($currentPassword, $user['password'])) {

            $message = "Current password is incorrect.";
            $messageType = "error";

        } else {

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $update = $conn->prepare(
                "UPDATE users SET password=? WHERE id=?"
            );

            $update->bind_param("si", $newHash, $userId);

            if ($update->execute()) {
                $message = "Password changed successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to update password.";
                $messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }

        body{
            background:#f7f1ea;
            display:flex;
            justify-content:center;
            align-items:center;
            min-height:100vh;
            padding:20px;
        }

        .container{
            width:100%;
            max-width:460px;
            background:#fff;
            padding:30px;
            border-radius:15px;
            box-shadow:0 5px 15px rgba(0,0,0,0.1);
        }

        h2{
            text-align:center;
            margin-bottom:20px;
            color:#5c4b43;
        }

        .msg{
            padding:10px;
            border-radius:8px;
            margin-bottom:15px;
            font-size:14px;
        }

        .msg.success{
            background:#e8f7ee;
            color:#1f7a3f;
        }

        .msg.error{
            background:#fbe8e8;
            color:#8f2b2b;
        }

        input[type="password"],
        input[type="text"]{
            width:100%;
            padding:12px;
            margin-bottom:12px;
            border:1px solid #ddd;
            border-radius:8px;
        }

        .show-pass{
            display:flex;
            align-items:center;
            gap:8px;
            margin:4px 0 12px;
            color:#5c4b43;
            font-size:14px;
        }

        .show-pass input[type="checkbox"]{
            width:auto;
            margin:0;
            padding:0;
            border:none;
            accent-color:#5c4b43;
        }

        button{
            width:100%;
            padding:12px;
            background:#e89cae;
            color:#fff;
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-size:16px;
            margin-top:4px;
        }

        button:hover{
            opacity:0.9;
        }

        .links{
            margin-top:14px;
            text-align:center;
        }

        .links a{
            color:#e89cae;
            text-decoration:none;
            font-weight:bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Change Password</h2>

    <?php if ($message !== ""): ?>
        <div class="msg <?php echo htmlspecialchars($messageType ?: 'error'); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input
            id="current_password"
            type="password"
            name="current_password"
            placeholder="Current Password"
            required
        >

        <input
            id="new_password"
            type="password"
            name="new_password"
            placeholder="New Password"
            required
        >

        <input
            id="confirm_password"
            type="password"
            name="confirm_password"
            placeholder="Confirm New Password"
            required
        >

        <label class="show-pass">
            <input type="checkbox" onclick="togglePasswordFields()">
            Show Password
        </label>

        <button type="submit">Change Password</button>
    </form>

    <div class="links">
        <a href="javascript:history.back()">Back to Dashboard</a>
    </div>
</div>

<script>
    function togglePasswordFields() {
        var fields = [
            document.getElementById("current_password"),
            document.getElementById("new_password"),
            document.getElementById("confirm_password")
        ];

        for (var i = 0; i < fields.length; i++) {
            if (fields[i]) {
                fields[i].type = fields[i].type === "password" ? "text" : "password";
            }
        }
    }
</script>

</body>
</html>

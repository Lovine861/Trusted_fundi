<?php
include "includes/db.php";

$message = "";
$messageType = "";

if (!isset($_GET['token'])) {
    die("Invalid reset link.");
}

$token = $_GET['token'];

$stmt = $conn->prepare(
    "SELECT * FROM reset_tokens
     WHERE token = ?
     LIMIT 1"
);

$stmt->bind_param("s", $token);
$stmt->execute();

$result = $stmt->get_result();

if (!$reset = $result->fetch_assoc()) {
    die("Reset link is invalid or has expired.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } elseif (
        strlen($newPassword) < 8 ||
        !preg_match('/[A-Z]/', $newPassword) ||
        !preg_match('/[a-z]/', $newPassword) ||
        !preg_match('/[0-9]/', $newPassword)
    ) {

        $message =
        "Password must contain at least 8 characters, one uppercase letter, one lowercase letter and one number.";
        $messageType = "error";

    } else {

        $hash = password_hash(
            $newPassword,
            PASSWORD_DEFAULT
        );

        $update = $conn->prepare(
            "UPDATE users
             SET password=?
             WHERE id=?"
        );

        $update->bind_param(
            "si",
            $hash,
            $reset['user_id']
        );

        if ($update->execute()) {

            $delete = $conn->prepare(
                "DELETE FROM reset_tokens
                 WHERE id=?"
            );

            $delete->bind_param(
                "i",
                $reset['id']
            );

            $delete->execute();

            $message =
            "Password reset successful. You can now login.";
            $messageType = "success";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, sans-serif;
        }

        body{
            margin:0;
            background:#f7f1ea;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
        }

        .shell{
            width:100%;
            max-width:980px;
            display:grid;
            grid-template-columns:300px 1fr;
            border-radius:14px;
            overflow:hidden;
            box-shadow:0 8px 22px rgba(0,0,0,0.12);
            background:white;
        }

        .side{
            background:#5c4b43;
            color:white;
            padding:26px 22px;
        }

        .side h2{
            margin:0 0 12px;
            color:white;
        }

        .side p{
            margin:0;
            line-height:1.5;
            color:#f2e9e2;
            font-size:14px;
        }

        .panel{
            padding:28px;
        }

        h1{
            margin:0 0 8px;
            color:#3e3027;
        }

        .subtitle{
            margin:0 0 18px;
            color:#7a6d63;
            font-size:14px;
        }

        .msg{
            padding:10px 12px;
            border-radius:8px;
            margin-bottom:14px;
            font-size:14px;
        }

        .msg.success{
            background:#e8f7ee;
            color:#1f7a3f;
        }

        .msg.error{
            background:#fce8e8;
            color:#8f2b2b;
        }

        input[type="password"],
        input[type="text"]{
            width:100%;
            padding:12px;
            border:1px solid #d8cec3;
            border-radius:8px;
            font-size:14px;
            margin-bottom:12px;
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

        button.submit-btn{
            margin-top:4px;
            width:100%;
            border:none;
            border-radius:8px;
            padding:12px;
            background:#5c4b43;
            color:white;
            cursor:pointer;
            font-size:15px;
        }

        button.submit-btn:hover{
            background:#e89cae;
        }

        .foot{
            margin-top:14px;
            font-size:14px;
            color:#6b5a50;
        }

        .foot a{
            color:#e089a7;
            text-decoration:none;
            font-weight:bold;
        }

        @media (max-width:760px){
            .shell{ grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="side">
            <h2>Trusted Fundi</h2>
            <p>Set a new secure password to regain access to your account.</p>
        </div>

        <div class="panel">
            <h1>Reset Password</h1>
            <p class="subtitle">Enter and confirm your new password.</p>

            <?php if ($message !== ""): ?>
                <div class="msg <?php echo htmlspecialchars($messageType ?: 'error'); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input
                    id="new_password"
                    type="password"
                    name="password"
                    placeholder="New Password"
                    required
                >

                <input
                    id="confirm_password"
                    type="password"
                    name="confirm_password"
                    placeholder="Confirm Password"
                    required
                >

                <label class="show-pass">
                    <input type="checkbox" onclick="toggleResetPasswords()">
                    Show Password
                </label>

                <button class="submit-btn" type="submit">Reset Password</button>
            </form>

            <p class="foot">
                Back to <a href="login.php">Login</a>
            </p>
        </div>
    </div>

    <script>
        function toggleResetPasswords() {
            var fields = [
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

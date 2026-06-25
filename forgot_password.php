<?php
date_default_timezone_set('Africa/Nairobi');
include "includes/db.php";
include "send_email.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);

    $stmt = $conn->prepare(
        "SELECT id, fullname FROM users WHERE email=? LIMIT 1"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {

        $token = bin2hex(random_bytes(32));

        $expiry = date("Y-m-d H:i:s", time() + 3600);

        $insert = $conn->prepare(
            "INSERT INTO reset_tokens
            (user_id, token, expires_at)
            VALUES (?, ?, ?)"
        );

        $insert->bind_param(
            "iss",
            $user['id'],
            $token,
            $expiry
        );

        $insert->execute();

        $resetLink =
            "http://localhost:8081/trusted_fundi/reset_password.php?token="
            . $token;

        $subject = "Password Reset Request";

        $body =
            "Hello " . $user['fullname'] .
            "<br><br>" .
            "Click the link below to reset your password:" .
            "<br><br>" .
            "<a href='$resetLink'>$resetLink</a>" .
            "<br><br>" .
            "This link expires in 1 hour.";

        sendEmail($email, $subject, $body);

        $message =
            "Password reset email sent. Check your inbox.";
    }
    else {

        $message = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        body{
            margin:0;
            font-family:Arial, sans-serif;
            background:#f7f1ea;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
        }

        .container{
            width:100%;
            max-width:460px;
            background:white;
            border-radius:14px;
            box-shadow:0 8px 22px rgba(0,0,0,0.12);
            padding:28px;
        }

        h2{
            margin:0 0 14px;
            color:#3e3027;
            text-align:center;
        }

        p{
            margin:0 0 12px;
            font-size:14px;
            color:#6b5a50;
        }

        input[type="email"]{
            width:100%;
            padding:12px;
            border:1px solid #d8cec3;
            border-radius:8px;
            font-size:14px;
            margin-bottom:12px;
        }

        button{
            width:100%;
            border:none;
            border-radius:8px;
            padding:12px;
            background:#5c4b43;
            color:white;
            cursor:pointer;
            font-size:15px;
        }

        button:hover{
            background:#e89cae;
        }

        .back{
            margin-top:12px;
            text-align:center;
        }

        .back a{
            color:#e089a7;
            text-decoration:none;
            font-weight:bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>

        <p><?php echo htmlspecialchars($message); ?></p>

        <form method="POST">
            <input
                type="email"
                name="email"
                placeholder="Enter your email"
                required
            >

            <button type="submit">
                Send Reset Link
            </button>
        </form>

        <p class="back"><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>

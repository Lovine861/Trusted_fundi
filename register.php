<?php
require_once __DIR__ . "/includes/db.php";

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    if (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ) {
        die("Password must contain:<br>- At least 8 characters<br>- One uppercase letter<br>- One lowercase letter<br>- One number");
    }
    $role = strtolower(trim($_POST['role'] ?? 'client'));
    $serviceCategory = trim($_POST['service_category'] ?? 'General Service');
    $location = trim($_POST['location'] ?? 'Not set');

    $allowedRoles = ['client', 'fundi'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'client';
    }

    if ($fullname === '' || $email === '' || $password === '') {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $accountStatus = ($role === 'client') ? 'approved' : 'pending';

        $userStmt = $conn->prepare(
            "INSERT INTO users (fullname, email, phone, password, role, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        if ($userStmt) {
            $userStmt->bind_param("ssssss", $fullname, $email, $phone, $passwordHash, $role, $accountStatus);

            if ($userStmt->execute()) {
                $newUserId = (int) $userStmt->insert_id;
                $userStmt->close();

                if ($role === 'fundi') {
                    $fundiStmt = $conn->prepare(
                        "INSERT INTO fundis (user_id, service_category, location, verification_status)
                         VALUES (?, ?, ?, 'pending')
                         ON DUPLICATE KEY UPDATE
                            service_category = VALUES(service_category),
                            location = VALUES(location),
                            verification_status = 'pending'"
                    );

                    if ($fundiStmt) {
                        $fundiStmt->bind_param("iss", $newUserId, $serviceCategory, $location);
                        $fundiStmt->execute();
                        $fundiStmt->close();
                    }
                }

                if ($role === 'client') {
                    $message = 'Registration successful. You can now log in.';
                } else {
                    $message = 'Registration submitted. Wait for admin approval before login.';
                }
                $messageType = 'success';
            } else {
                if ($conn->errno === 1062) {
                    $message = 'That email is already registered.';
                } else {
                    $message = 'Registration failed: ' . $conn->error;
                }
                $messageType = 'error';
                $userStmt->close();
            }
        } else {
            $message = 'Could not prepare registration request.';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Registration</title>

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
            text-align:left;
        }

        .side p{
            margin:0;
            line-height:1.5;
            color:#f2e9e2;
            font-size:14px;
            text-align:left;
        }

        .panel{
            padding:28px;
        }

        .container{
            width:100%;
            max-width:620px;
        }

        h2{
            text-align:left;
            margin-bottom:8px;
            color:#3e3027;
        }

        .subtitle{
            margin:0 0 18px;
            color:#7a6d63;
            font-size:14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select{
            width:100%;
            padding:12px;
            margin-bottom:15px;
            border:1px solid #d8cec3;
            border-radius:8px;
            font-size:14px;
            color:#3e3027;
        }

        button{
            width:100%;
            padding:12px;
            background:#5c4b43;
            color:white;
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-size:15px;
        }

        button:hover{
            background:#e89cae;
        }

        p{
            text-align:left;
            margin-top:15px;
        }

        .msg{
            padding:10px;
            border-radius:8px;
            margin-bottom:14px;
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

        a{
            color:#e089a7;
            text-decoration:none;
            font-weight:bold;
        }

        .password-wrap{
            position:relative;
        }

        .password-wrap input{
            padding-right:44px;
            margin-bottom:8px;
        }

        .toggle-pass{
            position:absolute;
            right:10px;
            top:50%;
            transform:translateY(-50%);
            border:none;
            background:transparent;
            width:auto;
            padding:0;
            margin:0;
            color:#7a6d63;
            font-size:18px;
            cursor:pointer;
            line-height:1;
        }

        .toggle-pass:hover{
            background:transparent;
            color:#5c4b43;
        }

        .hint{
            margin:0 0 14px;
            font-size:13px;
            color:#7a6d63;
        }

        .foot{
            margin-top:14px;
            font-size:14px;
            color:#6b5a50;
            text-align:left;
        }

        @media (max-width:760px){
            .shell{
                grid-template-columns:1fr;
            }
        }
    </style>

</head>
<body>

<div class="shell">
    <div class="side">
        <h2>Trusted Fundi</h2>
        <p>Create your account to find or offer trusted services quickly and safely.</p>
    </div>

    <div class="panel">
        <div class="container">
            <h2>Create Account</h2>
            <p class="subtitle">Fill in your details to get started.</p>

            <?php if ($message !== ""): ?>
                <div class="msg <?php echo htmlspecialchars($messageType); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="fullname" placeholder="Full Name" required>

                <input type="email" name="email" placeholder="Email Address" required>

                <input type="text" name="phone" placeholder="Phone Number" required>

                <div class="password-wrap">
                    <input id="password" type="password" name="password" placeholder="Minimum 8 chars, uppercase, lowercase and number" required>
                    <button class="toggle-pass" type="button" onclick="togglePassword()" aria-label="Show or hide password">&#128065;</button>
                </div>
                <p class="hint">Use at least 8 characters, uppercase, lowercase, and a number.</p>

                <select name="role" id="role" required>
                    <option value="client">Client</option>
                    <option value="fundi">Fundi</option>
                </select>

                <input type="text" name="service_category" placeholder="Fundi Service Category (only for fundi)">

                <input type="text" name="location" placeholder="Location (only for fundi)">

                <button type="submit" name="register">Register</button>
            </form>

            <p class="foot">
                Already have an account?
                <a href="login.php">Login</a>
            </p>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        var passwordField = document.getElementById("password");
        if (passwordField.type === "password") {
            passwordField.type = "text";
        } else {
            passwordField.type = "password";
        }
    }
</script>

</body>
</html>
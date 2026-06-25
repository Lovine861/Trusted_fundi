<?php
require_once __DIR__ . "/includes/db.php";

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
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
            background:#f7f1ea;
            display:flex;
            justify-content:center;
            align-items:center;
            min-height:100vh;
        }

        .container{
            width:400px;
            background:white;
            padding:30px;
            border-radius:15px;
            box-shadow:0 5px 15px rgba(0,0,0,0.1);
        }

        h2{
            text-align:center;
            margin-bottom:20px;
            color:#5c4b43;
        }

        input{
            width:100%;
            padding:12px;
            margin-bottom:15px;
            border:1px solid #ddd;
            border-radius:8px;
        }

        button{
            width:100%;
            padding:12px;
            background:#e89cae;
            color:white;
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-size:16px;
        }

        button:hover{
            opacity:0.9;
        }

        p{
            text-align:center;
            margin-top:15px;
        }

        select{
            width:100%;
            padding:12px;
            margin-bottom:15px;
            border:1px solid #ddd;
            border-radius:8px;
        }

        .msg{
            padding:10px;
            border-radius:8px;
            margin-bottom:12px;
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
            color:#e89cae;
            text-decoration:none;
            font-weight:bold;
        }
    </style>

</head>
<body>

<div class="container">

    <h2>Create Account</h2>

    <?php if ($message !== ""): ?>
        <div class="msg <?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input type="text" name="fullname" placeholder="Full Name" required>

        <input type="email" name="email" placeholder="Email Address" required>

        <input type="text" name="phone" placeholder="Phone Number" required>

        <input type="password" name="password" placeholder="Password" required>

        <select name="role" id="role" required>
            <option value="client">Client</option>
            <option value="fundi">Fundi</option>
        </select>

        <input type="text" name="service_category" placeholder="Fundi Service Category (only for fundi)">

        <input type="text" name="location" placeholder="Location (only for fundi)">

        <button type="submit" name="register">Register</button>

    </form>

    <p>
        Already have an account?
        <a href="login.php">Login</a>
    </p>

</div>

</body>
</html>
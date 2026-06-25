 <?php
session_start();
include "includes/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, fullname, email, password, role, status FROM users WHERE email = ? LIMIT 1");

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $role = strtolower(trim((string) $user['role']));
            $status = strtolower(trim((string) $user['status']));

            // Only fundi accounts require admin approval before login.
            if ($role === "fundi" && $status !== "approved") {
                if ($status === "rejected") {
                    $message = "Your fundi account was rejected by admin.";
                } else {
                    $message = "Your fundi account is pending admin approval.";
                }
            } else {

                if (password_verify($password, $user['password']) || $password === $user['password']) {

                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $role;

                    // Redirect based on role
                    if ($_SESSION['role'] == "admin") {
                        header("Location: admin/admin_dashboard.php");
                    } elseif ($_SESSION['role'] == "client") {
                        header("Location: client/client_dashboard.php");
                    } elseif ($_SESSION['role'] == "fundi") {
                        header("Location: fundi/fundi_dashboard.php");
                    } else {
                        header("Location: index.php");
                    }

                    exit();
                } else {
                    $message = "Invalid email or password!";
                }
            }

        } else {
            $message = "User not found!";
        }

        $stmt->close();
    } else {
        $message = "Database error!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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

        .shell{
            width:100%;
            max-width:980px;
            display:grid;
            grid-template-columns: 300px 1fr;
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
            background:#fce8e8;
            color:#8f2b2b;
            font-size:14px;
        }

        label{
            display:block;
            margin:10px 0 6px;
            color:#5c4b43;
            font-weight:bold;
            font-size:14px;
        }

        input{
            width:100%;
            padding:12px;
            border:1px solid #d8cec3;
            border-radius:8px;
            font-size:14px;
        }

        button{
            margin-top:14px;
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
            <p>Login to access your dashboard and manage your services with a consistent, clean experience.</p>
        </div>

        <div class="panel">
            <h1>Login</h1>
            <p class="subtitle">Enter your account details to continue.</p>

            <?php if ($message != ""): ?>
                <div class="msg"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" placeholder="you@example.com" required>

                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="Enter password" required>

                <button type="submit">Login</button>
            </form>

            <p class="foot">
                New user? <a href="register.php">Create account</a>
            </p>
        </div>
    </div>
</body>
</html>
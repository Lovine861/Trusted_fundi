<?php
$baseDir = dirname(__DIR__);
require_once $baseDir . "/includes/db.php";
require_once $baseDir . "/includes/session.php";

// allow only admin
if (!is_admin_session()) {
    header("Location: ../login.php");
    exit();
}

// Show approval metrics for fundis only.
$total = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='fundi'");
$total_fundis = mysqli_fetch_assoc($total)['total'];

$approved = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='fundi' AND status='approved'");
$approved_fundis = mysqli_fetch_assoc($approved)['total'];

$pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='fundi' AND status='pending'");
$pending_fundis = mysqli_fetch_assoc($pending)['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body{
            margin:0;
            font-family:Arial, sans-serif;
            background:#f7f1ea;
        }

        .sidebar{
            width:220px;
            height:100vh;
            background:#5c4b43;
            position:fixed;
            top:0;
            left:0;
            padding-top:20px;
        }

        .sidebar h2{
            color:white;
            text-align:center;
            margin-bottom:30px;
        }

        .sidebar a{
            display:block;
            color:white;
            padding:15px;
            text-decoration:none;
            transition:0.3s;
        }

        .sidebar a:hover{
            background:#e89cae;
        }

        .main{
            margin-left:220px;
            padding:20px;
        }

        .topbar{
            background:white;
            padding:16px;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,0.1);
            margin-bottom:20px;
        }

        .stats{
            display:grid;
            grid-template-columns:repeat(3, minmax(0, 1fr));
            gap:14px;
            margin-bottom:20px;
        }

        .card{
            background:white;
            padding:18px;
            border-radius:12px;
            box-shadow:0 4px 10px rgba(0,0,0,0.1);
        }

        .label{
            color:#7a6d63;
            font-size:13px;
            margin-bottom:8px;
        }

        .value{
            font-size:30px;
            color:#3e3027;
            font-weight:700;
        }

        .quick h3{
            margin-top:0;
        }

        .quick-links{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:10px;
        }

        .quick-links a{
            display:inline-block;
            text-decoration:none;
            color:#fff;
            background:#5c4b43;
            padding:10px 14px;
            border-radius:8px;
        }

        .quick-links a:hover{
            background:#e89cae;
        }

        @media (max-width: 900px){
            .stats{ grid-template-columns:1fr; }
        }

        @media (max-width: 700px){
            .sidebar{
                position:static;
                width:100%;
                height:auto;
            }

            .main{
                margin-left:0;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">
    <h2>ADMIN</h2>
    <a href="view_users.php">👥 Users</a>
    <a href="view_fundi_requests.php">🛠️ Fundi Requests</a>
    <a href="view_complaints.php">⚠️ Complaints</a>
    <a href="view_reviews.php">⭐ Reviews</a>
    <a href="../add_user.php">➕ Add User</a>
    <a href="../change_password.php">🔐 Change Password</a>
    <a href="../logout.php">🚪 Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <h2>Welcome to Admin Dashboard</h2>
        <p>Signed in as <?php echo htmlspecialchars((string) $_SESSION['email']); ?>.</p>
    </div>

    <div class="stats">
        <div class="card">
            <div class="label">Total Fundis</div>
            <div class="value"><?php echo (int) $total_fundis; ?></div>
        </div>
        <div class="card">
            <div class="label">Approved Fundis</div>
            <div class="value"><?php echo (int) $approved_fundis; ?></div>
        </div>
        <div class="card">
            <div class="label">Pending Fundi Approvals</div>
            <div class="value"><?php echo (int) $pending_fundis; ?></div>
        </div>
    </div>

    <div class="card quick">
        <h3>Quick Access</h3>
        <p>Use these shortcuts to manage the platform.</p>
        <div class="quick-links">
            <a href="view_users.php">👥 View Users</a>
            <a href="view_fundi_requests.php">🛠️ Review Fundi Requests</a>
            <a href="view_complaints.php">⚠️ Open Complaints</a>
            <a href="view_reviews.php">⭐ View Reviews</a>
        </div>
    </div>
</div>

</body>
</html> 
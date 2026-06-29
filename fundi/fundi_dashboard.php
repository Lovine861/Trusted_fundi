<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/db.php";

$currentRole = strtolower(trim((string) ($_SESSION['role'] ?? '')));
if ($currentRole !== "fundi") {
    header("Location: ../login.php");
    exit();
}

$fundiUserId = (int) $_SESSION['user_id'];
$fundiProfileId = 0;

$profileStmt = $conn->prepare("SELECT id FROM fundis WHERE user_id = ? LIMIT 1");
if ($profileStmt) {
    $profileStmt->bind_param("i", $fundiUserId);
    $profileStmt->execute();
    $profileResult = $profileStmt->get_result();
    $profileRow = $profileResult ? $profileResult->fetch_assoc() : null;
    if ($profileRow) {
        $fundiProfileId = (int) $profileRow['id'];
    }
    $profileStmt->close();
}

$statsSql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_total,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted_total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_total
             FROM bookings
             WHERE fundi_id = ?";

$stats = [
    'total' => 0,
    'pending_total' => 0,
    'accepted_total' => 0,
    'completed_total' => 0,
];

$statsStmt = $conn->prepare($statsSql);
if ($statsStmt) {
    $statsStmt->bind_param("i", $fundiProfileId);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $row = $statsResult ? $statsResult->fetch_assoc() : null;
    if ($row) {
        $stats = $row;
    }
    $statsStmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fundi Dashboard</title>
    <style>
        *{ box-sizing: border-box; }

        body{
            margin: 0;
            font-family: Arial, sans-serif;
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
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
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
            font-weight: 700;
        }

        .quick h3{
            margin-top:0;
        }

        .quick-links{
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top:10px;
        }

        .quick-links a{
            display:inline-block;
            text-decoration: none;
            color:#fff;
            background:#5c4b43;
            padding:10px 14px;
            border-radius:8px;
        }

        .quick-links a:hover{
            background:#e89cae;
        }

        @media (max-width: 980px) {
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
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

            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>FUNDI</h2>
        <a href="view_requests.php">📋 Service Requests</a>
        <a href="notifications.php">🔔 Notifications</a>
        <a href="ratings.php">⭐ Ratings</a>
        <a href="upload_documents.php">🗂️ Verification</a>
        <a href="../change_password.php">🔐 Change Password</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <div class="main">
        <div class="topbar">
            <h2>Welcome to Fundi Dashboard</h2>
            <p>Hello <?php echo htmlspecialchars((string) ($_SESSION['fullname'] ?? $_SESSION['email'] ?? 'Fundi')); ?>, track jobs and respond quickly.</p>
        </div>

        <div class="stats">
            <div class="card">
                <div class="label">Total Requests</div>
                <div class="value"><?php echo (int) $stats['total']; ?></div>
            </div>
            <div class="card">
                <div class="label">Pending</div>
                <div class="value"><?php echo (int) $stats['pending_total']; ?></div>
            </div>
            <div class="card">
                <div class="label">Accepted</div>
                <div class="value"><?php echo (int) $stats['accepted_total']; ?></div>
            </div>
            <div class="card">
                <div class="label">Completed</div>
                <div class="value"><?php echo (int) $stats['completed_total']; ?></div>
            </div>
        </div>

        <div class="card quick">
            <h3>Quick Access</h3>
            <p>Use shortcuts to manage your work.</p>
            <div class="quick-links">
                <a href="view_requests.php">📋 View Service Requests</a>
                <a href="notifications.php">🔔 Notifications</a>
                <a href="ratings.php">⭐ View Ratings</a>
                <a href="upload_documents.php">🗂️ Verification</a>
            </div>
        </div>
    </div>
</body>
</html>
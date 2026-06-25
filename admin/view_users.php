 <?php
$baseDir = dirname(__DIR__);
require_once $baseDir . "/includes/session.php";
require_once $baseDir . "/includes/db.php";

// allow only admin
if (!is_admin_session()) {
    header("Location: ../login.php");
    exit();
}

$sql = "SELECT * FROM users";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Users</title>
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

        .card{
            background:white;
            padding:18px;
            border-radius:12px;
            box-shadow:0 4px 10px rgba(0,0,0,0.1);
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border-bottom: 1px solid #eee;
            padding: 12px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background-color: #5c4b43;
            color: white;
        }

        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .pending { color: #8c5a00; background:#fff4dd; border:1px solid #f1d79a; }
        .approved { color: #1f7a3f; background:#e8f7ee; border:1px solid #bfe5cc; }
        .rejected { color: #8f2b2b; background:#fbe8e8; border:1px solid #e9b8b8; }

        .action-link{
            text-decoration:none;
            font-weight:bold;
        }

        .action-link.approve{ color:#1f7a3f; }
        .action-link.reject{ color:#8f2b2b; }

        .back-wrap{
            margin-top:12px;
        }

        .back-wrap a{
            color:#5c4b43;
            text-decoration:none;
            font-weight:bold;
        }

        @media (max-width: 900px){
            .main{ padding:14px; }
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
    <a href="../logout.php">🚪 Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <h2>View Users</h2>
        <p>Manage users and approve/reject fundi accounts.</p>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Action (Fundi Only)</th>
            </tr>

            <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php while($row = mysqli_fetch_assoc($result)) { ?>
                    <?php $statusClass = strtolower((string) $row['status']); ?>
                    <tr>
                        <td><?php echo (int) $row['id']; ?></td>
                        <td><?php echo htmlspecialchars((string) $row['fullname']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['email']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['role']); ?></td>
                        <td>
                            <span class="status <?php echo htmlspecialchars($statusClass); ?>">
                                <?php echo htmlspecialchars((string) $row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (strtolower((string) $row['role']) === 'fundi') { ?>
                                <a class="action-link approve" href="update_user_status.php?id=<?php echo (int) $row['id']; ?>&status=approved">✅ Approve</a>
                                |
                                <a class="action-link reject" href="update_user_status.php?id=<?php echo (int) $row['id']; ?>&status=rejected">❌ Reject</a>
                            <?php } else { ?>
                                -
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="6">No users found</td>
                </tr>
            <?php } ?>
        </table>

        <div class="back-wrap">
            <a href="admin_dashboard.php">⬅️ Back to Dashboard</a>
        </div>
    </div>
</div>

</body>
</html>
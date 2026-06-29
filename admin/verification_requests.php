<?php
$baseDir = dirname(__DIR__);
require_once $baseDir . "/includes/session.php";
require_once $baseDir . "/includes/db.php";

if (!is_admin_session()) {
    header("Location: ../login.php");
    exit();
}

$sql = "SELECT
            users.id AS user_id,
            users.fullname,
            users.email,
            COALESCE(fundis.service_category, 'General Service') AS service_category,
            COALESCE(fundis.location, 'Not set') AS location,
            COALESCE(fundis.verification_status, 'pending') AS verification_status,
            COALESCE(fundis.id_document, '') AS id_document,
            COALESCE(fundis.certificate_document, '') AS certificate_document,
            COALESCE(fundis.cv_document, '') AS cv_document,
            COALESCE(fundis.admin_comment, '') AS admin_comment
        FROM users
        INNER JOIN fundis ON fundis.user_id = users.id
        WHERE LOWER(users.role) = 'fundi'
          AND LOWER(COALESCE(fundis.verification_status, 'pending')) = 'pending'
        ORDER BY users.id DESC";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die('Failed to fetch verification requests: ' . mysqli_error($conn));
}

$message = '';
if (isset($_GET['message'])) {
    if ($_GET['message'] === 'updated') {
        $message = 'Verification status updated successfully.';
    } elseif ($_GET['message'] === 'error') {
        $message = 'Could not update verification status.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verification Requests</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f7f1ea; }
        .sidebar { width: 220px; height: 100vh; background: #5c4b43; position: fixed; top: 0; left: 0; padding-top: 20px; }
        .sidebar h2 { color: white; text-align: center; margin-bottom: 30px; }
        .sidebar a { display: block; color: white; padding: 15px; text-decoration: none; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #e89cae; }
        .main { margin-left: 220px; padding: 20px; }
        .card { background: white; padding: 18px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #eee; padding: 12px; text-align: left; vertical-align: top; }
        th { background: #5c4b43; color: #fff; }
        .msg { margin-bottom: 12px; border-radius: 8px; padding: 10px 12px; background: #e8f7ee; color: #1f7a3f; }
        .link { color: #146c63; text-decoration: none; font-weight: bold; }
        .btn { border: none; border-radius: 8px; padding: 8px 10px; cursor: pointer; color: #fff; }
        .approve { background: #1f7a3f; }
        .reject { background: #8f2b2b; }
        textarea { width: 100%; min-height: 64px; border: 1px solid #d8cec3; border-radius: 8px; padding: 8px; resize: vertical; }
        .empty { text-align: center; color: #7a6d63; }
        @media (max-width: 700px) {
            .sidebar { position: static; width: 100%; height: auto; }
            .main { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h2>ADMIN</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="view_users.php">Users</a>
    <a href="view_fundi_requests.php">Fundi Requests</a>
    <a class="active" href="verification_requests.php">Verification</a>
    <a href="view_complaints.php">Complaints</a>
    <a href="view_reviews.php">Reviews</a>
    <a href="../change_password.php">Change Password</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="main">
    <div class="card">
        <h2>Pending Verification Requests</h2>
        <p>Review uploaded fundi documents and approve or reject verification.</p>

        <?php if ($message !== ''): ?>
            <div class="msg"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table>
            <tr>
                <th>Fundi</th>
                <th>Service</th>
                <th>Location</th>
                <th>Documents</th>
                <th>Action</th>
            </tr>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars((string) $row['fullname']); ?></strong><br>
                            <?php echo htmlspecialchars((string) $row['email']); ?>
                        </td>
                        <td><?php echo htmlspecialchars((string) $row['service_category']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['location']); ?></td>
                        <td>
                            <div>
                                ID: <?php if (!empty($row['id_document'])): ?><a class="link" target="_blank" rel="noopener" href="../<?php echo htmlspecialchars((string) $row['id_document']); ?>">View</a><?php else: ?>Missing<?php endif; ?>
                            </div>
                            <div>
                                Certificate: <?php if (!empty($row['certificate_document'])): ?><a class="link" target="_blank" rel="noopener" href="../<?php echo htmlspecialchars((string) $row['certificate_document']); ?>">View</a><?php else: ?>Missing<?php endif; ?>
                            </div>
                            <div>
                                Police/CV: <?php if (!empty($row['cv_document'])): ?><a class="link" target="_blank" rel="noopener" href="../<?php echo htmlspecialchars((string) $row['cv_document']); ?>">View</a><?php else: ?>Missing<?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <form method="POST" action="update_verification_status.php" style="display:grid; gap:8px; min-width:220px;">
                                <input type="hidden" name="user_id" value="<?php echo (int) $row['user_id']; ?>">
                                <button class="btn approve" type="submit" name="decision" value="verified">Approve</button>
                                <textarea name="admin_comment" placeholder="Reason for rejection (required only if rejecting)"></textarea>
                                <button class="btn reject" type="submit" name="decision" value="rejected">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td class="empty" colspan="5">No pending verification requests found.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>

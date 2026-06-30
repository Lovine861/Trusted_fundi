<?php
$baseDir = dirname(__DIR__);
require_once $baseDir . "/includes/session.php";
require_once $baseDir . "/includes/db.php";

if (!is_admin_session()) {
    header("Location: ../login.php");
    exit();
}

$sql = "SELECT users.id,
                             users.fullname,
                             users.email,
                             users.phone,
                             users.status,
                             COALESCE(fundis.service_category, 'General Service') AS service_category,
                             COALESCE(fundis.location, 'Not set') AS location,
                             COALESCE(fundis.id_document, '') AS id_document,
                             COALESCE(fundis.certificate_document, '') AS certificate_document,
                             COALESCE(fundis.cv_document, '') AS cv_document,
                             COALESCE(fundis.face_verification_status, 'pending') AS face_verification_status,
                             COALESCE(fundis.verification_status, 'pending') AS verification_status
                FROM users
                LEFT JOIN fundis ON fundis.user_id = users.id
                WHERE LOWER(users.role) = 'fundi'
                    AND LOWER(COALESCE(fundis.verification_status, 'pending')) = 'pending'
                ORDER BY users.id DESC";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Could not load fundi requests: " . mysqli_error($conn));
}

$message = '';
$messageType = 'error';
if (isset($_GET['message'])) {
    if ($_GET['message'] === 'missing_documents') {
        $message = 'This fundi cannot be approved until ID, certificate, and CV are uploaded.';
    } elseif ($_GET['message'] === 'updated') {
        $messageType = 'success';
        $message = 'Verification status updated successfully.';
    } elseif ($_GET['message'] === 'reason_required') {
        $message = 'Please provide a reason before rejecting a verification request.';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fundi Requests</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4efe7; margin: 0; padding: 20px; }
        .shell { max-width: 1100px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e4dfd7; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #1b8a7f; color: #fff; }
        .top { margin-bottom: 12px; }
        a { color: #146c63; text-decoration: none; }
        .approve { color: #1f7a3f; font-weight: bold; }
        .reject { color: #8f2b2b; font-weight: bold; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="top"><a href="admin_dashboard.php">⬅️ Back to Dashboard</a></div>
        <h2>Pending Fundi Requests</h2>

        <?php if ($message !== ''): ?>
            <div style="padding:10px 12px; margin-bottom:12px; border-radius:8px; background:#fbe8e8; color:#8f2b2b;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Service</th>
                <th>Location</th>
                <th>Documents</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo (int) $row['id']; ?></td>
                        <td><?php echo htmlspecialchars((string) $row['fullname']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['email']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['phone']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['service_category']); ?></td>
                        <td><?php echo htmlspecialchars((string) $row['location']); ?></td>
                        <td>
                            <div>
                                ID: <?php if (!empty($row['id_document'])): ?><a href="../<?php echo htmlspecialchars((string) $row['id_document']); ?>" target="_blank" rel="noopener">View</a><?php else: ?>Missing<?php endif; ?>
                            </div>
                            <div>
                                Certificate: <?php if (!empty($row['certificate_document'])): ?><a href="../<?php echo htmlspecialchars((string) $row['certificate_document']); ?>" target="_blank" rel="noopener">View</a><?php else: ?>Missing<?php endif; ?>
                            </div>
                            <div>
                                Police/CV: <?php if (!empty($row['cv_document'])): ?><a href="../<?php echo htmlspecialchars((string) $row['cv_document']); ?>" target="_blank" rel="noopener">View</a><?php else: ?>Missing<?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars((string) $row['verification_status']); ?></td>
                        <td>
                            <form method="POST" action="update_verification_status.php" style="display:grid; gap:8px; min-width:220px;">
                                <input type="hidden" name="user_id" value="<?php echo (int) $row['id']; ?>">
                                <button class="approve" type="submit" name="decision" value="verified" style="border:none; background:transparent; cursor:pointer; text-align:left; padding:0;">✅ Accept</button>
                                <textarea name="admin_comment" placeholder="Reason for rejection (required only if rejecting)"></textarea>
                                <button class="reject" type="submit" name="decision" value="rejected" style="border:none; background:transparent; cursor:pointer; text-align:left; padding:0;">❌ Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">No pending fundi requests found.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
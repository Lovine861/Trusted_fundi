<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/db.php";

$currentRole = strtolower(trim((string) ($_SESSION['role'] ?? '')));
if ($currentRole !== 'fundi') {
    header('Location: ../login.php');
    exit();
}

$fundiUserId = (int) ($_SESSION['user_id'] ?? 0);
$uploadStatus = '';
$uploadStatusClass = '';

if (isset($_GET['status'])) {
    $statusParam = strtolower(trim((string) $_GET['status']));
    if ($statusParam === 'success') {
        $uploadStatus = 'Documents uploaded successfully. They are now pending admin review.';
        $uploadStatusClass = 'success';
    } elseif ($statusParam === 'error') {
        $uploadStatus = 'Upload failed. Please check file type/size and try again.';
        $uploadStatusClass = 'error';
    }
}

$sql = "SELECT id_document, certificate_document, cv_document, verification_status, admin_comment
        FROM fundis
        WHERE user_id = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$profile = [
    'id_document' => '',
    'certificate_document' => '',
    'cv_document' => '',
    'verification_status' => 'pending',
    'admin_comment' => null,
];

if ($stmt) {
    $stmt->bind_param('i', $fundiUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    if ($row) {
        $profile = array_merge($profile, $row);
    }
    $stmt->close();
}

$statusLabel = ucfirst(strtolower((string) ($profile['verification_status'] ?? 'pending')));
$statusClass = strtolower((string) ($profile['verification_status'] ?? 'pending'));

function render_doc_link($path, $label)
{
    $safePath = trim((string) $path);
    if ($safePath === '') {
        return '<span class="missing">Not uploaded</span>';
    }

    return '<a href="../' . htmlspecialchars($safePath) . '" target="_blank" rel="noopener">View ' . htmlspecialchars($label) . '</a>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fundi Verification Upload</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f7f1ea; }
        .layout { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }
        .sidebar { background: #5c4b43; color: #fff; padding-top: 20px; }
        .sidebar h2 { text-align: center; margin: 0 0 28px; }
        .sidebar a { display: block; color: #fff; text-decoration: none; padding: 14px 16px; }
        .sidebar a:hover, .sidebar a.active { background: #e89cae; }
        .main { padding: 20px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 18px; margin-bottom: 16px; }
        h2 { margin-top: 0; color: #3e3027; }
        .status-pill { display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: bold; text-transform: capitalize; }
        .pending { background: #fff4dd; border: 1px solid #f1d79a; color: #8c5a00; }
        .verified { background: #e8f7ee; border: 1px solid #bfe5cc; color: #1f7a3f; }
        .approved { background: #e8f7ee; border: 1px solid #bfe5cc; color: #1f7a3f; }
        .rejected { background: #fbe8e8; border: 1px solid #e9b8b8; color: #8f2b2b; }
        .msg { border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; font-size: 14px; }
        .msg.success { background: #e8f7ee; color: #1f7a3f; }
        .msg.error { background: #fbe8e8; color: #8f2b2b; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .field label { display: block; font-weight: bold; color: #5c4b43; margin-bottom: 6px; }
        .field input[type=file] { width: 100%; }
        .actions { margin-top: 14px; }
        button { background: #5c4b43; color: #fff; border: none; border-radius: 8px; padding: 10px 14px; cursor: pointer; }
        button:hover { background: #e89cae; }
        .docs-list { display: grid; gap: 8px; font-size: 14px; }
        .docs-list strong { color: #3e3027; }
        .missing { color: #8f2b2b; }
        .note { margin-top: 8px; font-size: 13px; color: #7a6d63; }
        @media (max-width: 860px) {
            .layout { grid-template-columns: 1fr; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="layout">
    <div class="sidebar">
        <h2>FUNDI</h2>
        <a href="fundi_dashboard.php">Dashboard</a>
        <a href="view_requests.php">Service Requests</a>
        <a href="notifications.php">Notifications</a>
        <a href="ratings.php">Ratings</a>
        <a class="active" href="upload_documents.php">Verification</a>
        <a href="../change_password.php">Change Password</a>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="main">
        <div class="card">
            <h2>Verification Documents</h2>
            <p>Upload your ID, certificate, and police/CV document for admin verification.</p>
            <p>
                Current status:
                <span class="status-pill <?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
            </p>
            <?php if (!empty($profile['admin_comment'])): ?>
                <p><strong>Admin comment:</strong> <?php echo htmlspecialchars((string) $profile['admin_comment']); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($uploadStatus !== ''): ?>
            <div class="msg <?php echo htmlspecialchars($uploadStatusClass); ?>"><?php echo htmlspecialchars($uploadStatus); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Upload New Documents</h2>
            <form method="POST" action="save_documents.php" enctype="multipart/form-data">
                <div class="grid">
                    <div class="field">
                        <label for="id_document">National ID (PDF/JPG/PNG)</label>
                        <input id="id_document" type="file" name="id_document" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="field">
                        <label for="certificate_document">Certificate (PDF/JPG/PNG)</label>
                        <input id="certificate_document" type="file" name="certificate_document" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                </div>
                <div class="field" style="margin-top: 12px;">
                    <label for="cv_document">Police Clearance or CV (PDF/JPG/PNG/DOC/DOCX)</label>
                    <input id="cv_document" type="file" name="cv_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                </div>

                <div class="actions">
                    <button type="submit">Upload Documents</button>
                </div>
                <p class="note">Each file must be 5MB or less. A new upload replaces old documents.</p>
            </form>
        </div>

        <div class="card">
            <h2>Current Uploaded Files</h2>
            <div class="docs-list">
                <div><strong>ID Document:</strong> <?php echo render_doc_link($profile['id_document'] ?? '', 'ID'); ?></div>
                <div><strong>Certificate:</strong> <?php echo render_doc_link($profile['certificate_document'] ?? '', 'Certificate'); ?></div>
                <div><strong>Police/CV:</strong> <?php echo render_doc_link($profile['cv_document'] ?? '', 'Police/CV'); ?></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

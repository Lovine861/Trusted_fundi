<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/db.php";

$currentRole = strtolower(trim((string) ($_SESSION['role'] ?? '')));
if ($currentRole !== 'fundi') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: upload_documents.php?status=error');
    exit();
}

$fundiUserId = (int) ($_SESSION['user_id'] ?? 0);
if ($fundiUserId <= 0) {
    header('Location: upload_documents.php?status=error');
    exit();
}

$baseUploadDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
$targetDirs = [
    'id_document' => ['folder' => 'ids', 'db' => 'id_document'],
    'certificate_document' => ['folder' => 'certificates', 'db' => 'certificate_document'],
    'cv_document' => ['folder' => 'police', 'db' => 'cv_document'],
];

$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
$maxSizeBytes = 5 * 1024 * 1024;
$savedPaths = [];

foreach ($targetDirs as $fieldName => $meta) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        header('Location: upload_documents.php?status=error');
        exit();
    }

    $file = $_FILES[$fieldName];
    $originalName = (string) ($file['name'] ?? '');
    $tmpName = (string) ($file['tmp_name'] ?? '');
    $fileSize = (int) ($file['size'] ?? 0);

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true) || $fileSize <= 0 || $fileSize > $maxSizeBytes) {
        header('Location: upload_documents.php?status=error');
        exit();
    }

    $targetFolder = $baseUploadDir . $meta['folder'] . DIRECTORY_SEPARATOR;
    if (!is_dir($targetFolder) && !mkdir($targetFolder, 0755, true)) {
        header('Location: upload_documents.php?status=error');
        exit();
    }

    $safeBaseName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($originalName));
    $newFileName = 'fundi_' . $fundiUserId . '_' . $fieldName . '_' . time() . '_' . $safeBaseName;
    $absolutePath = $targetFolder . $newFileName;

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        header('Location: upload_documents.php?status=error');
        exit();
    }

    $relativePath = 'uploads/' . $meta['folder'] . '/' . $newFileName;
    $savedPaths[$meta['db']] = $relativePath;
}

$updateSql = "UPDATE fundis
              SET id_document = ?,
                  certificate_document = ?,
                  cv_document = ?,
                  verification_status = 'pending',
                  admin_comment = NULL,
                  verification_updated_at = CURRENT_TIMESTAMP
              WHERE user_id = ?";

$updateStmt = $conn->prepare($updateSql);
if (!$updateStmt) {
    header('Location: upload_documents.php?status=error');
    exit();
}

$idPath = $savedPaths['id_document'] ?? null;
$certPath = $savedPaths['certificate_document'] ?? null;
$cvPath = $savedPaths['cv_document'] ?? null;
$updateStmt->bind_param('sssi', $idPath, $certPath, $cvPath, $fundiUserId);
$ok = $updateStmt->execute();
$updateStmt->close();

if (!$ok) {
    header('Location: upload_documents.php?status=error');
    exit();
}

$userPendingStmt = $conn->prepare("UPDATE users SET status = 'pending' WHERE id = ?");
if ($userPendingStmt) {
    $userPendingStmt->bind_param('i', $fundiUserId);
    $userPendingStmt->execute();
    $userPendingStmt->close();
}

header('Location: upload_documents.php?status=success');
exit();

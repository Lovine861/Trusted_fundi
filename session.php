<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Recover role/email from DB if a legacy session is missing those fields.
if (!isset($_SESSION['role']) || trim((string) $_SESSION['role']) === '') {
    include_once "db.php";

    if (isset($conn) && $conn instanceof mysqli) {
        $userId = (int) $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT role, email FROM users WHERE id = ? LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;

            if ($user) {
                $_SESSION['role'] = strtolower(trim((string) $user['role']));
                $_SESSION['email'] = $user['email'];
            }

            $stmt->close();
        }
    }
}

if (!function_exists('is_admin_session')) {
    function is_admin_session(): bool
    {
        return strtolower(trim((string) ($_SESSION['role'] ?? ''))) === 'admin';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token): bool
    {
        if (!isset($_SESSION['csrf_token']) || $token === null) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>
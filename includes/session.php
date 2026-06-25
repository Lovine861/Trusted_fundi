 <?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Recover missing session data if needed
if (!isset($_SESSION['role']) || empty($_SESSION['role'])) {

    include_once __DIR__ . "/db.php";

    if (isset($conn)) {
        $userId = $_SESSION['user_id'];

        $stmt = $conn->prepare("SELECT role, email, fullname FROM users WHERE id = ? LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                $_SESSION['role'] = strtolower($user['role']);
                $_SESSION['email'] = $user['email'];
                $_SESSION['fullname'] = $user['fullname'];
            }

            $stmt->close();
        }
    }
}

// Helper: check admin
function is_admin_session() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// CSRF token (optional but good)
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
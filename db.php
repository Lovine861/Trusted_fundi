 <?php
$conn = new mysqli("localhost", "root", "", "trusted_fundi");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$createUsersTable = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL DEFAULT '',
    email VARCHAR(191) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'client',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (!$conn->query($createUsersTable)) {
    die("Table setup failed: " . $conn->error);
}

// Keep schema aligned with admin pages.
$checkFullname = $conn->query("SHOW COLUMNS FROM users LIKE 'fullname'");
if ($checkFullname && $checkFullname->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN fullname VARCHAR(150) NOT NULL DEFAULT '' AFTER id");
}

$checkStatus = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
if ($checkStatus && $checkStatus->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER role");
}

$checkCreatedAt = $conn->query("SHOW COLUMNS FROM users LIKE 'created_at'");
if ($checkCreatedAt && $checkCreatedAt->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
}

$checkEmailIndex = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'uniq_users_email'");
if ($checkEmailIndex && $checkEmailIndex->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD UNIQUE KEY uniq_users_email (email)");
}
?>
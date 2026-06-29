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
    phone VARCHAR(30) DEFAULT NULL,
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

$checkPhone = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
if ($checkPhone && $checkPhone->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(30) DEFAULT NULL AFTER email");
}

$createFundisTable = "
CREATE TABLE IF NOT EXISTS fundis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_category VARCHAR(100) DEFAULT 'General Service',
    location VARCHAR(120) DEFAULT 'Not set',
    verification_status VARCHAR(20) DEFAULT 'pending',
    id_document VARCHAR(255) DEFAULT NULL,
    certificate_document VARCHAR(255) DEFAULT NULL,
    cv_document VARCHAR(255) DEFAULT NULL,
    admin_comment TEXT DEFAULT NULL,
    verification_updated_at TIMESTAMP NULL DEFAULT NULL,
    face_verification_status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_fundi_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (!$conn->query($createFundisTable)) {
    die("Fundis table setup failed: " . $conn->error);
}

// Remove duplicate fundi profile rows (keep the lowest id per user_id).
$conn->query(
    "DELETE f1 FROM fundis f1
     INNER JOIN fundis f2
       ON f1.user_id = f2.user_id
      AND f1.id > f2.id"
);

$checkFundiUnique = $conn->query("SHOW INDEX FROM fundis WHERE Key_name = 'uniq_fundi_user'");
if ($checkFundiUnique && $checkFundiUnique->num_rows === 0) {
    $conn->query("ALTER TABLE fundis ADD UNIQUE KEY uniq_fundi_user (user_id)");
}

$checkFundiIdDoc = $conn->query("SHOW COLUMNS FROM fundis LIKE 'id_document'");
if ($checkFundiIdDoc && $checkFundiIdDoc->num_rows === 0) {
    $conn->query("ALTER TABLE fundis ADD COLUMN id_document VARCHAR(255) DEFAULT NULL AFTER verification_status");
}

$checkFundiCert = $conn->query("SHOW COLUMNS FROM fundis LIKE 'certificate_document'");
if ($checkFundiCert && $checkFundiCert->num_rows === 0) {
    $conn->query("ALTER TABLE fundis ADD COLUMN certificate_document VARCHAR(255) DEFAULT NULL AFTER id_document");
}

$checkFundiCv = $conn->query("SHOW COLUMNS FROM fundis LIKE 'cv_document'");
if ($checkFundiCv && $checkFundiCv->num_rows === 0) {
    $conn->query("ALTER TABLE fundis ADD COLUMN cv_document VARCHAR(255) DEFAULT NULL AFTER certificate_document");
}

$checkFundiAdminComment = $conn->query("SHOW COLUMNS FROM fundis LIKE 'admin_comment'");
if ($checkFundiAdminComment && $checkFundiAdminComment->num_rows === 0) {
    $conn->query("ALTER TABLE fundis ADD COLUMN admin_comment TEXT DEFAULT NULL AFTER cv_document");
}

$checkVerificationUpdatedAt = $conn->query("SHOW COLUMNS FROM fundis LIKE 'verification_updated_at'");
if ($checkVerificationUpdatedAt && $checkVerificationUpdatedAt->num_rows === 0) {
    $conn->query("ALTER TABLE fundis ADD COLUMN verification_updated_at TIMESTAMP NULL DEFAULT NULL AFTER admin_comment");
}

$checkFaceVerification = $conn->query("SHOW COLUMNS FROM fundis LIKE 'face_verification_status'");
if ($checkFaceVerification && $checkFaceVerification->num_rows === 0) {
    $conn->query("ALTER TABLE fundis ADD COLUMN face_verification_status VARCHAR(20) DEFAULT 'pending' AFTER cv_document");
}

$syncApprovedFundisSql = "
INSERT INTO fundis (user_id, service_category, location, verification_status)
SELECT users.id, 'General Service', 'Not set', 'verified'
FROM users
WHERE LOWER(users.role) LIKE '%fundi%'
    AND LOWER(users.status) LIKE '%approved%'
ON DUPLICATE KEY UPDATE verification_status = 'verified'
";

$conn->query($syncApprovedFundisSql);

$createBookingsTable = "
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    fundi_id INT NOT NULL,
    booking_date DATE NOT NULL,
    service_name VARCHAR(150) NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_booking_client (client_id),
    INDEX idx_booking_fundi (fundi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (!$conn->query($createBookingsTable)) {
    die("Bookings table setup failed: " . $conn->error);
}

$checkBookingServiceName = $conn->query("SHOW COLUMNS FROM bookings LIKE 'service_name'");
if ($checkBookingServiceName && $checkBookingServiceName->num_rows === 0) {
    $conn->query("ALTER TABLE bookings ADD COLUMN service_name VARCHAR(150) NULL AFTER booking_date");
}

$checkBookingAmount = $conn->query("SHOW COLUMNS FROM bookings LIKE 'amount'");
if ($checkBookingAmount && $checkBookingAmount->num_rows === 0) {
    $conn->query("ALTER TABLE bookings ADD COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER service_name");
}

$createPaymentsTable = "
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    fundi_id INT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    phone VARCHAR(15) NOT NULL,
    transaction_status VARCHAR(20) NOT NULL DEFAULT 'pending',
    checkout_request_id VARCHAR(100) NULL,
    mpesa_receipt VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (!$conn->query($createPaymentsTable)) {
    die("Payments table setup failed: " . $conn->error);
}

$checkPaymentFundiId = $conn->query("SHOW COLUMNS FROM payments LIKE 'fundi_id'");
if ($checkPaymentFundiId && $checkPaymentFundiId->num_rows === 0) {
    $conn->query("ALTER TABLE payments ADD COLUMN fundi_id INT NULL AFTER booking_id");
}

$createReviewsTable = "
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    fundi_id INT NOT NULL,
    review TEXT NOT NULL,
    rating TINYINT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_review_fundi (fundi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (!$conn->query($createReviewsTable)) {
    die("Reviews table setup failed: " . $conn->error);
}

$createNotificationsTable = "
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notification_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (!$conn->query($createNotificationsTable)) {
    die("Notifications table setup failed: " . $conn->error);
}

$createComplaintsTable = "
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    booking_id INT NOT NULL,
    complaint TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_complaint_client (client_id),
    INDEX idx_complaint_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (!$conn->query($createComplaintsTable)) {
    die("Complaints table setup failed: " . $conn->error);
}

$checkReviewRating = $conn->query("SHOW COLUMNS FROM reviews LIKE 'rating'");
if ($checkReviewRating && $checkReviewRating->num_rows === 0) {
    $conn->query("ALTER TABLE reviews ADD COLUMN rating TINYINT NULL AFTER review");
}

$checkReviewClientId = $conn->query("SHOW COLUMNS FROM reviews LIKE 'client_id'");
if ($checkReviewClientId && $checkReviewClientId->num_rows === 0) {
    $conn->query("ALTER TABLE reviews ADD COLUMN client_id INT NULL AFTER id");
}

$checkReviewFundiId = $conn->query("SHOW COLUMNS FROM reviews LIKE 'fundi_id'");
if ($checkReviewFundiId && $checkReviewFundiId->num_rows === 0) {
    $conn->query("ALTER TABLE reviews ADD COLUMN fundi_id INT NULL AFTER client_id");
}

$checkReviewText = $conn->query("SHOW COLUMNS FROM reviews LIKE 'review'");
if ($checkReviewText && $checkReviewText->num_rows === 0) {
    $conn->query("ALTER TABLE reviews ADD COLUMN review TEXT NULL AFTER fundi_id");
}

$checkReviewCreatedAt = $conn->query("SHOW COLUMNS FROM reviews LIKE 'created_at'");
if ($checkReviewCreatedAt && $checkReviewCreatedAt->num_rows === 0) {
    $conn->query("ALTER TABLE reviews ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
}

$checkReviewFundiIndex = $conn->query("SHOW INDEX FROM reviews WHERE Key_name = 'idx_review_fundi'");
if ($checkReviewFundiIndex && $checkReviewFundiIndex->num_rows === 0) {
    $conn->query("ALTER TABLE reviews ADD INDEX idx_review_fundi (fundi_id)");
}
?>
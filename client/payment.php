<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";

if (strtolower(trim((string) ($_SESSION['role'] ?? ''))) !== 'client') {
    header("Location: ../login.php");
    exit();
}

$booking_id = (int) ($_GET['booking_id'] ?? 0);
$message = '';
$messageType = '';

if ($booking_id <= 0) {
    die("No booking selected");
}

$stmt = $conn->prepare(
    "SELECT b.id,
            b.client_id,
            b.amount,
            b.service_name,
            b.status,
            COALESCE(NULLIF(b.service_name, ''), COALESCE(f.service_category, 'Service not set')) AS service_display
     FROM bookings b
     LEFT JOIN fundis f ON f.id = b.fundi_id
     WHERE b.id = ? AND b.client_id = ?
     LIMIT 1"
);

$booking = null;
if ($stmt) {
    $clientUserId = (int) ($_SESSION['user_id'] ?? 0);
    $stmt->bind_param("ii", $booking_id, $clientUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
}

if (!$booking) {
    die("Booking not found");
}

$payableAmount = (float) ($booking['amount'] ?? 0);
$priceConfirmed = false;

if (empty($booking['service_name']) && !empty($booking['service_display'])) {
    $updateServiceStmt = $conn->prepare("UPDATE bookings SET service_name = ? WHERE id = ?");
    if ($updateServiceStmt) {
        $updateServiceStmt->bind_param("si", $booking['service_display'], $booking_id);
        $updateServiceStmt->execute();
        $updateServiceStmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/\D+/', '', trim((string) ($_POST['phone'] ?? '')));
    $priceConfirmed = !empty($_POST['confirm_price']);

    if (strlen($phone) < 10) {
        $message = 'Please enter a valid phone number.';
        $messageType = 'error';
    } elseif (strtolower(trim((string) ($booking['status'] ?? ''))) === 'cancelled') {
        $message = 'This booking was cancelled and cannot be paid.';
        $messageType = 'error';
    } elseif (strtolower(trim((string) ($booking['status'] ?? ''))) === 'paid') {
        $message = 'This booking has already been paid.';
        $messageType = 'error';
    } elseif (!$priceConfirmed) {
        $message = 'Please confirm that this is the agreed price before paying.';
        $messageType = 'error';
    } elseif ($payableAmount <= 0) {
        $message = 'The fundi has not set the agreed price yet.';
        $messageType = 'error';
    } else {
        $insertStmt = $conn->prepare(
            "INSERT INTO payments (booking_id, amount, phone, transaction_status)
             VALUES (?, ?, ?, 'pending')"
        );

        if ($insertStmt) {
            $insertStmt->bind_param("ids", $booking_id, $payableAmount, $phone);
            if ($insertStmt->execute()) {
                $paymentId = (int) $conn->insert_id;
                header('Location: ../payments/stk_push.php?payment_id=' . $paymentId);
                exit();
            } else {
                $message = 'Could not start payment right now. DB error: ' . $conn->error;
                $messageType = 'error';
            }
            $insertStmt->close();
        } else {
            $message = 'Could not prepare payment request. DB error: ' . $conn->error;
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Payment</title>

<style>
body{
    font-family:Arial;
    background:#f7f1ea;
    padding:30px;
}

.box{
    background:#fffaf5;
    padding:20px;
    width:420px;
    max-width:100%;
    margin:auto;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

input, button{
    width:100%;
    padding:10px;
    margin-top:10px;
    border-radius:6px;
    border:1px solid #ccc;
}

button{
    background:#5c4b43;
    color:white;
    border:none;
    cursor:pointer;
}

button:hover { background:#e89cae; }

.alert {
    padding:10px;
    border-radius:6px;
    margin-bottom:10px;
}

.alert.error {
    background:#fce8e8;
    color:#9f2d2d;
}

.alert.success {
    background:#e8f7ee;
    color:#1f7a3f;
}
</style>

</head>

<body>

<div class="box">

<h2>Pay for Service</h2>

<?php if ($message !== ''): ?>
    <div class="alert <?= htmlspecialchars((string) $messageType) ?>"><?= htmlspecialchars((string) $message) ?></div>
<?php endif; ?>

<p><b>Service:</b> <?= htmlspecialchars((string) ($booking['service_display'] ?? 'Service not set')) ?></p>
<p><b>Agreed price:</b>
    <?php if ($payableAmount > 0): ?>
        KES <?= htmlspecialchars((string) number_format($payableAmount, 2)) ?>
    <?php else: ?>
        <span style="color:#a94442;">Pending confirmation from the fundi</span>
    <?php endif; ?>
</p>
<p style="color:#7a6d63; font-size:13px;">Please confirm the amount before you pay. This is the amount the fundi agreed for the service.</p>

<form method="POST">
    <input type="hidden" name="booking_id" value="<?= (int) $booking_id ?>">
    <label style="display:block; margin-top:10px; font-size:13px;">
        <input type="checkbox" name="confirm_price" value="1" <?= $priceConfirmed ? 'checked' : '' ?>> I confirm this is the agreed price.
    </label>
    <input type="text" name="phone" placeholder="07XXXXXXXX" required>
    <button type="submit">Pay with M-Pesa</button>
</form>

</div>

</body>
</html>
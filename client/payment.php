<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/notification_helper.php";

if (strtolower(trim((string) ($_SESSION['role'] ?? ''))) !== 'client') {
    header("Location: ../login.php");
    exit();
}

$booking_id = (int) ($_GET['booking_id'] ?? 0);
$clientUserId = (int) ($_SESSION['user_id'] ?? 0);
$message = '';
$messageType = '';

if ($booking_id <= 0) {
    die("No booking selected");
}

$stmt = $conn->prepare(
    "SELECT b.id,
            b.client_id,
            b.amount,
            b.price_offer_amount,
            b.price_offer_by,
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
$offeredAmount = (float) ($booking['price_offer_amount'] ?? 0);
$offerBy = strtolower(trim((string) ($booking['price_offer_by'] ?? '')));
$clientPhoneInput = '';

function notify_fundi_for_booking($conn, $bookingId, $message, $subject)
{
    $stmt = $conn->prepare(
        "SELECT f.user_id
         FROM bookings b
         INNER JOIN fundis f ON f.id = b.fundi_id
         WHERE b.id = ?
         LIMIT 1"
    );

    if ($stmt) {
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        $fundiUserId = (int) ($row['user_id'] ?? 0);
        if ($fundiUserId > 0) {
            send_notification_with_email($conn, $fundiUserId, $message, $subject);
        }
    }
}

if (empty($booking['service_name']) && !empty($booking['service_display'])) {
    $updateServiceStmt = $conn->prepare("UPDATE bookings SET service_name = ? WHERE id = ?");
    if ($updateServiceStmt) {
        $updateServiceStmt->bind_param("si", $booking['service_display'], $booking_id);
        $updateServiceStmt->execute();
        $updateServiceStmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = strtolower(trim((string) ($_POST['action'] ?? 'pay')));
    $phoneRaw = trim((string) ($_POST['phone'] ?? ''));
    $clientPhoneInput = $phoneRaw;
    $phone = preg_replace('/\D+/', '', $phoneRaw);
    $priceConfirmed = !empty($_POST['confirm_price']);
    $counterAmount = (float) ($_POST['counter_amount'] ?? 0);

    if ($action === 'agree_offer') {
        if ($offeredAmount <= 0 || $offerBy !== 'fundi') {
            $message = 'No valid fundi offer found to agree.';
            $messageType = 'error';
        } else {
            $agreeStmt = $conn->prepare(
                "UPDATE bookings
                 SET amount = ?,
                     price_offer_amount = NULL,
                     price_offer_by = NULL
                 WHERE id = ?
                   AND client_id = ?
                   AND LOWER(COALESCE(price_offer_by, '')) = 'fundi'"
            );

            if ($agreeStmt) {
                $agreeStmt->bind_param("dii", $offeredAmount, $booking_id, $clientUserId);
                if ($agreeStmt->execute() && $agreeStmt->affected_rows > 0) {
                    $payableAmount = $offeredAmount;
                    $offeredAmount = 0;
                    $offerBy = '';
                    $message = 'Price agreed successfully. You can now proceed to payment.';
                    $messageType = 'success';

                    notify_fundi_for_booking(
                        $conn,
                        $booking_id,
                        'Client accepted your offer. Final agreed price is KES ' . number_format($payableAmount, 2) . '.',
                        'Client accepted price offer'
                    );
                } else {
                    $message = 'Unable to agree price at the moment. Please refresh and try again.';
                    $messageType = 'error';
                }
                $agreeStmt->close();
            }
        }
    } elseif ($action === 'counter_offer') {
        if ($counterAmount <= 0) {
            $message = 'Please enter a valid counter offer amount.';
            $messageType = 'error';
        } else {
            $counterStmt = $conn->prepare(
                "UPDATE bookings
                 SET amount = 0,
                     price_offer_amount = ?,
                     price_offer_by = 'client'
                 WHERE id = ? AND client_id = ?"
            );

            if ($counterStmt) {
                $counterStmt->bind_param("dii", $counterAmount, $booking_id, $clientUserId);
                if ($counterStmt->execute()) {
                    $offeredAmount = $counterAmount;
                    $offerBy = 'client';
                    $payableAmount = 0;
                    $message = 'Counter offer sent to fundi successfully.';
                    $messageType = 'success';

                    notify_fundi_for_booking(
                        $conn,
                        $booking_id,
                        'Client sent a counter offer of KES ' . number_format($counterAmount, 2) . '. Please review it in service requests.',
                        'New client counter offer'
                    );
                } else {
                    $message = 'Unable to send counter offer right now.';
                    $messageType = 'error';
                }
                $counterStmt->close();
            }
        }
    } elseif (strlen($phone) < 10) {
        $message = 'Please enter a valid phone number.';
        $messageType = 'error';
    } elseif ($offeredAmount > 0 && $offerBy !== '') {
        $message = 'Please agree the current price offer first or send a counter offer.';
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

.back-wrap {
    margin-top: 14px;
    text-align: center;
}

.back-link {
    display: inline-block;
    padding: 10px 14px;
    background: #5c4b43;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
}

.back-link:hover {
    background: #3f332d;
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
<?php if ($payableAmount > 0): ?>
    <p><b>Agreed price:</b> KES <?= htmlspecialchars((string) number_format($payableAmount, 2)) ?></p>
    <p style="color:#7a6d63; font-size:13px;">Please confirm the amount before you pay. This is the final agreed price.</p>

    <form method="POST">
        <input type="hidden" name="booking_id" value="<?= (int) $booking_id ?>">
        <input type="hidden" name="action" value="pay">
        <label style="display:block; margin-top:10px; font-size:13px;">
            <input type="checkbox" name="confirm_price" value="1" <?= $priceConfirmed ? 'checked' : '' ?>> I confirm this is the agreed price.
        </label>
        <input type="text" name="phone" value="<?= htmlspecialchars((string) $clientPhoneInput) ?>" placeholder="07XXXXXXXX" required>
        <button type="submit">Pay with M-Pesa</button>
    </form>
<?php else: ?>
    <p><b>Price negotiation:</b></p>
    <?php if ($offeredAmount > 0 && $offerBy === 'fundi'): ?>
        <p>Fundi offered: <b>KES <?= htmlspecialchars((string) number_format($offeredAmount, 2)) ?></b></p>

        <form method="POST" style="margin-bottom:8px;">
            <input type="hidden" name="booking_id" value="<?= (int) $booking_id ?>">
            <input type="hidden" name="action" value="agree_offer">
            <button type="submit">Agree Price</button>
        </form>

        <form method="POST">
            <input type="hidden" name="booking_id" value="<?= (int) $booking_id ?>">
            <input type="hidden" name="action" value="counter_offer">
            <input type="number" name="counter_amount" step="0.01" min="0.01" placeholder="Enter your counter offer" required>
            <button type="submit">Disagree and Send Counter Offer</button>
        </form>
    <?php elseif ($offeredAmount > 0 && $offerBy === 'client'): ?>
        <p style="color:#7a6d63;">You sent a counter offer of <b>KES <?= htmlspecialchars((string) number_format($offeredAmount, 2)) ?></b>. Waiting for fundi response.</p>
        <form method="POST">
            <input type="hidden" name="booking_id" value="<?= (int) $booking_id ?>">
            <input type="hidden" name="action" value="counter_offer">
            <input type="number" name="counter_amount" step="0.01" min="0.01" placeholder="Update your counter offer" required>
            <button type="submit">Update Counter Offer</button>
        </form>
    <?php else: ?>
        <p style="color:#a94442;">Waiting for fundi to send a price offer.</p>
    <?php endif; ?>
<?php endif; ?>

<div class="back-wrap">
    <a class="back-link" href="client_dashboard.php">Back to Dashboard</a>
</div>

</div>

</body>
</html>
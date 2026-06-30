<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification_helper.php";

if (strtolower($_SESSION['role'] ?? '') !== 'fundi') {
    header("Location: ../login.php");
    exit();
}

$booking_id = (int) ($_GET['booking_id'] ?? 0);
$fundiUserId = (int) ($_SESSION['user_id'] ?? 0);
$message = '';
$messageType = '';
$formPhone = '';
$formAmount = '';

$fundiProfileId = 0;

$profileStmt = $conn->prepare("SELECT id FROM fundis WHERE user_id = ? LIMIT 1");
if ($profileStmt) {
    $profileStmt->bind_param("i", $fundiUserId);
    $profileStmt->execute();
    $profileResult = $profileStmt->get_result();
    if ($profileRow = $profileResult->fetch_assoc()) {
        $fundiProfileId = (int) $profileRow['id'];
    }
    $profileStmt->close();
}

$sql = "
    SELECT b.id,
           b.client_id,
           b.status,
           b.service_name,
           b.amount,
           u.phone,
           u.fullname
    FROM bookings b
    JOIN users u ON u.id = b.client_id
    WHERE b.id = ? AND b.fundi_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$booking = null;

if ($stmt) {
    $stmt->bind_param("ii", $booking_id, $fundiProfileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
}

if (!$booking) {
    die("Booking not found.");
}

$formPhone = (string) ($booking['phone'] ?? '');
$formAmount = (string) ($booking['amount'] ?? '');

if (strtolower(trim((string) ($booking['status'] ?? ''))) !== 'completed') {
    die("❌ Service not completed yet.");
}

if ((float) ($booking['amount'] ?? 0) <= 0) {
    die("❌ The price must be agreed before the payment request can be sent.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedBookingId = (int) ($_POST['booking_id'] ?? 0);
    $submittedPhoneRaw = trim((string) ($_POST['phone'] ?? ''));
    $submittedPhone = preg_replace('/\D+/', '', $submittedPhoneRaw);
    $formPhone = $submittedPhoneRaw;
    $submittedAmount = (float) ($booking['amount'] ?? 0);
    $formAmount = (string) ($booking['amount'] ?? '');

    if ($submittedBookingId === $booking_id && $submittedAmount > 0 && strlen($submittedPhone) >= 10) {
        $updateBookingStmt = $conn->prepare("UPDATE bookings SET amount = ? WHERE id = ? AND fundi_id = ?");
        if ($updateBookingStmt) {
            $updateBookingStmt->bind_param("dii", $submittedAmount, $submittedBookingId, $fundiProfileId);
            $updateBookingStmt->execute();
            $updateBookingStmt->close();
        }

        $insertStmt = $conn->prepare(
            "INSERT INTO payments (booking_id, amount, phone, transaction_status, fundi_id)
             VALUES (?, ?, ?, 'pending', ?)"
        );

        if ($insertStmt) {
            $insertStmt->bind_param("idsi", $submittedBookingId, $submittedAmount, $submittedPhone, $fundiProfileId);
            if ($insertStmt->execute()) {
                $clientId = (int) ($booking['client_id'] ?? 0);
                if ($clientId > 0) {
                    $noticeMessage = 'The fundi has agreed a price of KES ' . number_format($submittedAmount, 2) . ' for your service "' . ($booking['service_name'] ?: 'your service') . '". Please review it before paying.';
                    send_notification_with_email($conn, $clientId, $noticeMessage, 'Price agreed for your booking');
                }

                $message = 'Payment request submitted successfully.';
                $messageType = 'success';
            } else {
                $message = 'Unable to submit payment request. DB error: ' . $conn->error;
                $messageType = 'error';
            }
            $insertStmt->close();
        } else {
            $message = 'Unable to submit payment request. DB error: ' . $conn->error;
            $messageType = 'error';
        }
    } else {
        $message = 'Unable to submit payment request. Please try again.';
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Payment</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f7f1ea;
            color: #3e3027;
            padding: 24px;
        }

        .shell {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 14px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 24px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .topbar h2 {
            margin: 0 0 6px;
            color: #5c4b43;
        }

        .topbar p {
            margin: 0;
            color: #7a6d63;
        }

        .topbar a {
            text-decoration: none;
            color: #5c4b43;
            font-weight: 600;
        }

        .card {
            background: #fffaf5;
            border: 1px solid #eadfd6;
            border-radius: 12px;
            padding: 18px;
        }

        form {
            display: grid;
            gap: 12px;
        }

        label {
            font-weight: 700;
            color: #5c4b43;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d8cfc4;
            border-radius: 8px;
            background: white;
            color: #3e3027;
        }

        button {
            margin-top: 6px;
            padding: 11px 15px;
            border: none;
            border-radius: 8px;
            background: #5c4b43;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #e89cae;
        }

        .hint {
            font-size: 13px;
            color: #7a6d63;
        }

        .alert {
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 14px;
            font-weight: 600;
        }

        .alert.success {
            background: #e8f7ee;
            color: #1f7a3f;
            border: 1px solid #bfe5cc;
        }

        .alert.error {
            background: #fce8e8;
            color: #9f2d2d;
            border: 1px solid #f0b8b8;
        }

        @media (max-width: 640px) {
            body { padding: 14px; }
            .shell { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="topbar">
            <div>
                <h2>Request Payment</h2>
                <p>Send a payment request for this completed service.</p>
            </div>
            <a href="view_requests.php">← Back</a>
        </div>

        <div class="card">
            <?php if ($message !== ''): ?>
                <div class="alert <?= htmlspecialchars((string) $messageType) ?>">
                    <?= htmlspecialchars((string) $message) ?>
                </div>
            <?php endif; ?>

            <?php if ($messageType === 'success'): ?>
                <div class="hint">You can return to the requests list and continue managing your jobs.</div>
            <?php else: ?>
                <form method="POST" action="<?= htmlspecialchars((string) ($_SERVER['PHP_SELF'] . '?booking_id=' . $booking_id)) ?>">
                    <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">

                    <label>Client Name</label>
                    <input type="text" value="<?= htmlspecialchars((string) ($booking['fullname'] ?? '')) ?>" readonly>

                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars((string) $formPhone) ?>" placeholder="Enter phone (e.g. 07XXXXXXXX or 2547XXXXXXXX)" required>

                    <label>Service</label>
                    <input type="text" value="<?= htmlspecialchars((string) ($booking['service_name'] ?: 'Service not set')) ?>" readonly>

                    <label>Amount (KES)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" value="<?= htmlspecialchars((string) number_format((float) $formAmount, 2, '.', '')) ?>" readonly>

                    <button type="submit">Send Payment Request</button>
                    <div class="hint">Amount is locked to the agreed price. To change it, go back to Service Requests and update the agreed price first.</div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
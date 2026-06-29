<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/notification_helper.php";

if (strtolower(trim((string) ($_SESSION['role'] ?? ''))) !== 'fundi') {
    header("Location: ../login.php");
    exit();
}

$fundiUserId = (int) $_SESSION['user_id'];
$message = '';
$messageType = '';

/* Get fundi profile ID */
$fundiProfileId = 0;

$profileStmt = $conn->prepare("SELECT id FROM fundis WHERE user_id = ? LIMIT 1");
$profileStmt->bind_param("i", $fundiUserId);
$profileStmt->execute();
$res = $profileStmt->get_result();

if ($row = $res->fetch_assoc()) {
    $fundiProfileId = (int) $row['id'];
}
$profileStmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_price'])) {
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $agreedAmount = (float) ($_POST['amount'] ?? 0);

    if ($bookingId > 0 && $fundiProfileId > 0 && $agreedAmount > 0) {
        $updateStmt = $conn->prepare("UPDATE bookings SET amount = ? WHERE id = ? AND fundi_id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param("dii", $agreedAmount, $bookingId, $fundiProfileId);
            if ($updateStmt->execute()) {
                $message = 'Price agreed and saved successfully.';
                $messageType = 'success';

                $clientStmt = $conn->prepare("SELECT client_id FROM bookings WHERE id = ? LIMIT 1");
                if ($clientStmt) {
                    $clientStmt->bind_param("i", $bookingId);
                    $clientStmt->execute();
                    $clientResult = $clientStmt->get_result();
                    $clientRow = $clientResult ? $clientResult->fetch_assoc() : null;
                    if ($clientRow) {
                        $clientId = (int) ($clientRow['client_id'] ?? 0);
                        if ($clientId > 0) {
                            send_notification_with_email(
                                $conn,
                                $clientId,
                                'The fundi has agreed a price of KES ' . number_format($agreedAmount, 2) . ' for your service. Please review it before paying.',
                                'Price agreed for your booking'
                            );
                        }
                    }
                    $clientStmt->close();
                }
            } else {
                $message = 'Unable to save the agreed price.';
                $messageType = 'error';
            }
            $updateStmt->close();
        } else {
            $message = 'Unable to update the booking price.';
            $messageType = 'error';
        }
    } else {
        $message = 'Please enter a valid price before saving.';
        $messageType = 'error';
    }
}

$conn->query(
    "UPDATE bookings b
     LEFT JOIN fundis f ON f.id = b.fundi_id
     SET b.service_name = COALESCE(NULLIF(TRIM(b.service_name), ''), COALESCE(NULLIF(TRIM(f.service_category), ''), 'Service not set'))
     WHERE b.fundi_id = $fundiProfileId
       AND (TRIM(COALESCE(b.service_name, '')) = '' OR b.service_name IS NULL)"
);

/* =========================
   BOOKINGS + SERVICE JOIN
========================= */
$stmt = $conn->prepare(
    "SELECT b.id,
            b.client_id,
            b.booking_date,
            b.status,
            COALESCE(NULLIF(TRIM(b.service_name), ''), COALESCE(NULLIF(TRIM(f.service_category), ''), 'Service not set')) AS service_name,
            b.amount,
            u.fullname AS client_name
     FROM bookings b
     LEFT JOIN users u ON u.id = b.client_id
     LEFT JOIN fundis f ON f.id = b.fundi_id
     WHERE b.fundi_id = ?
     ORDER BY b.booking_date DESC"
);

$result = false;

if ($stmt) {
    $stmt->bind_param("i", $fundiProfileId);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Service Requests</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4efe7;
            margin: 0;
            padding: 20px;
        }

        .shell {
            max-width: 1100px;
            margin: auto;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .back-btn {
            display: inline-block;
            padding: 8px 12px;
            background: #5c4b43;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }

        .back-btn:hover {
            background: #3f332d;
        }

        h2 { margin-top: 0; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #e4dfd7;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #5c4b43;
            color: #fff;
        }

        .btn {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 13px;
            margin-right: 5px;
            text-decoration: none;
        }

        .accept { color: #0a7d5e; }
        .reject { color: #c0392b; }
        .complete { color: #2c3e50; }

        .pay {
            background: #5c4b43;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
        }

        .pay:hover {
            background: #e89cae;
        }

        .muted {
            color: #aaa;
        }

        .alert {
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 12px;
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

        .price-form {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .price-form input {
            width: 110px;
            padding: 6px 8px;
            border: 1px solid #d8cfc4;
            border-radius: 6px;
        }

        .price-form button {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            background: #5c4b43;
            color: white;
            cursor: pointer;
        }

        .price-form button:hover {
            background: #e89cae;
        }
    </style>
</head>

<body>

<div class="shell">

    <div class="topbar">
        <h2>Service Requests</h2>
        <a class="back-btn" href="fundi_dashboard.php">← Back to Dashboard</a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert <?= htmlspecialchars((string) $messageType) ?>">
            <?= htmlspecialchars((string) $message) ?>
        </div>
    <?php endif; ?>

    <table>
        <tr>
            <th>Client</th>
            <th>Service</th>
            <th>Price</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>

                <tr>

                    <!-- CLIENT -->
                    <td>
                        <?php echo htmlspecialchars($row['client_name'] ?: ('Client #' . $row['client_id'])); ?>
                    </td>

                    <!-- SERVICE -->
                    <td>
                        <?php echo htmlspecialchars((string) ($row['service_name'] ?? 'Service not set')); ?>
                    </td>

                    <!-- PRICE -->
                    <td>
                        <?php if (!empty($row['amount']) && (float) $row['amount'] > 0): ?>
                            <?php echo htmlspecialchars((string) number_format((float) $row['amount'], 2)); ?>
                        <?php else: ?>
                            <span class="muted">Pending agreement</span>
                        <?php endif; ?>
                        <form class="price-form" method="POST">
                            <input type="hidden" name="booking_id" value="<?= (int) $row['id'] ?>">
                            <input type="number" name="amount" step="0.01" min="0.01" placeholder="Enter agreed price" required>
                            <button type="submit" name="save_price">Agree Price</button>
                        </form>
                    </td>

                    <!-- DATE -->
                    <td>
                        <?php echo htmlspecialchars($row['booking_date']); ?>
                    </td>

                    <!-- STATUS -->
                    <td>
                        <?php echo htmlspecialchars($row['status']); ?>
                    </td>

                    <!-- ACTIONS -->
                    <td>

                        <a class="btn accept"
                           href="update_booking.php?id=<?php echo (int)$row['id']; ?>&action=accept">
                           ✅ Accept
                        </a>

                        <a class="btn reject"
                           href="update_booking.php?id=<?php echo (int)$row['id']; ?>&action=reject">
                           ❌ Reject
                        </a>

                        <a class="btn complete"
                           href="complete_service.php?id=<?php echo (int)$row['id']; ?>">
                           ✔️ Complete
                        </a>

                        <!-- PAYMENT ONLY AFTER COMPLETION -->
                        <?php if ($row['status'] === 'completed'): ?>
                            <a class="btn pay"
                               href="fundi_request_payment.php?booking_id=<?php echo (int)$row['id']; ?>">
                               💳 Request Payment
                            </a>
                        <?php else: ?>
                            <span class="muted">Payment Locked</span>
                        <?php endif; ?>

                    </td>

                </tr>

            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No requests found yet.</td>
            </tr>
        <?php endif; ?>

    </table>

</div>

</body>
</html>

<?php
if ($stmt) {
    $stmt->close();
}
?>
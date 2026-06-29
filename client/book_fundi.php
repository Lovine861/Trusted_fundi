<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/db.php";

$currentRole = strtolower(trim((string) ($_SESSION['role'] ?? '')));
if ($currentRole !== 'client') {
    header("Location: ../login.php");
    exit();
}

$clientId = (int) $_SESSION['user_id'];
$fundiId = isset($_GET['fundi_id']) ? (int) $_GET['fundi_id'] : 0;
$message = "";
$messageType = "";

if ($fundiId <= 0) {
    $message = "No fundi selected.";
    $messageType = "error";
}

$fundi = null;
if ($fundiId > 0) {
    $stmt = $conn->prepare(
    "SELECT fundis.id AS fundi_id,
        users.id AS user_id,
                users.fullname,
                users.email,
                users.phone,
                COALESCE(fundis.verification_status, users.status) AS verification_status,
                COALESCE(fundis.service_category, 'General Service') AS service_category,
                COALESCE(fundis.location, 'Not set') AS location
     FROM fundis
     JOIN users ON fundis.user_id = users.id
     WHERE fundis.id = ?
       AND users.role = 'fundi'
       AND users.status = 'approved'
             AND LOWER(COALESCE(fundis.verification_status, 'pending')) IN ('approved', 'verified')
         LIMIT 1"
    );

    if ($stmt) {
        $stmt->bind_param("i", $fundiId);
        $stmt->execute();
        $result = $stmt->get_result();
        $fundi = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }

    if (!$fundi && $message === "") {
        $message = "Selected fundi is not available.";
        $messageType = "error";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $fundi) {
    $bookingDate = trim($_POST['booking_date'] ?? '');

    if ($bookingDate === "") {
        $message = "Please choose a booking date.";
        $messageType = "error";
    } else {
        $serviceName = trim((string) ($fundi['service_category'] ?? 'General Service'));
        $amount = 0.00;

        $insertStmt = $conn->prepare(
            "INSERT INTO bookings (client_id, fundi_id, booking_date, service_name, amount, status)
             VALUES (?, ?, ?, ?, ?, 'pending')"
        );

        if ($insertStmt) {
            $insertStmt->bind_param("iissd", $clientId, $fundiId, $bookingDate, $serviceName, $amount);

            if ($insertStmt->execute()) {
                $message = "Booking submitted successfully.";
                $messageType = "success";
            } else {
                $message = "Could not submit booking: " . $conn->error;
                $messageType = "error";
            }

            $insertStmt->close();
        } else {
            $message = "Could not prepare booking request.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Fundi</title>
    <style>
        :root {
            --bg: #f4efe8;
            --panel: #ffffff;
            --ink: #2c2a2a;
            --muted: #6f6b67;
            --brand: #356859;
            --brand-2: #4a8c78;
            --error: #9f2d2d;
            --ok: #1f7a3f;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: radial-gradient(circle at top right, #efe4d2, var(--bg));
            color: var(--ink);
            padding: 28px;
        }

        .shell {
            max-width: 760px;
            margin: 0 auto;
            background: var(--panel);
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.08);
            padding: 24px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .top a {
            text-decoration: none;
            background: var(--brand);
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
        }

        h2 { margin: 14px 0 8px; }

        .sub {
            margin: 0 0 18px;
            color: var(--muted);
        }

        .fundi-card {
            border: 1px solid #ebe7e2;
            border-radius: 10px;
            padding: 16px;
            background: #fcfbf9;
            margin-bottom: 16px;
        }

        .title-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 9px;
            border-radius: 999px;
        }

        .badge.verified {
            background: #e8f7ee;
            color: #1f7a3f;
            border: 1px solid #bfe5cc;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .label { color: var(--muted); font-size: 13px; }
        .value { font-weight: 600; }

        .msg {
            padding: 12px;
            border-radius: 8px;
            margin: 12px 0 16px;
        }

        .msg.error { background: #fce8e8; color: var(--error); }
        .msg.success { background: #e8f7ee; color: var(--ok); }

        form {
            border: 1px solid #ebe7e2;
            border-radius: 10px;
            padding: 16px;
        }

        input[type="date"] {
            width: 100%;
            margin-top: 8px;
            padding: 11px;
            border: 1px solid #d6d2cc;
            border-radius: 8px;
            font-size: 14px;
        }

        button {
            margin-top: 14px;
            border: 0;
            background: linear-gradient(90deg, var(--brand), var(--brand-2));
            color: #fff;
            padding: 11px 16px;
            border-radius: 8px;
            cursor: pointer;
        }

        @media (max-width: 640px) {
            body { padding: 16px; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="top">
            <strong>Trusted Fundi</strong>
            <a href="services.php">🔎 Back to Search</a>
        </div>

        <h2>Book Fundi</h2>
        <p class="sub">Choose a date and submit your booking request.</p>

        <?php if ($message !== ""): ?>
            <div class="msg <?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($fundi): ?>
            <div class="fundi-card">
                <div class="title-row">
                    <div class="label">Selected Fundi</div>
                    <?php if (in_array(strtolower((string) ($fundi['verification_status'] ?? '')), ['approved', 'verified'], true)): ?>
                        <span class="badge verified">Admin Verified</span>
                    <?php endif; ?>
                </div>
                <div class="grid">
                    <div>
                        <div class="label">Fundi Name</div>
                        <div class="value"><?php echo htmlspecialchars($fundi['fullname']); ?></div>
                    </div>
                    <div>
                        <div class="label">Service</div>
                        <div class="value"><?php echo htmlspecialchars($fundi['service_category']); ?></div>
                    </div>
                    <div>
                        <div class="label">Location</div>
                        <div class="value"><?php echo htmlspecialchars($fundi['location']); ?></div>
                    </div>
                    <div>
                        <div class="label">Contact</div>
                        <div class="value"><?php echo htmlspecialchars((string) ($fundi['phone'] ?: $fundi['email'])); ?></div>
                    </div>
                </div>
            </div>

            <form method="POST">
                <label for="booking_date">Preferred Date</label>
                <input id="booking_date" type="date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
                <button type="submit" name="book">Submit Booking</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
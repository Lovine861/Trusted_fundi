<?php
require_once __DIR__ . "/../includes/session.php";
include "../includes/db.php";

$currentRole = strtolower(trim((string) ($_SESSION['role'] ?? '')));

if ($currentRole !== 'client') {
    header("Location: ../login.php");
    exit();
}

$client_id = (int) ($_SESSION['user_id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT b.*, 
            COALESCE(NULLIF(b.service_name, ''), COALESCE(f.service_category, 'Service not set')) AS service_name,
            COALESCE(b.amount, 0) AS amount,
            COALESCE(b.price_offer_amount, 0) AS price_offer_amount,
            COALESCE(b.price_offer_by, '') AS price_offer_by,
            COALESCE(
                (SELECT p.transaction_status
                 FROM payments p
                 WHERE p.booking_id = b.id
                 ORDER BY p.id DESC
                 LIMIT 1),
                ''
            ) AS latest_payment_status
     FROM bookings b
     LEFT JOIN fundis f ON f.id = b.fundi_id
     WHERE b.client_id = ?
     ORDER BY b.id DESC"
);

if ($stmt) {
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $query = $stmt->get_result();
    $stmt->close();
} else {
    $query = false;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>My Bookings</title>

<style>
body{
    font-family:Arial;
    background:#f7f1ea;
    margin:0;
    padding:20px;
}

.container{
    max-width:900px;
    margin:auto;
}

.card{
    background:#fffaf5;
    padding:15px;
    margin-bottom:15px;
    border-radius:10px;
    box-shadow:0 3px 10px rgba(0,0,0,0.08);
}

.btn{
    display:inline-block;
    padding:10px 15px;
    background:#e8b7b0;
    color:white;
    text-decoration:none;
    border-radius:6px;
    margin-top:10px;
}

.btn:hover{
    background:#d9a39b;
}

.status{
    padding:5px 10px;
    border-radius:6px;
    display:inline-block;
    font-size:12px;
}

.pending{
    background:#ffe0e0;
    color:#a94442;
}

.paid{
    background:#d4edda;
    color:#155724;
}

.back-btn{
    text-align:center;
    margin-top:30px;
}

.back-btn a{
    display:inline-block;
    padding:12px 20px;
    background:#5c4b43;
    color:white;
    text-decoration:none;
    border-radius:8px;
    font-weight:bold;
}

.back-btn a:hover{
    background:#3f332d;
}
</style>

</head>

<body>

<div class="container">

<h2>My Bookings</h2>

<?php if ($query && mysqli_num_rows($query) > 0): ?>

    <?php while ($row = mysqli_fetch_assoc($query)): ?>

        <div class="card">

            <p style="margin-top:0; color:#7a6d63;"><b>Booking ID:</b> #<?php echo (int) $row['id']; ?></p>

            <!-- SERVICE NAME (FIXED) -->
            <h3>
                <?php echo htmlspecialchars($row['service_name'] ?? 'Service not set'); ?>
            </h3>

            <!-- PRICE -->
            <p>
                <b>Agreed price:</b>
                <?php if (!empty($row['amount']) && (float) $row['amount'] > 0): ?>
                    KES <?php echo htmlspecialchars((string) number_format((float) $row['amount'], 2)); ?>
                <?php elseif (!empty($row['price_offer_amount']) && (float) $row['price_offer_amount'] > 0): ?>
                    <span style="color:#7a6d63;">Offer pending: KES <?php echo htmlspecialchars((string) number_format((float) $row['price_offer_amount'], 2)); ?>
                    (<?php echo strtolower((string) ($row['price_offer_by'] ?? '')) === 'fundi' ? 'from fundi' : 'your counter offer'; ?>)</span>
                <?php else: ?>
                    <span style="color:#a94442;">Waiting for fundi to agree the price</span>
                <?php endif; ?>
            </p>
            <p style="color:#7a6d63; font-size:13px;">
                This amount must be agreed by the fundi before you can pay for the service.
            </p>

            <!-- STATUS -->
            <p>
                <b>Status:</b>

                <?php $bookingStatus = strtolower(trim((string) ($row['status'] ?? ''))); $paymentStatus = strtolower(trim((string) ($row['latest_payment_status'] ?? ''))); ?>
                <?php if ($bookingStatus === 'paid' || $paymentStatus === 'success'): ?>
                    <span class="status paid">Paid</span>
                <?php else: ?>
                    <span class="status pending">Pending</span>
                <?php endif; ?>
            </p>

            <!-- PAYMENT BUTTON -->
            <?php $bookingStatus = strtolower(trim((string) ($row['status'] ?? ''))); $paymentStatus = strtolower(trim((string) ($row['latest_payment_status'] ?? ''))); ?>
            <?php if ($bookingStatus === 'paid' || $paymentStatus === 'success'): ?>
                <p>Payment completed ✔</p>
            <?php elseif (!empty($row['amount']) && (float) $row['amount'] > 0): ?>
                <a class="btn"
                   href="payment.php?booking_id=<?php echo (int)$row['id']; ?>">
                    Pay Now
                </a>
            <?php elseif (!empty($row['price_offer_amount']) && (float) $row['price_offer_amount'] > 0): ?>
                <a class="btn"
                   href="payment.php?booking_id=<?php echo (int)$row['id']; ?>">
                    Review Offer
                </a>
            <?php else: ?>
                <p style="color:#a94442; margin-top:10px;">Waiting for the fundi to agree the price.</p>
            <?php endif; ?>

        </div>

    <?php endwhile; ?>

<?php else: ?>

    <p>No bookings found.</p>

<?php endif; ?>

<!-- BACK BUTTON -->
<div class="back-btn">
    <a href="client_dashboard.php">⬅ Back to Dashboard</a>
</div>

</div>

</body>
</html>
<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/db.php";

if (strtolower(trim((string) ($_SESSION['role'] ?? ''))) !== 'fundi') {
    header("Location: ../login.php");
    exit();
}

$fundiUserId = (int) ($_SESSION['user_id'] ?? 0);
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

/* =========================
   SAVE PRICE
========================= */
if (isset($_POST['save_price'])) {
    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);

    if ($booking_id > 0 && $fundiProfileId > 0) {
        $stmt = $conn->prepare("UPDATE bookings SET amount = ? WHERE id = ? AND fundi_id = ?");
        $stmt->bind_param("dii", $amount, $booking_id, $fundiProfileId);

        if ($stmt->execute()) {
            echo "<script>alert('Price saved successfully!');</script>";
        } else {
            echo "<script>alert('Failed to save price');</script>";
        }

        $stmt->close();
    }
}

/* =========================
   GET FUNDI BOOKINGS
========================= */
$stmt = $conn->prepare(
    "SELECT b.id, b.booking_date, b.status, b.amount, u.fullname
     FROM bookings b
     JOIN users u ON u.id = b.client_id
     WHERE b.fundi_id = ?
     ORDER BY b.booking_date DESC"
);

$result = false;
if ($stmt) {
    $stmt->bind_param("i", $fundiProfileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Set Service Price</title>

<style>
body{
    font-family:Arial;
    background:#f7f1ea;
    margin:0;
    padding:20px;
}

.container{
    width:90%;
    margin:auto;
}

.card{
    background:white;
    padding:20px;
    margin-bottom:20px;
    border-radius:12px;
    box-shadow:0 4px 10px rgba(0,0,0,.1);
}

input{
    width:150px;
    padding:10px;
    border:1px solid #ccc;
    border-radius:8px;
}

button{
    padding:10px 15px;
    background:#e89cae;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

button:hover{
    background:#d98699;
}

.back{
    text-decoration:none;
    background:#5c4b43;
    color:white;
    padding:10px 15px;
    border-radius:8px;
}
</style>

</head>

<body>

<div class="container">

<a href="fundi_dashboard.php" class="back">← Back to Dashboard</a>

<h2>Client Booking Requests</h2>

<?php while ($row = $result->fetch_assoc()): ?>

<div class="card">

<h3><?php echo htmlspecialchars($row['fullname']); ?></h3>

<p>Booking Date: <?php echo $row['booking_date']; ?></p>
<p>Status: <?php echo $row['status']; ?></p>

<form method="POST">

<input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">

<input type="number"
       name="amount"
       placeholder="Enter Price"
       required>

<button type="submit" name="save_price">
Save Price
</button>

</form>

</div>

<?php endwhile; ?>

</div>

</body>
</html>

<?php
$stmt->close();
?>
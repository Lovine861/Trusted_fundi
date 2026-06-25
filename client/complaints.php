<?php
session_start();
include "../includes/db.php";
include "../includes/session.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$message = "";
$messageType = "";
$client_id = (int) $_SESSION['user_id'];

$bookings = [];
$bookingsStmt = $conn->prepare(
    "SELECT bookings.id,
            bookings.booking_date,
            bookings.status,
            COALESCE(users.fullname, 'Fundi') AS fundi_name,
            COALESCE(fundis.service_category, 'General Service') AS service_category
     FROM bookings
     LEFT JOIN fundis ON fundis.id = bookings.fundi_id
     LEFT JOIN users ON users.id = fundis.user_id
     WHERE bookings.client_id = ?
     ORDER BY bookings.id DESC"
);

if ($bookingsStmt) {
    $bookingsStmt->bind_param("i", $client_id);
    $bookingsStmt->execute();
    $bookingsResult = $bookingsStmt->get_result();
    if ($bookingsResult) {
        while ($b = $bookingsResult->fetch_assoc()) {
            $bookings[] = $b;
        }
    }
    $bookingsStmt->close();
}

if(isset($_POST['send'])){

    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $complaint = trim($_POST['complaint'] ?? '');

    if ($booking_id <= 0 || $complaint === '') {
        $message = "Please provide a valid booking id and complaint.";
        $messageType = "error";
    } else {
        // Ensure the complaint is for a booking that belongs to this client.
        $bookingStmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND client_id = ? LIMIT 1");
        if ($bookingStmt) {
            $bookingStmt->bind_param("ii", $booking_id, $client_id);
            $bookingStmt->execute();
            $bookingResult = $bookingStmt->get_result();
            $booking = $bookingResult ? $bookingResult->fetch_assoc() : null;
            $bookingStmt->close();

            if (!$booking) {
                $message = "Booking not found for your account.";
                $messageType = "error";
            } else {
                $insertStmt = $conn->prepare("INSERT INTO complaints (client_id, booking_id, complaint) VALUES (?, ?, ?)");
                if ($insertStmt) {
                    $insertStmt->bind_param("iis", $client_id, $booking_id, $complaint);

                    if ($insertStmt->execute()) {
                        $message = "Complaint submitted successfully.";
                        $messageType = "success";
                    } else {
                        $message = "Failed to submit complaint: " . $conn->error;
                        $messageType = "error";
                    }

                    $insertStmt->close();
                } else {
                    $message = "Could not prepare complaint request.";
                    $messageType = "error";
                }
            }
        } else {
            $message = "Could not validate booking.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Complaints</title>

<style>
body{
    background:#F7F1EA;
    font-family:Arial,sans-serif;
}

.container{
    width:500px;
    margin:50px auto;
    background:white;
    padding:30px;
    border-radius:20px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
    color:#5C4B43;
}

input,textarea{
    width:100%;
    padding:12px;
    margin-top:10px;
    margin-bottom:15px;
    border:1px solid #ddd;
    border-radius:8px;
}

select{
    width:100%;
    padding:12px;
    margin-top:10px;
    margin-bottom:15px;
    border:1px solid #ddd;
    border-radius:8px;
}

button{
    width:100%;
    padding:12px;
    background:#E8A0BF;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.msg{
    padding:10px;
    border-radius:8px;
    margin-bottom:12px;
}

.msg.success{
    background:#e8f7ee;
    color:#1f7a3f;
}

.msg.error{
    background:#fbe8e8;
    color:#8f2b2b;
}

a{
    color:#E8A0BF;
    text-decoration:none;
}
</style>

</head>
<body>

<div class="container">

<h2>Submit Complaint</h2>

<?php if ($message !== ""): ?>
    <div class="msg <?php echo htmlspecialchars($messageType); ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<form method="POST">

<label for="booking_id"><b>Booking</b></label>
<select id="booking_id" name="booking_id" required>
    <option value="">Select booking ID</option>
    <?php foreach ($bookings as $b): ?>
        <option value="<?php echo (int) $b['id']; ?>" <?php echo ((int) ($_POST['booking_id'] ?? 0) === (int) $b['id']) ? 'selected' : ''; ?>>
            #<?php echo (int) $b['id']; ?> | <?php echo htmlspecialchars((string) $b['fundi_name']); ?> | <?php echo htmlspecialchars((string) $b['service_category']); ?> | <?php echo htmlspecialchars((string) $b['booking_date']); ?> | <?php echo htmlspecialchars((string) $b['status']); ?>
        </option>
    <?php endforeach; ?>
</select>

<textarea
name="complaint"
placeholder="Describe your complaint"
required></textarea>

<button type="submit" name="send">
Submit Complaint
</button>

</form>

<?php if (count($bookings) === 0): ?>
    <p>No bookings found yet. Book a fundi first, then submit a complaint.</p>
<?php endif; ?>

<br>

<a href="client_dashboard.php">
⬅️ Back to Dashboard
</a>

</div>

</body>
</html>
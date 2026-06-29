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
$clientId = (int) $_SESSION['user_id'];

$bookings = [];
$bookingsStmt = $conn->prepare(
    "SELECT bookings.id,
            bookings.booking_date,
            bookings.status,
            COALESCE(users.fullname, 'Fundi') AS fundi_name,
            COALESCE(fundis.service_category, 'General Service') AS service_category,
            COALESCE(
                (SELECT p.transaction_status
                 FROM payments p
                 WHERE p.booking_id = bookings.id
                 ORDER BY p.id DESC
                 LIMIT 1),
                ''
            ) AS latest_payment_status
     FROM bookings
     LEFT JOIN fundis ON fundis.id = bookings.fundi_id
     LEFT JOIN users ON users.id = fundis.user_id
     WHERE bookings.client_id = ?
       AND NOT EXISTS (
           SELECT 1
           FROM reviews r
           WHERE r.client_id = bookings.client_id
             AND r.booking_id = bookings.id
       )
       AND (
           LOWER(COALESCE(bookings.status, 'pending')) = 'completed'
           OR LOWER(COALESCE(bookings.status, 'pending')) = 'paid'
           OR LOWER(COALESCE(
               (SELECT p.transaction_status
                FROM payments p
                WHERE p.booking_id = bookings.id
                ORDER BY p.id DESC
                LIMIT 1),
               ''
           )) = 'success'
       )
     ORDER BY bookings.id DESC"
);

if ($bookingsStmt) {
    $bookingsStmt->bind_param("i", $clientId);
    $bookingsStmt->execute();
    $bookingsResult = $bookingsStmt->get_result();
    if ($bookingsResult) {
        while ($b = $bookingsResult->fetch_assoc()) {
            $bookings[] = $b;
        }
    }
    $bookingsStmt->close();
}

if(isset($_POST['review'])){

    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $rating = (int) ($_POST['rating'] ?? 0);
    $review = trim($_POST['comment'] ?? '');

    if ($bookingId <= 0 || $rating < 1 || $rating > 5 || $review === '') {
        $message = "Please provide valid review details.";
        $messageType = "error";
    } else {
                // Confirm booking belongs to this client and is completed before review.
                $bookingStmt = $conn->prepare(
                        "SELECT fundi_id
                         FROM bookings
                         WHERE id = ?
                             AND client_id = ?
                             AND (
                                 LOWER(COALESCE(status, 'pending')) = 'completed'
                                 OR LOWER(COALESCE(status, 'pending')) = 'paid'
                                 OR LOWER(COALESCE(
                                     (SELECT p.transaction_status
                                      FROM payments p
                                      WHERE p.booking_id = bookings.id
                                      ORDER BY p.id DESC
                                      LIMIT 1),
                                     ''
                                 )) = 'success'
                             )
                         LIMIT 1"
                );
        if ($bookingStmt) {
            $bookingStmt->bind_param("ii", $bookingId, $clientId);
            $bookingStmt->execute();
            $bookingResult = $bookingStmt->get_result();
            $booking = $bookingResult ? $bookingResult->fetch_assoc() : null;
            $bookingStmt->close();

            if (!$booking) {
                $message = "Only completed, paid, or successfully paid bookings can be reviewed.";
                $messageType = "error";
            } else {
                $existingReviewStmt = $conn->prepare(
                    "SELECT id FROM reviews WHERE client_id = ? AND booking_id = ? LIMIT 1"
                );
                if ($existingReviewStmt) {
                    $existingReviewStmt->bind_param("ii", $clientId, $bookingId);
                    $existingReviewStmt->execute();
                    $existingReviewResult = $existingReviewStmt->get_result();
                    $existingReview = $existingReviewResult ? $existingReviewResult->fetch_assoc() : null;
                    $existingReviewStmt->close();

                    if ($existingReview) {
                        $message = "You have already reviewed this booking.";
                        $messageType = "error";
                    } else {
                        $fundiId = (int) $booking['fundi_id'];
                        $insertStmt = $conn->prepare("INSERT INTO reviews (client_id, fundi_id, booking_id, review, rating) VALUES (?, ?, ?, ?, ?)");
                        if ($insertStmt) {
                            $insertStmt->bind_param("iiisi", $clientId, $fundiId, $bookingId, $review, $rating);

                            if ($insertStmt->execute()) {
                                $newReviewId = (int) $insertStmt->insert_id;
                                $message = "Review submitted successfully. Reference ID: #" . $newReviewId;
                                $messageType = "success";
                            } else {
                                $message = "Failed to submit review: " . $conn->error;
                                $messageType = "error";
                            }

                            $insertStmt->close();
                        } else {
                            $message = "Could not prepare review request: " . $conn->error;
                            $messageType = "error";
                        }
                    }
                } else {
                    $message = "Could not validate previous reviews.";
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
<title>Reviews</title>

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

<h2>Leave a Review</h2>
<p>Completed, paid, or successfully paid bookings appear in the list below.</p>

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

<input type="number" name="rating"
min="1" max="5"
placeholder="Rating (1-5)" required>

<textarea name="comment"
placeholder="Write your review"
required></textarea>

<button type="submit" name="review">
Submit Review
</button>

</form>

<?php if (count($bookings) === 0): ?>
    <p>No completed or paid bookings found yet. Complete a job or finish a payment first, then submit a review.</p>
<?php endif; ?>

<br>

<a href="client_dashboard.php">
⬅️ Back to Dashboard
</a>

</div>

</body>
</html>
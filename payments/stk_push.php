<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/mpesa_config.php";

/* =========================
   VALIDATE INPUT
========================= */
$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

if ($payment_id <= 0) {
    die("Invalid payment ID");
}

// Allow the app to continue in demo/test mode when the passkey is not available yet.
$demoMode = (MPESA_PASSKEY === 'YOUR_PASSKEY' || MPESA_PASSKEY === '');

/* =========================
   GET PAYMENT
========================= */
$stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();

if (!$payment) {
    die("Payment not found");
}

$phone = $payment['phone'];
$amount = $payment['amount'];

/* =========================
   FORMAT PHONE (IMPORTANT)
========================= */
// Convert 07XXXXXXXX → 2547XXXXXXXX
$phone = preg_replace('/^0/', '254', $phone);

/* =========================
   GET ACCESS TOKEN
========================= */
if (!$demoMode && (MPESA_CONSUMER_KEY === 'YOUR_CONSUMER_KEY' || MPESA_CONSUMER_SECRET === 'YOUR_CONSUMER_SECRET')) {
    die('MPesa credentials are not configured yet. Please update includes/mpesa_config.php with your real Safaricom sandbox values.');
}

if ($demoMode) {
    $update = $conn->prepare("UPDATE payments SET transaction_status = 'success', mpesa_receipt = ? WHERE id = ?");
    $demoReceipt = 'DEMO-' . $payment_id;
    $update->bind_param("si", $demoReceipt, $payment_id);
    $update->execute();

    $bookingUpdate = $conn->prepare("UPDATE bookings SET status = 'paid' WHERE id = (SELECT booking_id FROM payments WHERE id = ? LIMIT 1)");
    $bookingUpdate->bind_param("i", $payment_id);
    $bookingUpdate->execute();

    echo "🧪 Demo payment completed. The booking has been marked as paid.<br><br>";
    echo "<a href='../client/my%20bookings.php' style='display:inline-block;padding:10px 14px;background:#5c4b43;color:white;text-decoration:none;border-radius:6px;'>← Back to My Bookings</a>";
    exit();
}

$credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, MPESA_TOKEN_URL);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: Basic " . $credentials
]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

if (!isset($result['access_token'])) {
    die('Failed to get access token. Safaricom response: ' . json_encode($result));
}

$access_token = $result['access_token'];

/* =========================
   STK PUSH REQUEST
========================= */
$timestamp = date('YmdHis');
$password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

$data = [
    "BusinessShortCode" => MPESA_SHORTCODE,
    "Password" => $password,
    "Timestamp" => $timestamp,
    "TransactionType" => "CustomerPayBillOnline",
    "Amount" => (int)$amount,
    "PartyA" => $phone,
    "PartyB" => MPESA_SHORTCODE,
    "PhoneNumber" => $phone,
    "CallBackURL" => MPESA_CALLBACK_URL,
    "AccountReference" => "Fundi Payment",
    "TransactionDesc" => "Service Payment"
];

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, MPESA_STK_URL);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $access_token,
    "Content-Type: application/json"
]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

/* =========================
   SAVE CHECKOUT REQUEST ID
========================= */
if (isset($result['CheckoutRequestID'])) {

    $update = $conn->prepare("
        UPDATE payments
        SET checkout_request_id = ?, transaction_status = 'pending'
        WHERE id = ?
    ");

    $update->bind_param("si", $result['CheckoutRequestID'], $payment_id);
    $update->execute();
}

/* =========================
   RESPONSE
========================= */
if (isset($result['ResponseCode']) && $result['ResponseCode'] == "0") {
    echo "📲 STK Push sent successfully. Check your phone.<br><br>";
    echo "<a href='../client/my%20bookings.php' style='display:inline-block;padding:10px 14px;background:#5c4b43;color:white;text-decoration:none;border-radius:6px;'>← Back to My Bookings</a>";
} else {
    echo "❌ STK Push failed. Safaricom response: " . json_encode($result);
}
?>
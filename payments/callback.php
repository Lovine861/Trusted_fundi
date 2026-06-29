<?php
require_once __DIR__ . "/../includes/db.php";

/* =========================
   READ CALLBACK DATA
========================= */
$data = file_get_contents("php://input");
$log = json_decode($data, true);

/* LOG FOR DEBUGGING */
file_put_contents("mpesa_log.txt", $data . PHP_EOL, FILE_APPEND);

/* =========================
   VALIDATE RESPONSE
========================= */
$stk = $log['Body']['stkCallback'] ?? null;

if (!$stk) {
    exit;
}

$checkoutRequestID = $stk['CheckoutRequestID'];
$resultCode = $stk['ResultCode'];

/* =========================
   SUCCESS PAYMENT
========================= */
if ($resultCode == 0) {

    $items = $stk['CallbackMetadata']['Item'] ?? [];

    $mpesaReceipt = "";
    $amount = 0;

    foreach ($items as $item) {
        if (($item['Name'] ?? '') == 'MpesaReceiptNumber') {
            $mpesaReceipt = $item['Value'];
        }

        if (($item['Name'] ?? '') == 'Amount') {
            $amount = $item['Value'];
        }
    }

    /* UPDATE PAYMENT */
    $stmt = $conn->prepare("
        UPDATE payments
        SET transaction_status='success',
            mpesa_receipt=?
        WHERE checkout_request_id=?
    ");

    $stmt->bind_param("ss", $mpesaReceipt, $checkoutRequestID);
    $stmt->execute();

    $bookingStmt = $conn->prepare(
        "UPDATE bookings
         SET status = 'paid'
         WHERE id = (
             SELECT booking_id
             FROM payments
             WHERE checkout_request_id = ?
             LIMIT 1
         )"
    );

    if ($bookingStmt) {
        $bookingStmt->bind_param("s", $checkoutRequestID);
        $bookingStmt->execute();
        $bookingStmt->close();
    }
}

/* =========================
   FAILED PAYMENT
========================= */
else {
    $stmt = $conn->prepare("
        UPDATE payments
        SET transaction_status='failed'
        WHERE checkout_request_id=?
    ");

    $stmt->bind_param("s", $checkoutRequestID);
    $stmt->execute();
}
?>
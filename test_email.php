 <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "START<br>";

include "send_email.php";

echo "LOADED<br>";

$sent = sendEmail(
    "lovine.muema@strathmore.edu",
    "Test Email",
    "This is a test email from Trusted Fundi system"
);

if ($sent) {
    echo "SUCCESS";
} else {
    echo "FAILED";
    $err = function_exists('getLastSendEmailError') ? getLastSendEmailError() : '';
    if ($err !== '') {
        echo "<br>Mailer Error: " . htmlspecialchars($err);
    }
}
?>
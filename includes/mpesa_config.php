<?php

/* =========================
   M-PESA CONFIG (SANDBOX)
========================= */

// Safaricom sandbox credentials from your developer app.
define('MPESA_CONSUMER_KEY', '6VYTWkmTMWTj9TDGsUzmM164gj5uu0XNFEf2lkElljFjrcSl');
define('MPESA_CONSUMER_SECRET', '2VdZpAl2RQAQAjKoE2X478fwDWS98adefwqt9aL6il9Ck3iCTjYYqc4OXQQYbFum');

define('MPESA_SHORTCODE', '174379'); // sandbox till/paybill
define('MPESA_PASSKEY', 'YOUR_PASSKEY');

/* URLs */
define('MPESA_TOKEN_URL', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

define('MPESA_STK_URL', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');

define('MPESA_CALLBACK_URL', 'https://yourdomain.com/callback.php');

?>
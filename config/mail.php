<?php

$mailUser = 'trustedfundi.notifications@gmail.com';
$mailPass = 'qoyr spnw ckij zbsk';
$mailFrom = 'trustedfundi.notifications@gmail.com';
$mailHost = 'smtp.gmail.com';
$mailPort = 465;
$mailEnc = 'ssl';
$mailAuth = true;

return [
    // Set to true to send emails.
    'enabled' => true,

    // SMTP server settings.
    'host' => $mailHost,
    'port' => $mailPort,
    'encryption' => $mailEnc, // tls, ssl, or none
    'auth' => $mailAuth,

    // SMTP credentials (required when auth=true).
    'username' => $mailUser,
    'password' => $mailPass,

    // Sender identity shown in the email.
    'from_email' => $mailFrom,
    'from_name' => 'Trusted Fundi',

    // Timeout in seconds.
    'timeout' => 20,
];

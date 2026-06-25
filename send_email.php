<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require __DIR__ . '/vendor/PHPMailer/src/Exception.php';
require __DIR__ . '/vendor/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/vendor/PHPMailer/src/SMTP.php';

$GLOBALS['SEND_EMAIL_LAST_ERROR'] = '';

function getLastSendEmailError()
{
    return (string) ($GLOBALS['SEND_EMAIL_LAST_ERROR'] ?? '');
}

// Function to send email
function sendEmail($to, $subject, $message) {

    // Load config INSIDE function (fixes all scope issues)
    $config = require __DIR__ . '/mail_config.php';

    $mail = new PHPMailer(true);

    try {
        // SMTP setup
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Host = trim((string) ($config['host'] ?? 'smtp.gmail.com'));
        $mail->SMTPAuth = (bool) ($config['auth'] ?? true);
        $mail->Username = preg_replace('/\s+/', '', (string) ($config['username'] ?? ''));
        $mail->Password = preg_replace('/\s+/', '', (string) ($config['password'] ?? ''));

        $encryption = strtolower(trim((string) ($config['encryption'] ?? 'tls')));
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $mail->Port = (int) ($config['port'] ?? 587);

        // Sender + Receiver
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to);

        // Email content
        $body = $message;
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Send
        $GLOBALS['SEND_EMAIL_LAST_ERROR'] = '';

        return $mail->send();

    } catch (Exception $e) {
        $GLOBALS['SEND_EMAIL_LAST_ERROR'] = (string) $mail->ErrorInfo;
        return false;
    }
}
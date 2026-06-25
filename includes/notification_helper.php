<?php

function notify_log($line)
{
    $logFile = dirname(__DIR__) . '/logs/mail.log';
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL, FILE_APPEND);
}

function smtp_read_response($socket)
{
    $response = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }
        $response .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function smtp_expect($socket, $expectedCodes)
{
    $response = smtp_read_response($socket);
    $code = (int) substr($response, 0, 3);
    foreach ($expectedCodes as $ok) {
        if ($code === (int) $ok) {
            return [true, $response];
        }
    }
    return [false, $response];
}

function smtp_command($socket, $command, $expectedCodes)
{
    fwrite($socket, $command . "\r\n");
    return smtp_expect($socket, $expectedCodes);
}

function normalize_smtp_value($value)
{
    // Remove accidental spaces/newlines pasted into config values.
    return preg_replace('/\s+/', '', (string) $value);
}

function normalize_smtp_password($value)
{
    // App passwords are sometimes copied in groups like "abcd efgh ijkl mnop".
    // Remove all whitespace so auth uses the real token.
    $value = (string) $value;
    return preg_replace('/\s+/', '', $value);
}

function smtp_send_mail_message($config, $toEmail, $subject, $body)
{
    $host = trim((string) ($config['host'] ?? ''));
    $port = (int) ($config['port'] ?? 587);
    $encryption = strtolower(trim((string) ($config['encryption'] ?? 'tls')));
    $authEnabled = array_key_exists('auth', $config) ? (bool) $config['auth'] : true;
    $username = normalize_smtp_value($config['username'] ?? '');
    $password = normalize_smtp_password($config['password'] ?? '');
    $fromEmail = normalize_smtp_value($config['from_email'] ?? $username);
    $fromName = trim((string) ($config['from_name'] ?? 'Trusted Fundi'));
    $timeout = (int) ($config['timeout'] ?? 20);

    if ($host === '' || $port <= 0 || $fromEmail === '') {
        return [false, 'SMTP config incomplete'];
    }

    if ($authEnabled && ($username === '' || $password === '')) {
        return [false, 'SMTP auth enabled but username/password missing'];
    }

    $transportHost = ($encryption === 'ssl' ? 'ssl://' : '') . $host;
    $socket = @stream_socket_client($transportHost . ':' . $port, $errno, $errstr, $timeout);
    if (!$socket) {
        return [false, 'Connection failed: ' . $errstr . ' (' . $errno . ')'];
    }

    stream_set_timeout($socket, $timeout);

    list($ok, $resp) = smtp_expect($socket, [220]);
    if (!$ok) {
        fclose($socket);
        return [false, 'Greeting failed: ' . trim($resp)];
    }

    list($ok, $resp) = smtp_command($socket, 'EHLO trusted-fundi.local', [250]);
    if (!$ok) {
        fclose($socket);
        return [false, 'EHLO failed: ' . trim($resp)];
    }

    if ($encryption === 'tls') {
        list($ok, $resp) = smtp_command($socket, 'STARTTLS', [220]);
        if (!$ok) {
            fclose($socket);
            return [false, 'STARTTLS failed: ' . trim($resp)];
        }

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return [false, 'TLS negotiation failed'];
        }

        list($ok, $resp) = smtp_command($socket, 'EHLO trusted-fundi.local', [250]);
        if (!$ok) {
            fclose($socket);
            return [false, 'EHLO after TLS failed: ' . trim($resp)];
        }
    }

    if ($authEnabled) {
        list($ok, $resp) = smtp_command($socket, 'AUTH LOGIN', [334]);
        if (!$ok) {
            fclose($socket);
            return [false, 'AUTH LOGIN failed: ' . trim($resp)];
        }

        list($ok, $resp) = smtp_command($socket, base64_encode($username), [334]);
        if (!$ok) {
            fclose($socket);
            return [false, 'SMTP username rejected: ' . trim($resp)];
        }

        list($ok, $resp) = smtp_command($socket, base64_encode($password), [235]);
        if (!$ok) {
            fclose($socket);
            return [false, 'SMTP password rejected: ' . trim($resp)];
        }
    }

    list($ok, $resp) = smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
    if (!$ok) {
        fclose($socket);
        return [false, 'MAIL FROM failed: ' . trim($resp)];
    }

    list($ok, $resp) = smtp_command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
    if (!$ok) {
        fclose($socket);
        return [false, 'RCPT TO failed: ' . trim($resp)];
    }

    list($ok, $resp) = smtp_command($socket, 'DATA', [354]);
    if (!$ok) {
        fclose($socket);
        return [false, 'DATA failed: ' . trim($resp)];
    }

    $headers = [];
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'To: <' . $toEmail . '>';
    $headers[] = 'Subject: ' . $subject;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';

    $payload = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
    fwrite($socket, $payload . "\r\n");

    list($ok, $resp) = smtp_expect($socket, [250]);
    if (!$ok) {
        fclose($socket);
        return [false, 'Message send failed: ' . trim($resp)];
    }

    smtp_command($socket, 'QUIT', [221]);
    fclose($socket);
    return [true, 'sent'];
}

function php_mail_fallback_send($fromEmail, $fromName, $toEmail, $subject, $body)
{
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    if ($fromEmail !== '') {
        $headers .= "From: " . $fromName . " <" . $fromEmail . ">\r\n";
    }

    $sent = @mail($toEmail, $subject, $body, $headers);
    return [
        $sent,
        $sent ? 'php mail fallback sent' : 'php mail fallback failed',
    ];
}

/**
 * Save an in-app notification and send an email copy.
 * Returns an array with operation details.
 */
function send_notification_with_email($conn, $userId, $message, $subject = 'Trusted Fundi Notification')
{
    $result = [
        'db_saved' => false,
        'email_sent' => false,
        'email_target' => '',
        'email_error' => '',
    ];

    $userId = (int) $userId;
    $message = trim((string) $message);
    $subject = trim((string) $subject);

    if ($userId <= 0 || $message === '') {
        return $result;
    }

    $insertStmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    if ($insertStmt) {
        $insertStmt->bind_param("is", $userId, $message);
        $result['db_saved'] = (bool) $insertStmt->execute();
        $insertStmt->close();
    }

    $email = '';
    $userStmt = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
    if ($userStmt) {
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $row = $userResult ? $userResult->fetch_assoc() : null;
        if ($row) {
            $email = trim((string) ($row['email'] ?? ''));
        }
        $userStmt->close();
    }

    if ($email === '') {
        $result['email_error'] = 'No recipient email found';
        notify_log('Email skipped for user #' . $userId . ': no email');
        return $result;
    }

    $result['email_target'] = $email;

    $configFile = dirname(__DIR__) . '/config/mail.php';
    $mailConfig = file_exists($configFile) ? include $configFile : [];
    if (!is_array($mailConfig)) {
        $mailConfig = [];
    }

    $enabled = !empty($mailConfig['enabled']);
    if (!$enabled) {
        $result['email_error'] = 'SMTP disabled in config/mail.php';
        notify_log('Email skipped to ' . $email . ': SMTP disabled');
        return $result;
    }

    $authEnabled = array_key_exists('auth', $mailConfig) ? (bool) $mailConfig['auth'] : true;
    $configuredUser = normalize_smtp_value($mailConfig['username'] ?? '');
    $configuredPass = normalize_smtp_password($mailConfig['password'] ?? '');
    $fromEmail = trim((string) ($mailConfig['from_email'] ?? $configuredUser));
    $fromName = trim((string) ($mailConfig['from_name'] ?? 'Trusted Fundi'));

    $emailBody = "Hello,\n\n" . $message . "\n\n";
    $emailBody .= "This is an automated notification from Trusted Fundi.";

    if ($authEnabled && ($configuredUser === '' || $configuredPass === '' || $configuredUser === 'your_email@gmail.com' || $configuredPass === 'your_app_password')) {
        list($okFallback, $fallbackMessage) = php_mail_fallback_send(
            $fromEmail,
            $fromName,
            $email,
            ($subject !== '' ? $subject : 'Trusted Fundi Notification'),
            $emailBody
        );

        $result['email_sent'] = $okFallback;
        $result['email_error'] = $okFallback ? '' : 'SMTP credentials not configured and fallback failed';
        notify_log(
            ($okFallback ? 'Email sent to ' : 'Email failed to ') .
            $email .
            ': SMTP credentials missing/placeholders; ' .
            $fallbackMessage
        );
        return $result;
    }

    list($ok, $smtpMessage) = smtp_send_mail_message(
        $mailConfig,
        $email,
        ($subject !== '' ? $subject : 'Trusted Fundi Notification'),
        $emailBody
    );

    $result['email_sent'] = $ok;
    if (!$ok) {
        list($okFallback, $fallbackMessage) = php_mail_fallback_send(
            $fromEmail,
            $fromName,
            $email,
            ($subject !== '' ? $subject : 'Trusted Fundi Notification'),
            $emailBody
        );

        $result['email_sent'] = $okFallback;
        $result['email_error'] = $okFallback ? '' : ($smtpMessage . '; fallback failed');
        notify_log(
            ($okFallback ? 'Email sent to ' : 'Email failed to ') .
            $email .
            ': SMTP failed (' . $smtpMessage . '), smtp_user=' . $configuredUser . ', from=' . $fromEmail . ', ' .
            $fallbackMessage
        );
    } else {
        notify_log('Email sent to ' . $email . ' subject=' . ($subject !== '' ? $subject : 'Trusted Fundi Notification'));
    }

    return $result;
}

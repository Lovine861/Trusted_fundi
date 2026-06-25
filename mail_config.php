<?php

$mainConfigFile = __DIR__ . '/config/mail.php';
if (file_exists($mainConfigFile)) {
    $cfg = include $mainConfigFile;
    if (is_array($cfg)) {
        return [
            'host' => (string) ($cfg['host'] ?? 'smtp.gmail.com'),
            'port' => (int) ($cfg['port'] ?? 587),
            'encryption' => (string) ($cfg['encryption'] ?? 'tls'),
            'auth' => (bool) ($cfg['auth'] ?? true),
            'username' => (string) ($cfg['username'] ?? ''),
            'password' => (string) ($cfg['password'] ?? ''),
            'from_email' => (string) ($cfg['from_email'] ?? ''),
            'from_name' => (string) ($cfg['from_name'] ?? 'Trusted Fundi'),
        ];
    }
}

return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => '',
    'password' => '',
    'from_email' => '',
    'from_name' => 'Trusted Fundi',
];
<?php
/**
 * Copy this file to config.php and fill in real values.
 * config.php is git-ignored and must never be committed.
 */
return [
    'app' => [
        'name' => 'Afrisap SFMTP',
        'url' => 'http://localhost:8000',
        'env' => 'local', // local | production
        'debug' => true,
        'timezone' => 'Africa/Kigali',
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'sfmtp',
        'username' => 'sfmtp_app',
        'password' => 'sfmtp_dev_pw',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from_address' => 'no-reply@afrisap.test',
        'from_name' => 'Afrisap SFMTP',
    ],
    'session' => [
        'name' => 'sfmtp_session',
        'lifetime' => 7200, // seconds
    ],
    'uploads' => [
        'path' => __DIR__ . '/../../public/uploads',
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
    ],
];

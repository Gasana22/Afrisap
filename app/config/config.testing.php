<?php
/**
 * Config used only when APP_TESTING=1 (set by phpunit.xml). Points at a separate
 * database so the test suite never touches development or production data.
 * No secrets here beyond local dev/test-only defaults, safe to commit.
 */
return [
    'app' => [
        'name' => 'Afrisap SFMTP (testing)',
        'url' => 'http://localhost:8000',
        'env' => 'testing',
        'debug' => true,
        'timezone' => 'Africa/Kigali',
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'sfmtp_test',
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
        'name' => 'sfmtp_test_session',
        'lifetime' => 7200,
    ],
    'uploads' => [
        'path' => __DIR__ . '/../../storage/testing-uploads',
        'max_size' => 5 * 1024 * 1024,
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
    ],
];

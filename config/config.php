<?php
/**
 * Main application configuration.
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/constants.php';

return [
    'app' => [
        'name' => env('APP_NAME', 'Smart Farm Management & Traceability Platform'),
        'url' => rtrim((string) env('APP_URL', 'http://localhost:8080'), '/'),
        'env' => env('APP_ENV', 'local'),
        'debug' => (bool) env('APP_DEBUG', false),
        'timezone' => env('APP_TIMEZONE', 'Africa/Kigali'),
        'secret' => env('APP_SECRET', 'insecure-default-secret-change-me'),
    ],
    'mail' => [
        'host' => env('MAIL_HOST', ''),
        'port' => (int) env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'from_name' => env('MAIL_FROM_NAME', 'Smart Farm Platform'),
        'log_only' => (bool) env('MAIL_LOG_ONLY', true),
    ],
    'uploads' => [
        'max_size' => (int) env('MAX_UPLOAD_SIZE', 10485760),
        'allowed_types' => explode(',', (string) env('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,pdf,doc,docx')),
    ],
    'apis' => [
        'google_maps_key' => env('GOOGLE_MAPS_API_KEY', ''),
        'openweather_key' => env('OPENWEATHER_API_KEY', ''),
    ],
    'session' => [
        'timeout_minutes' => (int) env('SESSION_TIMEOUT_MINUTES', 120),
    ],
];

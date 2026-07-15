<?php
/**
 * Authentication configuration - session names & cookie params for the
 * three separate login contexts (platform admin, org-admin, worker).
 */

return [
    'session_name' => 'sfmtp_session',
    'password_min_length' => 8,
    'max_login_attempts' => 5,
    'lockout_minutes' => 15,
    'cookie' => [
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (($_SERVER['HTTPS'] ?? '') === 'on'),
    ],
];

<?php

namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        $config = require __DIR__ . '/../config/config.php';
        $session = $config['session'];

        session_name($session['name']);
        session_set_cookie_params([
            'lifetime' => $session['lifetime'],
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
        self::$started = true;
    }
}

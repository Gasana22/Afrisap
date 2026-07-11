<?php

namespace App\Core;

use App\Models\Notification;
use App\Models\User;

class Notifier
{
    public static function notify(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        Notification::create($userId, $title, $message, $type, $link);
    }

    public static function notifyWithEmail(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        self::notify($userId, $title, $message, $type, $link);

        $user = User::find($userId);
        if ($user) {
            Mailer::send($user['email'], $title, "<p>{$message}</p>");
        }
    }
}

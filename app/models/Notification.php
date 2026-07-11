<?php

namespace App\Models;

use App\Core\Database;

class Notification
{
    public static function forUser(int $userId, int $limit = 20): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM notifications WHERE user_id = :id ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :id AND is_read = 0');
        $stmt->execute(['id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function create(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, type, link) VALUES (:user_id, :title, :message, :type, :link)');
        $stmt->execute(['user_id' => $userId, 'title' => $title, 'message' => $message, 'type' => $type, 'link' => $link]);
        return (int) $pdo->lastInsertId();
    }

    public static function markRead(int $id, int $userId): void
    {
        Database::connection()->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id')
            ->execute(['id' => $id, 'user_id' => $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        Database::connection()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id')
            ->execute(['user_id' => $userId]);
    }
}

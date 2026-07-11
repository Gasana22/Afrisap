<?php

namespace App\Models;

use App\Core\Database;

class ActivityPhoto
{
    public static function forActivity(int $activityId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM activity_photos WHERE field_activity_id = :id ORDER BY id');
        $stmt->execute(['id' => $activityId]);
        return $stmt->fetchAll();
    }

    public static function create(int $activityId, string $filePath): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO activity_photos (field_activity_id, file_path) VALUES (:activity_id, :file_path)');
        $stmt->execute(['activity_id' => $activityId, 'file_path' => $filePath]);
        return (int) $pdo->lastInsertId();
    }
}

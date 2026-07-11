<?php

namespace App\Models;

use App\Core\Database;

class CropType
{
    public static function all(): array
    {
        return Database::connection()->query('SELECT * FROM crop_types ORDER BY name')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM crop_types WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO crop_types (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM crop_types WHERE id = :id')->execute(['id' => $id]);
    }
}

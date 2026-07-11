<?php

namespace App\Models;

use App\Core\Database;

class Block
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM blocks WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $farmId, string $name, ?string $description): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO blocks (farm_id, name, description) VALUES (:farm_id, :name, :description)');
        $stmt->execute(['farm_id' => $farmId, 'name' => $name, 'description' => $description ?: null]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM blocks WHERE id = :id')->execute(['id' => $id]);
    }

    public static function plots(int $blockId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM plots WHERE block_id = :id ORDER BY plot_code');
        $stmt->execute(['id' => $blockId]);
        return $stmt->fetchAll();
    }
}

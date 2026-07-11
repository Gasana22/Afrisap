<?php

namespace App\Models;

use App\Core\Database;

class Season
{
    public static function all(): array
    {
        return Database::connection()->query('SELECT * FROM seasons ORDER BY start_date DESC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM seasons WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO seasons (name, start_date, end_date) VALUES (:name, :start_date, :end_date)');
        $stmt->execute([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM seasons WHERE id = :id')->execute(['id' => $id]);
    }
}

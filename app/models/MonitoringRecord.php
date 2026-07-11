<?php

namespace App\Models;

use App\Core\Database;

class MonitoringRecord
{
    public static function forCycle(int $cropCycleId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM monitoring_records WHERE crop_cycle_id = :id ORDER BY record_date DESC, id DESC');
        $stmt->execute(['id' => $cropCycleId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM monitoring_records WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $cropCycleId, array $data, ?string $photoPath): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO monitoring_records (crop_cycle_id, type, record_date, description, severity, photo_path)
            VALUES (:crop_cycle_id, :type, :record_date, :description, :severity, :photo_path)');
        $stmt->execute([
            'crop_cycle_id' => $cropCycleId,
            'type' => $data['type'],
            'record_date' => $data['record_date'],
            'description' => $data['description'] ?: null,
            'severity' => $data['severity'] ?: null,
            'photo_path' => $photoPath,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM monitoring_records WHERE id = :id')->execute(['id' => $id]);
    }
}

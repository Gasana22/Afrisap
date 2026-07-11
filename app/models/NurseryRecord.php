<?php

namespace App\Models;

use App\Core\Database;

class NurseryRecord
{
    public static function forCycle(int $cropCycleId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM nursery_records WHERE crop_cycle_id = :id ORDER BY record_date DESC, id DESC');
        $stmt->execute(['id' => $cropCycleId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM nursery_records WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $cropCycleId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO nursery_records (crop_cycle_id, record_date, germination_rate, treatment, survival_rate, notes)
            VALUES (:crop_cycle_id, :record_date, :germination_rate, :treatment, :survival_rate, :notes)');
        $stmt->execute([
            'crop_cycle_id' => $cropCycleId,
            'record_date' => $data['record_date'],
            'germination_rate' => $data['germination_rate'] !== '' ? $data['germination_rate'] : null,
            'treatment' => $data['treatment'] ?: null,
            'survival_rate' => $data['survival_rate'] !== '' ? $data['survival_rate'] : null,
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM nursery_records WHERE id = :id')->execute(['id' => $id]);
    }
}

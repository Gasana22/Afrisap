<?php

namespace App\Models;

use App\Core\Database;

class Harvest
{
    public static function forCycle(int $cropCycleId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM harvests WHERE crop_cycle_id = :id ORDER BY harvest_date DESC, id DESC');
        $stmt->execute(['id' => $cropCycleId]);
        $harvests = $stmt->fetchAll();
        foreach ($harvests as &$harvest) {
            $harvest['sales'] = CropSale::forHarvest((int) $harvest['id']);
        }
        return $harvests;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM harvests WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $cropCycleId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO harvests (crop_cycle_id, harvest_date, quantity, unit, quality_grade, notes)
            VALUES (:crop_cycle_id, :harvest_date, :quantity, :unit, :quality_grade, :notes)');
        $stmt->execute([
            'crop_cycle_id' => $cropCycleId,
            'harvest_date' => $data['harvest_date'],
            'quantity' => $data['quantity'],
            'unit' => $data['unit'] ?: null,
            'quality_grade' => $data['quality_grade'] ?: null,
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM harvests WHERE id = :id')->execute(['id' => $id]);
    }
}

<?php

namespace App\Models;

use App\Core\Database;

class YieldForecast
{
    public static function forCycle(int $cropCycleId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM yield_forecasts WHERE crop_cycle_id = :id ORDER BY forecast_date DESC, id DESC');
        $stmt->execute(['id' => $cropCycleId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM yield_forecasts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $cropCycleId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO yield_forecasts (crop_cycle_id, forecast_date, estimated_yield, notes)
            VALUES (:crop_cycle_id, :forecast_date, :estimated_yield, :notes)');
        $stmt->execute([
            'crop_cycle_id' => $cropCycleId,
            'forecast_date' => $data['forecast_date'],
            'estimated_yield' => $data['estimated_yield'],
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM yield_forecasts WHERE id = :id')->execute(['id' => $id]);
    }
}

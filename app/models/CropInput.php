<?php

namespace App\Models;

use App\Core\Database;

class CropInput
{
    public static function forCycle(int $cropCycleId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM crop_inputs WHERE crop_cycle_id = :id ORDER BY purchase_date DESC, id DESC');
        $stmt->execute(['id' => $cropCycleId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM crop_inputs WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $cropCycleId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO crop_inputs (crop_cycle_id, input_type, supplier_name, quantity, unit, cost, purchase_date, expiry_date)
            VALUES (:crop_cycle_id, :input_type, :supplier_name, :quantity, :unit, :cost, :purchase_date, :expiry_date)');
        $stmt->execute([
            'crop_cycle_id' => $cropCycleId,
            'input_type' => $data['input_type'],
            'supplier_name' => $data['supplier_name'] ?: null,
            'quantity' => $data['quantity'] !== '' ? $data['quantity'] : null,
            'unit' => $data['unit'] ?: null,
            'cost' => $data['cost'] !== '' ? $data['cost'] : null,
            'purchase_date' => $data['purchase_date'] ?: null,
            'expiry_date' => $data['expiry_date'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM crop_inputs WHERE id = :id')->execute(['id' => $id]);
    }
}

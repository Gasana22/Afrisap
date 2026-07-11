<?php

namespace App\Models;

use App\Core\Database;

class CropSale
{
    public static function forHarvest(int $harvestId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM crop_sales WHERE harvest_id = :id ORDER BY sale_date DESC, id DESC');
        $stmt->execute(['id' => $harvestId]);
        return $stmt->fetchAll();
    }

    public static function create(int $harvestId, array $data): int
    {
        $pdo = Database::connection();
        $revenue = (float) $data['quantity'] * (float) $data['unit_price'];
        $stmt = $pdo->prepare('INSERT INTO crop_sales (harvest_id, buyer_name, quantity, unit_price, revenue, sale_date, notes)
            VALUES (:harvest_id, :buyer_name, :quantity, :unit_price, :revenue, :sale_date, :notes)');
        $stmt->execute([
            'harvest_id' => $harvestId,
            'buyer_name' => $data['buyer_name'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'revenue' => $revenue,
            'sale_date' => $data['sale_date'],
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM crop_sales WHERE id = :id')->execute(['id' => $id]);
    }
}

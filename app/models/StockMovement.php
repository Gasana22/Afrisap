<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

class StockMovement
{
    public static function stockByFarm(int $itemId): array
    {
        $stmt = Database::connection()->prepare('SELECT s.*, f.name AS farm_name FROM inventory_stock s
            JOIN farms f ON f.id = s.farm_id WHERE s.item_id = :id AND s.quantity_on_hand > 0 ORDER BY f.name');
        $stmt->execute(['id' => $itemId]);
        return $stmt->fetchAll();
    }

    public static function currentStock(int $itemId, int $farmId): float
    {
        $stmt = Database::connection()->prepare('SELECT quantity_on_hand FROM inventory_stock WHERE item_id = :item_id AND farm_id = :farm_id');
        $stmt->execute(['item_id' => $itemId, 'farm_id' => $farmId]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (float) $value : 0.0;
    }

    public static function forItem(int $itemId): array
    {
        $stmt = Database::connection()->prepare('SELECT sm.*, f.name AS farm_name, rf.name AS related_farm_name, u.name AS performed_by_name
            FROM stock_movements sm
            JOIN farms f ON f.id = sm.farm_id
            LEFT JOIN farms rf ON rf.id = sm.related_farm_id
            JOIN users u ON u.id = sm.performed_by
            WHERE sm.item_id = :id ORDER BY sm.movement_date DESC, sm.id DESC');
        $stmt->execute(['id' => $itemId]);
        return $stmt->fetchAll();
    }

    public static function recordIn(int $itemId, int $farmId, float $quantity, array $data, int $performedBy): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        self::adjustStock($itemId, $farmId, $quantity);
        self::insertMovement($itemId, $farmId, 'in', $quantity, null, $data, $performedBy);

        $pdo->commit();
    }

    public static function recordOut(int $itemId, int $farmId, float $quantity, array $data, int $performedBy): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        if (self::currentStock($itemId, $farmId) < $quantity) {
            $pdo->rollBack();
            throw new RuntimeException('Insufficient stock at this farm for this quantity.');
        }

        self::adjustStock($itemId, $farmId, -$quantity);
        self::insertMovement($itemId, $farmId, 'out', $quantity, null, $data, $performedBy);

        $pdo->commit();
    }

    public static function recordTransfer(int $itemId, int $fromFarmId, int $toFarmId, float $quantity, array $data, int $performedBy): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        if (self::currentStock($itemId, $fromFarmId) < $quantity) {
            $pdo->rollBack();
            throw new RuntimeException('Insufficient stock at the source farm for this quantity.');
        }

        self::adjustStock($itemId, $fromFarmId, -$quantity);
        self::adjustStock($itemId, $toFarmId, $quantity);
        self::insertMovement($itemId, $fromFarmId, 'transfer_out', $quantity, $toFarmId, $data, $performedBy);
        self::insertMovement($itemId, $toFarmId, 'transfer_in', $quantity, $fromFarmId, $data, $performedBy);

        $pdo->commit();
    }

    private static function adjustStock(int $itemId, int $farmId, float $delta): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO inventory_stock (item_id, farm_id, quantity_on_hand) VALUES (:item_id, :farm_id, :qty)
            ON DUPLICATE KEY UPDATE quantity_on_hand = quantity_on_hand + :qty2');
        $stmt->execute(['item_id' => $itemId, 'farm_id' => $farmId, 'qty' => $delta, 'qty2' => $delta]);
    }

    private static function insertMovement(int $itemId, int $farmId, string $type, float $quantity, ?int $relatedFarmId, array $data, int $performedBy): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO stock_movements (item_id, farm_id, type, quantity, related_farm_id, reference, performed_by, movement_date, notes)
            VALUES (:item_id, :farm_id, :type, :quantity, :related_farm_id, :reference, :performed_by, :movement_date, :notes)');
        $stmt->execute([
            'item_id' => $itemId,
            'farm_id' => $farmId,
            'type' => $type,
            'quantity' => $quantity,
            'related_farm_id' => $relatedFarmId,
            'reference' => $data['reference'] ?: null,
            'performed_by' => $performedBy,
            'movement_date' => $data['movement_date'],
            'notes' => $data['notes'] ?: null,
        ]);
    }
}

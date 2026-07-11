<?php

namespace App\Models;

use App\Core\Database;

class Delivery
{
    public static function forPurchaseOrder(int $poId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM deliveries WHERE purchase_order_id = :id ORDER BY delivery_date DESC, id DESC');
        $stmt->execute(['id' => $poId]);
        return $stmt->fetchAll();
    }

    public static function create(int $poId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO deliveries (purchase_order_id, delivery_date, received_by, condition_notes)
            VALUES (:po_id, :delivery_date, :received_by, :condition_notes)');
        $stmt->execute([
            'po_id' => $poId,
            'delivery_date' => $data['delivery_date'],
            'received_by' => $data['received_by'] ?: null,
            'condition_notes' => $data['condition_notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

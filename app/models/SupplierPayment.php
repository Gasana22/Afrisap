<?php

namespace App\Models;

use App\Core\Database;

class SupplierPayment
{
    public static function forPurchaseOrder(int $poId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM supplier_payments WHERE purchase_order_id = :id ORDER BY payment_date DESC, id DESC');
        $stmt->execute(['id' => $poId]);
        return $stmt->fetchAll();
    }

    public static function create(int $poId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO supplier_payments (purchase_order_id, amount, payment_date, method, notes)
            VALUES (:po_id, :amount, :payment_date, :method, :notes)');
        $stmt->execute([
            'po_id' => $poId,
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'method' => $data['method'],
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

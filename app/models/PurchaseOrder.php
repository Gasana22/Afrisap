<?php

namespace App\Models;

use App\Core\Database;

class PurchaseOrder
{
    private const SELECT_BASE = "SELECT po.*, s.name AS supplier_name, f.name AS farm_name, u.name AS created_by_name,
        (SELECT COALESCE(SUM(quantity * unit_cost), 0) FROM purchase_order_items WHERE purchase_order_id = po.id) AS total_cost,
        (SELECT COALESCE(SUM(amount), 0) FROM supplier_payments WHERE purchase_order_id = po.id) AS total_paid
        FROM purchase_orders po
        JOIN suppliers s ON s.id = po.supplier_id
        JOIN farms f ON f.id = po.farm_id
        JOIN users u ON u.id = po.created_by";

    public static function all(): array
    {
        return Database::connection()->query(self::SELECT_BASE . ' ORDER BY po.order_date DESC, po.id DESC')->fetchAll();
    }

    public static function paginated(int $page, int $perPage, int $organizationId): array
    {
        $pdo = Database::connection();

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM purchase_orders po JOIN farms f ON f.id = po.farm_id WHERE f.organization_id = :org_id');
        $countStmt->execute(['org_id' => $organizationId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(self::SELECT_BASE . ' WHERE f.organization_id = :org_id ORDER BY po.order_date DESC, po.id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':org_id', $organizationId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'totalPages' => (int) ceil($total / $perPage)];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE po.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** The tenant-isolation check: fetching another organization's purchase order by id returns null. */
    public static function findInOrganization(int $id, int $organizationId): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE po.id = :id AND f.organization_id = :org_id');
        $stmt->execute(['id' => $id, 'org_id' => $organizationId]);
        return $stmt->fetch() ?: null;
    }

    public static function items(int $poId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM purchase_order_items WHERE purchase_order_id = :id ORDER BY id');
        $stmt->execute(['id' => $poId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data, array $items, int $createdBy): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('INSERT INTO purchase_orders (supplier_id, farm_id, order_date, expected_date, status, notes, created_by)
            VALUES (:supplier_id, :farm_id, :order_date, :expected_date, :status, :notes, :created_by)');
        $stmt->execute([
            'supplier_id' => $data['supplier_id'],
            'farm_id' => $data['farm_id'],
            'order_date' => $data['order_date'],
            'expected_date' => $data['expected_date'] ?: null,
            'status' => 'draft',
            'notes' => $data['notes'] ?: null,
            'created_by' => $createdBy,
        ]);
        $poId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO purchase_order_items (purchase_order_id, item_name, quantity, unit, unit_cost)
            VALUES (:po_id, :item_name, :quantity, :unit, :unit_cost)');
        foreach ($items as $item) {
            if (trim((string) $item['item_name']) === '') {
                continue;
            }
            $itemStmt->execute([
                'po_id' => $poId,
                'item_name' => $item['item_name'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'] ?: null,
                'unit_cost' => $item['unit_cost'],
            ]);
        }

        $pdo->commit();
        return $poId;
    }

    public static function updateStatus(int $id, string $status): void
    {
        Database::connection()->prepare('UPDATE purchase_orders SET status = :status WHERE id = :id')
            ->execute(['id' => $id, 'status' => $status]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM purchase_orders WHERE id = :id')->execute(['id' => $id]);
    }
}

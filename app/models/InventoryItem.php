<?php

namespace App\Models;

use App\Core\Database;

class InventoryItem
{
    public static function all(): array
    {
        return Database::connection()->query("SELECT i.*,
            (SELECT COALESCE(SUM(quantity_on_hand), 0) FROM inventory_stock WHERE item_id = i.id) AS total_stock
            FROM inventory_items i ORDER BY i.name")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare("SELECT i.*,
            (SELECT COALESCE(SUM(quantity_on_hand), 0) FROM inventory_stock WHERE item_id = i.id) AS total_stock
            FROM inventory_items i WHERE i.id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function forOrganization(int $organizationId): array
    {
        $stmt = Database::connection()->prepare("SELECT i.*,
            (SELECT COALESCE(SUM(quantity_on_hand), 0) FROM inventory_stock WHERE item_id = i.id) AS total_stock
            FROM inventory_items i WHERE i.organization_id = :org_id ORDER BY i.name");
        $stmt->execute(['org_id' => $organizationId]);
        return $stmt->fetchAll();
    }

    /** The tenant-isolation check: fetching another organization's item by id returns null. */
    public static function findInOrganization(int $id, int $organizationId): ?array
    {
        $stmt = Database::connection()->prepare("SELECT i.*,
            (SELECT COALESCE(SUM(quantity_on_hand), 0) FROM inventory_stock WHERE item_id = i.id) AS total_stock
            FROM inventory_items i WHERE i.id = :id AND i.organization_id = :org_id");
        $stmt->execute(['id' => $id, 'org_id' => $organizationId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO inventory_items (organization_id, name, category, unit, reorder_level)
            VALUES (:organization_id, :name, :category, :unit, :reorder_level)');
        $stmt->execute([
            'organization_id' => $data['organization_id'],
            'name' => $data['name'],
            'category' => $data['category'],
            'unit' => $data['unit'] ?: null,
            'reorder_level' => $data['reorder_level'] !== '' ? $data['reorder_level'] : null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE inventory_items SET name = :name, category = :category,
            unit = :unit, reorder_level = :reorder_level WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'category' => $data['category'],
            'unit' => $data['unit'] ?: null,
            'reorder_level' => $data['reorder_level'] !== '' ? $data['reorder_level'] : null,
        ]);
    }

    public static function hasMovements(int $id): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM stock_movements WHERE item_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM inventory_items WHERE id = :id')->execute(['id' => $id]);
    }
}

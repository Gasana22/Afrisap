<?php

namespace App\Models;

use App\Core\Database;

class Asset
{
    private const SELECT_BASE = "SELECT a.*, f.name AS farm_name,
        (SELECT MIN(next_due_date) FROM asset_maintenance WHERE asset_id = a.id AND next_due_date >= CURDATE()) AS next_maintenance_due
        FROM assets a JOIN farms f ON f.id = a.farm_id";

    public static function all(): array
    {
        return Database::connection()->query(self::SELECT_BASE . ' ORDER BY a.created_at DESC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE a.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO assets (farm_id, type, name, identifier, purchase_date, purchase_value, status, notes)
            VALUES (:farm_id, :type, :name, :identifier, :purchase_date, :purchase_value, :status, :notes)');
        $stmt->execute([
            'farm_id' => $data['farm_id'],
            'type' => $data['type'],
            'name' => $data['name'],
            'identifier' => $data['identifier'] ?: null,
            'purchase_date' => $data['purchase_date'] ?: null,
            'purchase_value' => $data['purchase_value'] !== '' ? $data['purchase_value'] : null,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE assets SET type = :type, name = :name, identifier = :identifier,
            purchase_date = :purchase_date, purchase_value = :purchase_value, status = :status, notes = :notes WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'type' => $data['type'],
            'name' => $data['name'],
            'identifier' => $data['identifier'] ?: null,
            'purchase_date' => $data['purchase_date'] ?: null,
            'purchase_value' => $data['purchase_value'] !== '' ? $data['purchase_value'] : null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?: null,
        ]);
    }

    public static function hasMaintenance(int $id): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM asset_maintenance WHERE asset_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM assets WHERE id = :id')->execute(['id' => $id]);
    }
}

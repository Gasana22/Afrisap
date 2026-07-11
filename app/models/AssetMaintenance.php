<?php

namespace App\Models;

use App\Core\Database;

class AssetMaintenance
{
    public static function forAsset(int $assetId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM asset_maintenance WHERE asset_id = :id ORDER BY maintenance_date DESC, id DESC');
        $stmt->execute(['id' => $assetId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM asset_maintenance WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $assetId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO asset_maintenance (asset_id, maintenance_date, description, cost, next_due_date, performed_by)
            VALUES (:asset_id, :maintenance_date, :description, :cost, :next_due_date, :performed_by)');
        $stmt->execute([
            'asset_id' => $assetId,
            'maintenance_date' => $data['maintenance_date'],
            'description' => $data['description'],
            'cost' => $data['cost'] !== '' ? $data['cost'] : null,
            'next_due_date' => $data['next_due_date'] ?: null,
            'performed_by' => $data['performed_by'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

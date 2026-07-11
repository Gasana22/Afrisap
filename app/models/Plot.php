<?php

namespace App\Models;

use App\Core\Database;

class Plot
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM plots WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $blockId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO plots (block_id, plot_code, size_hectares, gps_lat, gps_lng, current_crop_type)
            VALUES (:block_id, :plot_code, :size_hectares, :gps_lat, :gps_lng, :current_crop_type)');
        $stmt->execute([
            'block_id' => $blockId,
            'plot_code' => $data['plot_code'],
            'size_hectares' => $data['size_hectares'] !== '' ? $data['size_hectares'] : null,
            'gps_lat' => $data['gps_lat'] !== '' ? $data['gps_lat'] : null,
            'gps_lng' => $data['gps_lng'] !== '' ? $data['gps_lng'] : null,
            'current_crop_type' => $data['current_crop_type'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM plots WHERE id = :id')->execute(['id' => $id]);
    }

    public static function allWithContext(): array
    {
        return Database::connection()->query('SELECT p.*, b.name AS block_name, f.id AS farm_id, f.name AS farm_name, f.code AS farm_code
            FROM plots p JOIN blocks b ON b.id = p.block_id JOIN farms f ON f.id = b.farm_id
            ORDER BY f.name, b.name, p.plot_code')->fetchAll();
    }

    public static function forOrganization(int $organizationId): array
    {
        $stmt = Database::connection()->prepare('SELECT p.*, b.name AS block_name, f.id AS farm_id, f.name AS farm_name, f.code AS farm_code
            FROM plots p JOIN blocks b ON b.id = p.block_id JOIN farms f ON f.id = b.farm_id
            WHERE f.organization_id = :org_id ORDER BY f.name, b.name, p.plot_code');
        $stmt->execute(['org_id' => $organizationId]);
        return $stmt->fetchAll();
    }

    public static function findWithContext(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT p.*, b.name AS block_name, f.id AS farm_id, f.name AS farm_name, f.code AS farm_code
            FROM plots p JOIN blocks b ON b.id = p.block_id JOIN farms f ON f.id = b.farm_id WHERE p.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}

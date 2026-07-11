<?php

namespace App\Models;

use App\Core\Database;

class Farm
{
    public static function all(): array
    {
        return Database::connection()->query('SELECT f.*, u.name AS owner_name FROM farms f
            LEFT JOIN users u ON u.id = f.owner_id ORDER BY f.created_at DESC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT f.*, u.name AS owner_name FROM farms f
            LEFT JOIN users u ON u.id = f.owner_id WHERE f.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO farms (name, size_hectares, gps_lat, gps_lng, district, village, owner_id, status)
            VALUES (:name, :size_hectares, :gps_lat, :gps_lng, :district, :village, :owner_id, :status)');
        $stmt->execute([
            'name' => $data['name'],
            'size_hectares' => $data['size_hectares'] !== '' ? $data['size_hectares'] : null,
            'gps_lat' => $data['gps_lat'] !== '' ? $data['gps_lat'] : null,
            'gps_lng' => $data['gps_lng'] !== '' ? $data['gps_lng'] : null,
            'district' => $data['district'] ?: null,
            'village' => $data['village'] ?: null,
            'owner_id' => $data['owner_id'] ?: null,
            'status' => $data['status'] ?? 'active',
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE farms SET name = :name, size_hectares = :size_hectares,
            gps_lat = :gps_lat, gps_lng = :gps_lng, district = :district, village = :village,
            owner_id = :owner_id, status = :status WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'size_hectares' => $data['size_hectares'] !== '' ? $data['size_hectares'] : null,
            'gps_lat' => $data['gps_lat'] !== '' ? $data['gps_lat'] : null,
            'gps_lng' => $data['gps_lng'] !== '' ? $data['gps_lng'] : null,
            'district' => $data['district'] ?: null,
            'village' => $data['village'] ?: null,
            'owner_id' => $data['owner_id'] ?: null,
            'status' => $data['status'],
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM farms WHERE id = :id')->execute(['id' => $id]);
    }

    public static function blocks(int $farmId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM blocks WHERE farm_id = :id ORDER BY name');
        $stmt->execute(['id' => $farmId]);
        return $stmt->fetchAll();
    }
}

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
}

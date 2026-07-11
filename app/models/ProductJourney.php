<?php

namespace App\Models;

use App\Core\Database;

class ProductJourney
{
    public static function forBatch(int $traceBatchId): array
    {
        $stmt = Database::connection()->prepare('SELECT pj.*, u.name AS responsible_user_name FROM product_journey pj
            JOIN users u ON u.id = pj.responsible_user_id WHERE pj.trace_batch_id = :id ORDER BY pj.stage_date DESC, pj.id DESC');
        $stmt->execute(['id' => $traceBatchId]);
        return $stmt->fetchAll();
    }

    public static function create(int $traceBatchId, array $data, int $responsibleUserId): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO product_journey (trace_batch_id, stage, stage_date, location, responsible_user_id, notes)
            VALUES (:batch_id, :stage, :stage_date, :location, :responsible_user_id, :notes)');
        $stmt->execute([
            'batch_id' => $traceBatchId,
            'stage' => $data['stage'],
            'stage_date' => $data['stage_date'],
            'location' => $data['location'] ?: null,
            'responsible_user_id' => $responsibleUserId,
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

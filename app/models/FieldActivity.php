<?php

namespace App\Models;

use App\Core\Database;

class FieldActivity
{
    public static function forCycle(int $cropCycleId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM field_activities WHERE crop_cycle_id = :id ORDER BY activity_date DESC, id DESC');
        $stmt->execute(['id' => $cropCycleId]);
        $activities = $stmt->fetchAll();
        foreach ($activities as &$activity) {
            $activity['photos'] = ActivityPhoto::forActivity((int) $activity['id']);
        }
        return $activities;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM field_activities WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $cropCycleId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO field_activities (crop_cycle_id, activity_type, activity_date, worker_name, gps_lat, gps_lng, cost, notes, status)
            VALUES (:crop_cycle_id, :activity_type, :activity_date, :worker_name, :gps_lat, :gps_lng, :cost, :notes, :status)');
        $stmt->execute([
            'crop_cycle_id' => $cropCycleId,
            'activity_type' => $data['activity_type'],
            'activity_date' => $data['activity_date'],
            'worker_name' => $data['worker_name'] ?: null,
            'gps_lat' => $data['gps_lat'] !== '' ? $data['gps_lat'] : null,
            'gps_lng' => $data['gps_lng'] !== '' ? $data['gps_lng'] : null,
            'cost' => $data['cost'] !== '' ? $data['cost'] : null,
            'notes' => $data['notes'] ?: null,
            'status' => $data['status'] ?? 'pending',
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateStatus(int $id, string $status): void
    {
        Database::connection()->prepare('UPDATE field_activities SET status = :status WHERE id = :id')
            ->execute(['id' => $id, 'status' => $status]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM field_activities WHERE id = :id')->execute(['id' => $id]);
    }
}

<?php

namespace App\Models;

use App\Core\Database;

class WorkerTask
{
    private const SELECT_BASE = "SELECT wt.*, assigner.name AS assigned_by_name, verifier.name AS verified_by_name
        FROM worker_tasks wt
        JOIN users assigner ON assigner.id = wt.assigned_by
        LEFT JOIN users verifier ON verifier.id = wt.verified_by";

    public static function forWorker(int $workerId): array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE wt.worker_id = :id ORDER BY wt.created_at DESC');
        $stmt->execute(['id' => $workerId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE wt.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $workerId, int $assignedBy, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO worker_tasks (worker_id, assigned_by, title, description, due_date, status)
            VALUES (:worker_id, :assigned_by, :title, :description, :due_date, :status)');
        $stmt->execute([
            'worker_id' => $workerId,
            'assigned_by' => $assignedBy,
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'due_date' => $data['due_date'] ?: null,
            'status' => 'pending',
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateStatus(int $id, string $status, array $data, ?string $photoPath): void
    {
        $stmt = Database::connection()->prepare('UPDATE worker_tasks SET status = :status,
            gps_lat = COALESCE(:gps_lat, gps_lat), gps_lng = COALESCE(:gps_lng, gps_lng),
            photo_path = COALESCE(:photo_path, photo_path) WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'gps_lat' => $data['gps_lat'] !== '' ? $data['gps_lat'] : null,
            'gps_lng' => $data['gps_lng'] !== '' ? $data['gps_lng'] : null,
            'photo_path' => $photoPath,
        ]);
    }

    public static function verify(int $id, int $verifierUserId): void
    {
        Database::connection()->prepare("UPDATE worker_tasks SET status = 'verified', verified_by = :verified_by, verified_at = NOW() WHERE id = :id")
            ->execute(['id' => $id, 'verified_by' => $verifierUserId]);
    }
}

<?php

namespace App\Models;

use App\Core\Database;

class WorkerAttendance
{
    private const SELECT_BASE = "SELECT wa.*, u.name AS approved_by_name FROM worker_attendance wa
        LEFT JOIN users u ON u.id = wa.approved_by";

    public static function forWorker(int $workerId): array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE wa.worker_id = :id ORDER BY wa.attendance_date DESC, wa.id DESC');
        $stmt->execute(['id' => $workerId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE wa.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function existsForDate(int $workerId, string $date): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM worker_attendance WHERE worker_id = :worker_id AND attendance_date = :date');
        $stmt->execute(['worker_id' => $workerId, 'date' => $date]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(int $workerId, array $data, ?string $photoPath): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO worker_attendance (worker_id, attendance_date, status, check_in_time, check_in_gps_lat, check_in_gps_lng, check_in_photo, check_out_time, notes)
            VALUES (:worker_id, :attendance_date, :status, :check_in_time, :check_in_gps_lat, :check_in_gps_lng, :check_in_photo, :check_out_time, :notes)');
        $stmt->execute([
            'worker_id' => $workerId,
            'attendance_date' => $data['attendance_date'],
            'status' => $data['status'],
            'check_in_time' => $data['check_in_time'] ?: null,
            'check_in_gps_lat' => $data['gps_lat'] !== '' ? $data['gps_lat'] : null,
            'check_in_gps_lng' => $data['gps_lng'] !== '' ? $data['gps_lng'] : null,
            'check_in_photo' => $photoPath,
            'check_out_time' => $data['check_out_time'] ?: null,
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function approve(int $id, int $approverUserId): void
    {
        Database::connection()->prepare('UPDATE worker_attendance SET approved_by = :approved_by, approved_at = NOW() WHERE id = :id')
            ->execute(['id' => $id, 'approved_by' => $approverUserId]);
    }
}

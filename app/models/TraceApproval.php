<?php

namespace App\Models;

use App\Core\Database;

class TraceApproval
{
    public static function forBatch(int $traceBatchId): array
    {
        $stmt = Database::connection()->prepare('SELECT ta.*, u.name AS approved_by_name FROM trace_approvals ta
            LEFT JOIN users u ON u.id = ta.approved_by WHERE ta.trace_batch_id = :id ORDER BY ta.created_at DESC');
        $stmt->execute(['id' => $traceBatchId]);
        return $stmt->fetchAll();
    }

    public static function approvedTypes(int $traceBatchId): array
    {
        $stmt = Database::connection()->prepare("SELECT approval_type FROM trace_approvals WHERE trace_batch_id = :id AND status = 'approved'");
        $stmt->execute(['id' => $traceBatchId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM trace_approvals WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $traceBatchId, string $approvalType, ?string $notes): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("INSERT INTO trace_approvals (trace_batch_id, approval_type, status, notes) VALUES (:batch_id, :type, 'pending', :notes)");
        $stmt->execute(['batch_id' => $traceBatchId, 'type' => $approvalType, 'notes' => $notes ?: null]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateStatus(int $id, string $status, int $approvedBy): void
    {
        Database::connection()->prepare('UPDATE trace_approvals SET status = :status, approved_by = :approved_by, approved_at = NOW() WHERE id = :id')
            ->execute(['id' => $id, 'status' => $status, 'approved_by' => $approvedBy]);
    }
}

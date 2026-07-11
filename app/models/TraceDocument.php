<?php

namespace App\Models;

use App\Core\Database;

class TraceDocument
{
    public static function forBatch(int $traceBatchId): array
    {
        $stmt = Database::connection()->prepare('SELECT d.*, u.name AS uploaded_by_name FROM trace_documents d
            JOIN users u ON u.id = d.uploaded_by WHERE d.trace_batch_id = :id ORDER BY d.uploaded_at DESC');
        $stmt->execute(['id' => $traceBatchId]);
        return $stmt->fetchAll();
    }

    public static function approvedCertificates(int $traceBatchId): array
    {
        $stmt = Database::connection()->prepare("SELECT * FROM trace_documents WHERE trace_batch_id = :id AND document_type = 'certificate' ORDER BY uploaded_at DESC");
        $stmt->execute(['id' => $traceBatchId]);
        return $stmt->fetchAll();
    }

    public static function create(int $traceBatchId, string $documentType, string $filePath, int $uploadedBy, ?string $notes): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO trace_documents (trace_batch_id, document_type, file_path, uploaded_by, notes)
            VALUES (:batch_id, :type, :path, :uploaded_by, :notes)');
        $stmt->execute([
            'batch_id' => $traceBatchId,
            'type' => $documentType,
            'path' => $filePath,
            'uploaded_by' => $uploadedBy,
            'notes' => $notes ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

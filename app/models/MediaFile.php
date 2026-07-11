<?php

namespace App\Models;

use App\Core\Database;

class MediaFile
{
    public static function all(?string $category = null, ?int $farmId = null): array
    {
        $sql = 'SELECT m.*, f.name AS farm_name, u.name AS uploaded_by_name FROM media_files m
            LEFT JOIN farms f ON f.id = m.farm_id
            JOIN users u ON u.id = m.uploaded_by';
        $conditions = [];
        $params = [];
        if ($category) {
            $conditions[] = 'm.category = :category';
            $params['category'] = $category;
        }
        if ($farmId) {
            $conditions[] = 'm.farm_id = :farm_id';
            $params['farm_id'] = $farmId;
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY m.uploaded_at DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM media_files WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data, string $filePath, int $uploadedBy): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO media_files (farm_id, category, title, file_path, uploaded_by, notes)
            VALUES (:farm_id, :category, :title, :file_path, :uploaded_by, :notes)');
        $stmt->execute([
            'farm_id' => $data['farm_id'] ?: null,
            'category' => $data['category'],
            'title' => $data['title'],
            'file_path' => $filePath,
            'uploaded_by' => $uploadedBy,
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM media_files WHERE id = :id')->execute(['id' => $id]);
    }
}

<?php

namespace App\Models;

use App\Core\Database;

class MediaFile
{
    public static function all(?string $category, ?int $farmId, int $organizationId): array
    {
        $sql = 'SELECT m.*, f.name AS farm_name, u.name AS uploaded_by_name FROM media_files m
            LEFT JOIN farms f ON f.id = m.farm_id
            JOIN users u ON u.id = m.uploaded_by';
        $conditions = ['m.organization_id = :organization_id'];
        $params = ['organization_id' => $organizationId];
        if ($category) {
            $conditions[] = 'm.category = :category';
            $params['category'] = $category;
        }
        if ($farmId) {
            $conditions[] = 'm.farm_id = :farm_id';
            $params['farm_id'] = $farmId;
        }
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
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

    /** The tenant-isolation check: fetching another organization's file by id returns null. */
    public static function findInOrganization(int $id, int $organizationId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM media_files WHERE id = :id AND organization_id = :org_id');
        $stmt->execute(['id' => $id, 'org_id' => $organizationId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data, string $filePath, int $uploadedBy): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO media_files (farm_id, organization_id, category, title, file_path, uploaded_by, notes)
            VALUES (:farm_id, :organization_id, :category, :title, :file_path, :uploaded_by, :notes)');
        $stmt->execute([
            'farm_id' => $data['farm_id'] ?: null,
            'organization_id' => $data['organization_id'],
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

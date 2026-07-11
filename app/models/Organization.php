<?php

namespace App\Models;

use App\Core\Database;

class Organization
{
    public static function all(): array
    {
        return Database::connection()->query('SELECT o.*, u.name AS owner_name, u.email AS owner_email,
                (SELECT COUNT(*) FROM farms f WHERE f.organization_id = o.id) AS farm_count,
                (SELECT COUNT(*) FROM users tu WHERE tu.organization_id = o.id) AS user_count
            FROM organizations o LEFT JOIN users u ON u.id = o.owner_user_id ORDER BY o.created_at DESC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT o.*, u.name AS owner_name, u.email AS owner_email
            FROM organizations o LEFT JOIN users u ON u.id = o.owner_user_id WHERE o.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Creates the organization row only. Caller creates the owner user and links both ids in one transaction (see RegistrationController). */
    public static function create(string $name): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO organizations (name, status) VALUES (:name, :status)');
        $stmt->execute(['name' => $name, 'status' => 'active']);
        return (int) $pdo->lastInsertId();
    }

    public static function setOwner(int $organizationId, int $userId): void
    {
        $stmt = Database::connection()->prepare('UPDATE organizations SET owner_user_id = :user_id WHERE id = :id');
        $stmt->execute(['user_id' => $userId, 'id' => $organizationId]);
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connection()->prepare('UPDATE organizations SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }
}

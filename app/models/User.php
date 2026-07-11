<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function all(): array
    {
        $pdo = Database::connection();
        return $pdo->query('SELECT u.*, r.name AS role_name FROM users u
            JOIN roles r ON r.id = u.role_id ORDER BY u.created_at DESC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u
            JOIN roles r ON r.id = u.role_id WHERE u.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public static function forOrganization(int $organizationId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u
            JOIN roles r ON r.id = u.role_id WHERE u.organization_id = :org_id ORDER BY u.created_at DESC');
        $stmt->execute(['org_id' => $organizationId]);
        return $stmt->fetchAll();
    }

    /** Fetches a user only if they belong to the given organization -- the actual
     * tenant-isolation check for team management (prevents one org's owner from
     * reaching another org's user by guessing an id in the URL). */
    public static function findInOrganization(int $id, int $organizationId): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u
            JOIN roles r ON r.id = u.role_id WHERE u.id = :id AND u.organization_id = :org_id');
        $stmt->execute(['id' => $id, 'org_id' => $organizationId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, role_id, organization_id, status, mfa_enabled)
            VALUES (:name, :email, :phone, :password_hash, :role_id, :organization_id, :status, :mfa_enabled)');
        $stmt->execute([
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role_id' => $data['role_id'],
            'organization_id' => $data['organization_id'] ?? null,
            'status' => $data['status'] ?? 'active',
            'mfa_enabled' => $data['mfa_enabled'] ?? 1,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::connection();
        $fields = ['name = :name', 'email = :email', 'phone = :phone', 'role_id = :role_id', 'status = :status', 'mfa_enabled = :mfa_enabled'];
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'role_id' => $data['role_id'],
            'status' => $data['status'],
            'mfa_enabled' => $data['mfa_enabled'] ?? 1,
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $pdo->prepare($sql)->execute($params);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::connection()->prepare('UPDATE users SET status = :status WHERE id = :id')
            ->execute(['id' => $id, 'status' => $status]);
    }
}

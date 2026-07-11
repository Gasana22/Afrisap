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

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, role_id, status, mfa_enabled)
            VALUES (:name, :email, :phone, :password_hash, :role_id, :status, :mfa_enabled)');
        $stmt->execute([
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'role_id' => $data['role_id'],
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

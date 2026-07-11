<?php

namespace App\Models;

use App\Core\Database;

class Worker
{
    private const SELECT_BASE = "SELECT w.*, f.name AS farm_name, u.email AS user_email
        FROM workers w
        JOIN farms f ON f.id = w.farm_id
        LEFT JOIN users u ON u.id = w.user_id";

    public static function all(): array
    {
        return Database::connection()->query(self::SELECT_BASE . ' ORDER BY w.created_at DESC')->fetchAll();
    }

    public static function paginated(int $page, int $perPage = 25): array
    {
        $pdo = Database::connection();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM workers')->fetchColumn();

        $stmt = $pdo->prepare(self::SELECT_BASE . ' ORDER BY w.created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'totalPages' => (int) ceil($total / $perPage)];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE w.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO workers (farm_id, user_id, name, phone, role_title, hire_date, pay_rate, pay_rate_type, status)
            VALUES (:farm_id, :user_id, :name, :phone, :role_title, :hire_date, :pay_rate, :pay_rate_type, :status)');
        $stmt->execute([
            'farm_id' => $data['farm_id'],
            'user_id' => $data['user_id'] ?: null,
            'name' => $data['name'],
            'phone' => $data['phone'] ?: null,
            'role_title' => $data['role_title'] ?: null,
            'hire_date' => $data['hire_date'] ?: null,
            'pay_rate' => $data['pay_rate'] !== '' ? $data['pay_rate'] : null,
            'pay_rate_type' => $data['pay_rate_type'] ?? 'daily',
            'status' => 'active',
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE workers SET name = :name, phone = :phone, role_title = :role_title,
            hire_date = :hire_date, pay_rate = :pay_rate, pay_rate_type = :pay_rate_type, user_id = :user_id WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?: null,
            'role_title' => $data['role_title'] ?: null,
            'hire_date' => $data['hire_date'] ?: null,
            'pay_rate' => $data['pay_rate'] !== '' ? $data['pay_rate'] : null,
            'pay_rate_type' => $data['pay_rate_type'] ?? 'daily',
            'user_id' => $data['user_id'] ?: null,
        ]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::connection()->prepare('UPDATE workers SET status = :status WHERE id = :id')
            ->execute(['id' => $id, 'status' => $status]);
    }
}

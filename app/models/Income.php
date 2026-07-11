<?php

namespace App\Models;

use App\Core\Database;

class Income
{
    public static function all(): array
    {
        return Database::connection()->query('SELECT i.*, f.name AS farm_name, u.name AS recorded_by_name
            FROM income i JOIN farms f ON f.id = i.farm_id JOIN users u ON u.id = i.recorded_by
            ORDER BY i.income_date DESC, i.id DESC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM income WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function forOrganization(int $organizationId): array
    {
        $stmt = Database::connection()->prepare('SELECT i.*, f.name AS farm_name, u.name AS recorded_by_name
            FROM income i JOIN farms f ON f.id = i.farm_id JOIN users u ON u.id = i.recorded_by
            WHERE f.organization_id = :org_id ORDER BY i.income_date DESC, i.id DESC');
        $stmt->execute(['org_id' => $organizationId]);
        return $stmt->fetchAll();
    }

    /** The tenant-isolation check: fetching another organization's income entry by id returns null. */
    public static function findInOrganization(int $id, int $organizationId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT i.* FROM income i JOIN farms f ON f.id = i.farm_id
            WHERE i.id = :id AND f.organization_id = :org_id');
        $stmt->execute(['id' => $id, 'org_id' => $organizationId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data, int $recordedBy): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO income (farm_id, description, amount, income_date, notes, recorded_by)
            VALUES (:farm_id, :description, :amount, :income_date, :notes, :recorded_by)');
        $stmt->execute([
            'farm_id' => $data['farm_id'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'income_date' => $data['income_date'],
            'notes' => $data['notes'] ?: null,
            'recorded_by' => $recordedBy,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM income WHERE id = :id')->execute(['id' => $id]);
    }
}

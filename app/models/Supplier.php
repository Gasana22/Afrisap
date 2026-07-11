<?php

namespace App\Models;

use App\Core\Database;

class Supplier
{
    public static function all(): array
    {
        return Database::connection()->query('SELECT * FROM suppliers ORDER BY name')->fetchAll();
    }

    public static function forOrganization(int $organizationId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM suppliers WHERE organization_id = :org_id ORDER BY name');
        $stmt->execute(['org_id' => $organizationId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM suppliers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** The tenant-isolation check: fetching another organization's supplier by id returns null. */
    public static function findInOrganization(int $id, int $organizationId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM suppliers WHERE id = :id AND organization_id = :org_id');
        $stmt->execute(['id' => $id, 'org_id' => $organizationId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO suppliers (organization_id, name, contact_person, phone, email, address, category)
            VALUES (:organization_id, :name, :contact_person, :phone, :email, :address, :category)');
        $stmt->execute([
            'organization_id' => $data['organization_id'],
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?: null,
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'address' => $data['address'] ?: null,
            'category' => $data['category'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE suppliers SET name = :name, contact_person = :contact_person,
            phone = :phone, email = :email, address = :address, category = :category WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?: null,
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'address' => $data['address'] ?: null,
            'category' => $data['category'] ?: null,
        ]);
    }

    public static function hasPurchaseOrders(int $id): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM purchase_orders WHERE supplier_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM suppliers WHERE id = :id')->execute(['id' => $id]);
    }
}

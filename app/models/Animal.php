<?php

namespace App\Models;

use App\Core\Database;

class Animal
{
    private const SELECT_BASE = "SELECT a.*, f.name AS farm_name, f.code AS farm_code, p.name AS parent_name
        FROM animals a
        JOIN farms f ON f.id = a.farm_id
        LEFT JOIN animals p ON p.id = a.parent_id";

    public static function all(): array
    {
        return Database::connection()->query(self::SELECT_BASE . ' ORDER BY a.created_at DESC')->fetchAll();
    }

    public static function paginated(int $page, int $perPage, int $organizationId): array
    {
        $pdo = Database::connection();

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM animals a JOIN farms f ON f.id = a.farm_id WHERE f.organization_id = :org_id');
        $countStmt->execute(['org_id' => $organizationId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare(self::SELECT_BASE . ' WHERE f.organization_id = :org_id ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':org_id', $organizationId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'totalPages' => (int) ceil($total / $perPage)];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE a.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** The tenant-isolation check: fetching another organization's animal by id returns null. */
    public static function findInOrganization(int $id, int $organizationId): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE a.id = :id AND f.organization_id = :org_id');
        $stmt->execute(['id' => $id, 'org_id' => $organizationId]);
        return $stmt->fetch() ?: null;
    }

    public static function forOrganization(int $organizationId): array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE f.organization_id = :org_id ORDER BY a.created_at DESC');
        $stmt->execute(['org_id' => $organizationId]);
        return $stmt->fetchAll();
    }

    public static function forFarm(int $farmId): array
    {
        $stmt = Database::connection()->prepare('SELECT id, name, animal_code FROM animals WHERE farm_id = :farm_id ORDER BY name');
        $stmt->execute(['farm_id' => $farmId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $year = $data['birth_date'] ? substr($data['birth_date'], 0, 4) : date('Y');
        $code = self::generateAnimalCode($data['species'], (int) $year);

        $stmt = $pdo->prepare('INSERT INTO animals (farm_id, animal_code, name, species, breed, tag_number, gender, birth_date, parent_id, status)
            VALUES (:farm_id, :animal_code, :name, :species, :breed, :tag_number, :gender, :birth_date, :parent_id, :status)');
        $stmt->execute([
            'farm_id' => $data['farm_id'],
            'animal_code' => $code,
            'name' => $data['name'] ?: null,
            'species' => $data['species'],
            'breed' => $data['breed'] ?: null,
            'tag_number' => $data['tag_number'] ?: null,
            'gender' => $data['gender'],
            'birth_date' => $data['birth_date'] ?: null,
            'parent_id' => $data['parent_id'] ?: null,
            'status' => 'active',
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE animals SET name = :name, breed = :breed, tag_number = :tag_number,
            gender = :gender, birth_date = :birth_date, parent_id = :parent_id WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'] ?: null,
            'breed' => $data['breed'] ?: null,
            'tag_number' => $data['tag_number'] ?: null,
            'gender' => $data['gender'],
            'birth_date' => $data['birth_date'] ?: null,
            'parent_id' => $data['parent_id'] ?: null,
        ]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::connection()->prepare('UPDATE animals SET status = :status WHERE id = :id')
            ->execute(['id' => $id, 'status' => $status]);
    }

    public static function hasAnyHistory(int $id): bool
    {
        $pdo = Database::connection();
        $tables = ['animal_vaccinations', 'animal_feedings', 'animal_weights', 'animal_treatments',
            'animal_breeding', 'animal_production', 'animal_mortality', 'animal_sales'];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE animal_id = :id");
            $stmt->execute(['id' => $id]);
            if ((int) $stmt->fetchColumn() > 0) {
                return true;
            }
        }
        return false;
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM animals WHERE id = :id')->execute(['id' => $id]);
    }

    private static function generateAnimalCode(string $species, int $year): string
    {
        $speciesSlug = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $species));
        $prefix = "AN-{$speciesSlug}-{$year}";

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM animals WHERE animal_code LIKE :prefix');
        $stmt->execute(['prefix' => $prefix . '-%']);
        $sequence = ((int) $stmt->fetchColumn()) + 1;

        return sprintf('%s-%04d', $prefix, $sequence);
    }
}

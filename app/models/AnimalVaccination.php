<?php

namespace App\Models;

use App\Core\Database;

class AnimalVaccination
{
    public static function forAnimal(int $animalId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_vaccinations WHERE animal_id = :id ORDER BY date_administered DESC, id DESC');
        $stmt->execute(['id' => $animalId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_vaccinations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $animalId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO animal_vaccinations (animal_id, vaccine_name, date_administered, next_due_date, administered_by, notes)
            VALUES (:animal_id, :vaccine_name, :date_administered, :next_due_date, :administered_by, :notes)');
        $stmt->execute([
            'animal_id' => $animalId,
            'vaccine_name' => $data['vaccine_name'],
            'date_administered' => $data['date_administered'],
            'next_due_date' => $data['next_due_date'] ?: null,
            'administered_by' => $data['administered_by'] ?: null,
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

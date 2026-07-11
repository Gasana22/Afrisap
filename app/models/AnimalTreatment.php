<?php

namespace App\Models;

use App\Core\Database;

class AnimalTreatment
{
    public static function forAnimal(int $animalId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_treatments WHERE animal_id = :id ORDER BY treatment_date DESC, id DESC');
        $stmt->execute(['id' => $animalId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_treatments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $animalId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO animal_treatments (animal_id, condition_name, treatment, treatment_date, administered_by, cost, notes)
            VALUES (:animal_id, :condition_name, :treatment, :treatment_date, :administered_by, :cost, :notes)');
        $stmt->execute([
            'animal_id' => $animalId,
            'condition_name' => $data['condition_name'],
            'treatment' => $data['treatment'],
            'treatment_date' => $data['treatment_date'],
            'administered_by' => $data['administered_by'] ?: null,
            'cost' => $data['cost'] !== '' ? $data['cost'] : null,
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

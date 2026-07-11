<?php

namespace App\Models;

use App\Core\Database;

class AnimalWeight
{
    public static function forAnimal(int $animalId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_weights WHERE animal_id = :id ORDER BY recorded_date DESC, id DESC');
        $stmt->execute(['id' => $animalId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_weights WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $animalId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO animal_weights (animal_id, weight_kg, recorded_date, notes)
            VALUES (:animal_id, :weight_kg, :recorded_date, :notes)');
        $stmt->execute([
            'animal_id' => $animalId,
            'weight_kg' => $data['weight_kg'],
            'recorded_date' => $data['recorded_date'],
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

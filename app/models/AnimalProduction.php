<?php

namespace App\Models;

use App\Core\Database;

class AnimalProduction
{
    public static function forAnimal(int $animalId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_production WHERE animal_id = :id ORDER BY production_date DESC, id DESC');
        $stmt->execute(['id' => $animalId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_production WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $animalId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO animal_production (animal_id, production_type, quantity, unit, production_date, notes)
            VALUES (:animal_id, :production_type, :quantity, :unit, :production_date, :notes)');
        $stmt->execute([
            'animal_id' => $animalId,
            'production_type' => $data['production_type'],
            'quantity' => $data['quantity'],
            'unit' => $data['unit'] ?: null,
            'production_date' => $data['production_date'],
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

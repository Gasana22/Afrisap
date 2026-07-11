<?php

namespace App\Models;

use App\Core\Database;

class AnimalBreeding
{
    public static function forAnimal(int $animalId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_breeding WHERE animal_id = :id ORDER BY breeding_date DESC, id DESC');
        $stmt->execute(['id' => $animalId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_breeding WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $animalId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO animal_breeding (animal_id, mate_description, breeding_date, expected_due_date, outcome, offspring_count, notes)
            VALUES (:animal_id, :mate_description, :breeding_date, :expected_due_date, :outcome, :offspring_count, :notes)');
        $stmt->execute([
            'animal_id' => $animalId,
            'mate_description' => $data['mate_description'] ?: null,
            'breeding_date' => $data['breeding_date'],
            'expected_due_date' => $data['expected_due_date'] ?: null,
            'outcome' => $data['outcome'] ?? 'pending',
            'offspring_count' => $data['offspring_count'] !== '' ? $data['offspring_count'] : null,
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

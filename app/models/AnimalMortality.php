<?php

namespace App\Models;

use App\Core\Database;

class AnimalMortality
{
    public static function forAnimal(int $animalId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_mortality WHERE animal_id = :id');
        $stmt->execute(['id' => $animalId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $animalId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO animal_mortality (animal_id, death_date, cause, notes) VALUES (:animal_id, :death_date, :cause, :notes)');
        $stmt->execute([
            'animal_id' => $animalId,
            'death_date' => $data['death_date'],
            'cause' => $data['cause'] ?: null,
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

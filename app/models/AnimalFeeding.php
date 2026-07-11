<?php

namespace App\Models;

use App\Core\Database;

class AnimalFeeding
{
    public static function forAnimal(int $animalId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_feedings WHERE animal_id = :id ORDER BY feeding_date DESC, id DESC');
        $stmt->execute(['id' => $animalId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_feedings WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $animalId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO animal_feedings (animal_id, feed_type, quantity, unit, feeding_date, cost, notes)
            VALUES (:animal_id, :feed_type, :quantity, :unit, :feeding_date, :cost, :notes)');
        $stmt->execute([
            'animal_id' => $animalId,
            'feed_type' => $data['feed_type'],
            'quantity' => $data['quantity'] !== '' ? $data['quantity'] : null,
            'unit' => $data['unit'] ?: null,
            'feeding_date' => $data['feeding_date'],
            'cost' => $data['cost'] !== '' ? $data['cost'] : null,
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

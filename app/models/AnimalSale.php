<?php

namespace App\Models;

use App\Core\Database;

class AnimalSale
{
    public static function forAnimal(int $animalId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM animal_sales WHERE animal_id = :id');
        $stmt->execute(['id' => $animalId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $animalId, array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO animal_sales (animal_id, buyer_name, sale_price, sale_date, notes)
            VALUES (:animal_id, :buyer_name, :sale_price, :sale_date, :notes)');
        $stmt->execute([
            'animal_id' => $animalId,
            'buyer_name' => $data['buyer_name'],
            'sale_price' => $data['sale_price'],
            'sale_date' => $data['sale_date'],
            'notes' => $data['notes'] ?: null,
        ]);
        return (int) $pdo->lastInsertId();
    }
}

<?php

namespace App\Models;

use App\Core\Database;

class Expense
{
    public static function all(): array
    {
        return Database::connection()->query('SELECT e.*, f.name AS farm_name, u.name AS recorded_by_name
            FROM expenses e JOIN farms f ON f.id = e.farm_id JOIN users u ON u.id = e.recorded_by
            ORDER BY e.expense_date DESC, e.id DESC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM expenses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data, int $recordedBy): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO expenses (farm_id, category, description, amount, expense_date, notes, recorded_by)
            VALUES (:farm_id, :category, :description, :amount, :expense_date, :notes, :recorded_by)');
        $stmt->execute([
            'farm_id' => $data['farm_id'],
            'category' => $data['category'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'notes' => $data['notes'] ?: null,
            'recorded_by' => $recordedBy,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM expenses WHERE id = :id')->execute(['id' => $id]);
    }
}

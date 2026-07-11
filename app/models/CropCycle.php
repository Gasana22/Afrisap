<?php

namespace App\Models;

use App\Core\Database;

class CropCycle
{
    private const SELECT_BASE = "SELECT cc.*, ct.name AS crop_type_name, s.name AS season_name,
        p.plot_code, b.name AS block_name, f.id AS farm_id, f.name AS farm_name, f.code AS farm_code
        FROM crop_cycles cc
        JOIN crop_types ct ON ct.id = cc.crop_type_id
        LEFT JOIN seasons s ON s.id = cc.season_id
        JOIN plots p ON p.id = cc.plot_id
        JOIN blocks b ON b.id = p.block_id
        JOIN farms f ON f.id = b.farm_id";

    public static function all(): array
    {
        return Database::connection()->query(self::SELECT_BASE . ' ORDER BY cc.created_at DESC')->fetchAll();
    }

    public static function paginated(int $page, int $perPage = 25): array
    {
        $pdo = Database::connection();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM crop_cycles')->fetchColumn();

        $stmt = $pdo->prepare(self::SELECT_BASE . ' ORDER BY cc.created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'totalPages' => (int) ceil($total / $perPage)];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(self::SELECT_BASE . ' WHERE cc.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();

        $plot = Plot::findWithContext((int) $data['plot_id']);
        $cropType = CropType::find((int) $data['crop_type_id']);
        $year = $data['start_date'] ? substr($data['start_date'], 0, 4) : date('Y');

        $batchCode = self::generateBatchCode($cropType['name'], $plot, (int) $year);

        $stmt = $pdo->prepare('INSERT INTO crop_cycles (batch_code, plot_id, crop_type_id, season_id, budget, expected_yield, start_date, status)
            VALUES (:batch_code, :plot_id, :crop_type_id, :season_id, :budget, :expected_yield, :start_date, :status)');
        $stmt->execute([
            'batch_code' => $batchCode,
            'plot_id' => $data['plot_id'],
            'crop_type_id' => $data['crop_type_id'],
            'season_id' => $data['season_id'] ?: null,
            'budget' => $data['budget'] !== '' ? $data['budget'] : null,
            'expected_yield' => $data['expected_yield'] !== '' ? $data['expected_yield'] : null,
            'start_date' => $data['start_date'] ?: null,
            'status' => $data['status'] ?? 'planning',
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE crop_cycles SET season_id = :season_id, budget = :budget,
            expected_yield = :expected_yield, start_date = :start_date, status = :status WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'season_id' => $data['season_id'] ?: null,
            'budget' => $data['budget'] !== '' ? $data['budget'] : null,
            'expected_yield' => $data['expected_yield'] !== '' ? $data['expected_yield'] : null,
            'start_date' => $data['start_date'] ?: null,
            'status' => $data['status'],
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM crop_cycles WHERE id = :id')->execute(['id' => $id]);
    }

    private static function generateBatchCode(string $cropTypeName, array $plot, int $year): string
    {
        $cropSlug = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $cropTypeName));
        $blockSlug = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plot['block_name']));
        $prefix = "{$cropSlug}-{$year}-{$plot['farm_code']}-{$blockSlug}";

        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM crop_cycles WHERE batch_code LIKE :prefix");
        $stmt->execute(['prefix' => $prefix . '-%']);
        $sequence = ((int) $stmt->fetchColumn()) + 1;

        return sprintf('%s-%03d', $prefix, $sequence);
    }
}

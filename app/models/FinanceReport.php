<?php

namespace App\Models;

use App\Core\Database;

class FinanceReport
{
    private const INCOME_UNION_SQL = "
        SELECT cs.sale_date AS entry_date, cs.revenue AS amount, 'Crop Sale' AS source, f.id AS farm_id, f.name AS farm_name
        FROM crop_sales cs
        JOIN harvests h ON h.id = cs.harvest_id
        JOIN crop_cycles cc ON cc.id = h.crop_cycle_id
        JOIN plots p ON p.id = cc.plot_id
        JOIN blocks b ON b.id = p.block_id
        JOIN farms f ON f.id = b.farm_id

        UNION ALL

        SELECT asale.sale_date AS entry_date, asale.sale_price AS amount, 'Livestock Sale' AS source, f.id AS farm_id, f.name AS farm_name
        FROM animal_sales asale
        JOIN animals a ON a.id = asale.animal_id
        JOIN farms f ON f.id = a.farm_id

        UNION ALL

        SELECT i.income_date AS entry_date, i.amount AS amount, i.description AS source, f.id AS farm_id, f.name AS farm_name
        FROM income i
        JOIN farms f ON f.id = i.farm_id
    ";

    private const EXPENSE_UNION_SQL = "
        SELECT ci.purchase_date AS entry_date, ci.cost AS amount, CONCAT('Input: ', ci.input_type) AS source, f.id AS farm_id, f.name AS farm_name
        FROM crop_inputs ci
        JOIN crop_cycles cc ON cc.id = ci.crop_cycle_id
        JOIN plots p ON p.id = cc.plot_id
        JOIN blocks b ON b.id = p.block_id
        JOIN farms f ON f.id = b.farm_id
        WHERE ci.cost IS NOT NULL AND ci.purchase_date IS NOT NULL

        UNION ALL

        SELECT DATE(wp.paid_at) AS entry_date, wp.net_pay AS amount, 'Payroll' AS source, f.id AS farm_id, f.name AS farm_name
        FROM worker_payroll wp
        JOIN workers w ON w.id = wp.worker_id
        JOIN farms f ON f.id = w.farm_id
        WHERE wp.status = 'paid'

        UNION ALL

        SELECT e.expense_date AS entry_date, e.amount AS amount, e.description AS source, f.id AS farm_id, f.name AS farm_name
        FROM expenses e
        JOIN farms f ON f.id = e.farm_id
    ";

    public static function incomeEntries(?int $farmId = null, ?string $from = null, ?string $to = null): array
    {
        return self::runFiltered(self::INCOME_UNION_SQL, $farmId, $from, $to);
    }

    public static function expenseEntries(?int $farmId = null, ?string $from = null, ?string $to = null): array
    {
        return self::runFiltered(self::EXPENSE_UNION_SQL, $farmId, $from, $to);
    }

    public static function summary(?int $farmId = null, ?string $from = null, ?string $to = null): array
    {
        $income = self::incomeEntries($farmId, $from, $to);
        $expenses = self::expenseEntries($farmId, $from, $to);

        $totalIncome = array_sum(array_column($income, 'amount'));
        $totalExpenses = array_sum(array_column($expenses, 'amount'));

        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $totalIncome - $totalExpenses,
        ];
    }

    private static function runFiltered(string $unionSql, ?int $farmId, ?string $from, ?string $to): array
    {
        $params = [];
        $conditions = [];
        if ($farmId) {
            $conditions[] = 'farm_id = :farm_id';
            $params['farm_id'] = $farmId;
        }
        if ($from) {
            $conditions[] = 'entry_date >= :from_date';
            $params['from_date'] = $from;
        }
        if ($to) {
            $conditions[] = 'entry_date <= :to_date';
            $params['to_date'] = $to;
        }
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT * FROM ({$unionSql}) combined {$where} ORDER BY entry_date DESC";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

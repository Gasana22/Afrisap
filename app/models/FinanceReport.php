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

        SELECT am.maintenance_date AS entry_date, am.cost AS amount, CONCAT('Asset Maintenance: ', am.description) AS source, f.id AS farm_id, f.name AS farm_name
        FROM asset_maintenance am
        JOIN assets a ON a.id = am.asset_id
        JOIN farms f ON f.id = a.farm_id
        WHERE am.cost IS NOT NULL

        UNION ALL

        SELECT e.expense_date AS entry_date, e.amount AS amount, e.description AS source, f.id AS farm_id, f.name AS farm_name
        FROM expenses e
        JOIN farms f ON f.id = e.farm_id
    ";

    /** @param int|array<int>|null $farmId A single farm id, a list of farm ids (an organization can own several farms), or null for unscoped (platform use only). */
    public static function incomeEntries(int|array|null $farmId = null, ?string $from = null, ?string $to = null): array
    {
        return self::runFiltered(self::INCOME_UNION_SQL, $farmId, $from, $to);
    }

    public static function expenseEntries(int|array|null $farmId = null, ?string $from = null, ?string $to = null): array
    {
        return self::runFiltered(self::EXPENSE_UNION_SQL, $farmId, $from, $to);
    }

    public static function summary(int|array|null $farmId = null, ?string $from = null, ?string $to = null): array
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

    private static function runFiltered(string $unionSql, int|array|null $farmId, ?string $from, ?string $to): array
    {
        // An organization with zero farms yet (fresh signup) must see zero
        // entries, not every organization's -- short-circuit before the query
        // rather than letting an empty IN () clause fall through to unscoped.
        if (is_array($farmId) && $farmId === []) {
            return [];
        }

        $params = [];
        $conditions = [];
        if (is_array($farmId)) {
            $placeholders = [];
            foreach (array_values($farmId) as $i => $fid) {
                $key = "farm_id_{$i}";
                $placeholders[] = ":{$key}";
                $params[$key] = $fid;
            }
            $conditions[] = 'farm_id IN (' . implode(',', $placeholders) . ')';
        } elseif ($farmId) {
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

<?php

namespace App\Models;

use App\Core\Database;

class AnalyticsReport
{
    public static function revenueExpenseTrend(int $months = 6): array
    {
        $income = FinanceReport::incomeEntries();
        $expenses = FinanceReport::expenseEntries();

        $buckets = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-{$i} months"));
            $buckets[$key] = ['month' => date('M Y', strtotime("-{$i} months")), 'income' => 0.0, 'expense' => 0.0];
        }

        foreach ($income as $entry) {
            $key = substr($entry['entry_date'], 0, 7);
            if (isset($buckets[$key])) {
                $buckets[$key]['income'] += (float) $entry['amount'];
            }
        }
        foreach ($expenses as $entry) {
            $key = substr($entry['entry_date'], 0, 7);
            if (isset($buckets[$key])) {
                $buckets[$key]['expense'] += (float) $entry['amount'];
            }
        }

        return array_values($buckets);
    }

    public static function revenueGrowth(): array
    {
        $income = FinanceReport::incomeEntries();
        $thisMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));

        $thisTotal = 0.0;
        $lastTotal = 0.0;
        foreach ($income as $entry) {
            $key = substr($entry['entry_date'], 0, 7);
            if ($key === $thisMonth) {
                $thisTotal += (float) $entry['amount'];
            } elseif ($key === $lastMonth) {
                $lastTotal += (float) $entry['amount'];
            }
        }

        $growth = $lastTotal > 0 ? (($thisTotal - $lastTotal) / $lastTotal) * 100 : ($thisTotal > 0 ? 100.0 : 0.0);

        return ['this_month' => $thisTotal, 'last_month' => $lastTotal, 'growth_pct' => $growth];
    }

    public static function costYieldPerHectare(): array
    {
        $sql = "SELECT ct.name AS crop_type_name,
                AVG(COALESCE(ci.cost_total, 0) / p.size_hectares) AS avg_cost_per_hectare,
                AVG(COALESCE(h.yield_total, 0) / p.size_hectares) AS avg_yield_per_hectare
            FROM crop_cycles cc
            JOIN crop_types ct ON ct.id = cc.crop_type_id
            JOIN plots p ON p.id = cc.plot_id
            LEFT JOIN (SELECT crop_cycle_id, SUM(cost) AS cost_total FROM crop_inputs GROUP BY crop_cycle_id) ci ON ci.crop_cycle_id = cc.id
            LEFT JOIN (SELECT crop_cycle_id, SUM(quantity) AS yield_total FROM harvests GROUP BY crop_cycle_id) h ON h.crop_cycle_id = cc.id
            WHERE p.size_hectares IS NOT NULL AND p.size_hectares > 0
            GROUP BY ct.id, ct.name
            ORDER BY ct.name";

        return Database::connection()->query($sql)->fetchAll();
    }

    public static function workerProductivity(?int $limit = 10): array
    {
        $sql = "SELECT w.name, f.name AS farm_name,
                COUNT(CASE WHEN wt.status = 'verified' THEN 1 END) AS tasks_verified,
                COUNT(CASE WHEN wa.status = 'present' THEN 1 END) AS days_present
            FROM workers w
            JOIN farms f ON f.id = w.farm_id
            LEFT JOIN worker_tasks wt ON wt.worker_id = w.id
            LEFT JOIN worker_attendance wa ON wa.worker_id = w.id
            GROUP BY w.id, w.name, f.name
            ORDER BY tasks_verified DESC, days_present DESC";

        $pdo = Database::connection();
        if ($limit === null) {
            return $pdo->query($sql)->fetchAll();
        }

        $stmt = $pdo->prepare($sql . ' LIMIT :limit');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function livestockMortality(): array
    {
        $stmt = Database::connection()->query("SELECT COUNT(*) AS total, SUM(status = 'deceased') AS deceased FROM animals");
        $row = $stmt->fetch();
        $total = (int) ($row['total'] ?? 0);
        $deceased = (int) ($row['deceased'] ?? 0);
        $rate = $total > 0 ? ($deceased / $total) * 100 : 0.0;

        return ['total' => $total, 'deceased' => $deceased, 'rate' => $rate];
    }
}

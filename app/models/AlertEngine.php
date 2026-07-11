<?php

namespace App\Models;

use App\Core\Database;

class AlertEngine
{
    /** @param int|null $organizationId null = unscoped (platform use only) */
    public static function all(?int $organizationId): array
    {
        return array_merge(
            self::missingRecords($organizationId),
            self::expiredInputs($organizationId),
            self::diseaseOutbreaks($organizationId),
            self::inventoryVariance($organizationId),
            self::unverifiedActivities($organizationId)
        );
    }

    public static function count(?int $organizationId): int
    {
        return count(self::all($organizationId));
    }

    public static function missingRecords(?int $organizationId): array
    {
        $where = $organizationId !== null ? 'AND f.organization_id = :org_id' : '';
        $sql = "SELECT cc.id, cc.batch_code, f.name AS farm_name,
                MAX(fa.activity_date) AS last_activity_date
            FROM crop_cycles cc
            JOIN plots p ON p.id = cc.plot_id
            JOIN blocks b ON b.id = p.block_id
            JOIN farms f ON f.id = b.farm_id
            LEFT JOIN field_activities fa ON fa.crop_cycle_id = cc.id
            WHERE cc.status IN ('field', 'monitoring') {$where}
            GROUP BY cc.id, cc.batch_code, f.name, cc.start_date
            HAVING (last_activity_date IS NULL AND cc.start_date <= DATE_SUB(CURDATE(), INTERVAL 14 DAY))
                OR last_activity_date <= DATE_SUB(CURDATE(), INTERVAL 14 DAY)";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($organizationId !== null ? ['org_id' => $organizationId] : []);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => [
            'type' => 'missing_records',
            'severity' => 'warning',
            'title' => 'No recent field activity',
            'description' => "Batch {$r['batch_code']} ({$r['farm_name']}) has no field activity logged" .
                ($r['last_activity_date'] ? " since {$r['last_activity_date']}" : ' since it started') . '.',
            'link' => "/crops/{$r['id']}",
        ], $rows);
    }

    public static function expiredInputs(?int $organizationId): array
    {
        $where = $organizationId !== null ? 'AND f.organization_id = :org_id' : '';
        $sql = "SELECT ci.id, ci.input_type, ci.expiry_date, cc.batch_code, f.name AS farm_name
            FROM crop_inputs ci
            JOIN crop_cycles cc ON cc.id = ci.crop_cycle_id
            JOIN plots p ON p.id = cc.plot_id
            JOIN blocks b ON b.id = p.block_id
            JOIN farms f ON f.id = b.farm_id
            WHERE ci.expiry_date IS NOT NULL AND ci.expiry_date < CURDATE() {$where}";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($organizationId !== null ? ['org_id' => $organizationId] : []);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => [
            'type' => 'expired_input',
            'severity' => 'danger',
            'title' => 'Expired input',
            'description' => ucfirst($r['input_type']) . " for batch {$r['batch_code']} ({$r['farm_name']}) expired on {$r['expiry_date']}.",
            'link' => "/crops/" . self::cropCycleIdFromInput((int) $r['id']),
        ], $rows);
    }

    public static function diseaseOutbreaks(?int $organizationId): array
    {
        $where = $organizationId !== null ? 'AND f.organization_id = :org_id' : '';
        $sql = "SELECT mr.id, mr.crop_cycle_id, mr.description, mr.record_date, cc.batch_code, f.name AS farm_name
            FROM monitoring_records mr
            JOIN crop_cycles cc ON cc.id = mr.crop_cycle_id
            JOIN plots p ON p.id = cc.plot_id
            JOIN blocks b ON b.id = p.block_id
            JOIN farms f ON f.id = b.farm_id
            WHERE mr.type = 'disease' AND mr.severity = 'high' AND mr.record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) {$where}";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($organizationId !== null ? ['org_id' => $organizationId] : []);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => [
            'type' => 'disease_outbreak',
            'severity' => 'danger',
            'title' => 'Disease outbreak reported',
            'description' => "High-severity disease on batch {$r['batch_code']} ({$r['farm_name']}), {$r['record_date']}" . ($r['description'] ? ": {$r['description']}" : '.'),
            'link' => "/crops/{$r['crop_cycle_id']}",
        ], $rows);
    }

    public static function inventoryVariance(?int $organizationId): array
    {
        $where = $organizationId !== null ? 'AND i.organization_id = :org_id' : '';
        $sql = "SELECT * FROM (
                SELECT i.id, i.name, i.reorder_level,
                    (SELECT COALESCE(SUM(quantity_on_hand), 0) FROM inventory_stock WHERE item_id = i.id) AS total_stock
                FROM inventory_items i
                WHERE i.reorder_level IS NOT NULL {$where}
            ) t
            WHERE t.total_stock < t.reorder_level";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($organizationId !== null ? ['org_id' => $organizationId] : []);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => [
            'type' => 'inventory_variance',
            'severity' => 'warning',
            'title' => 'Low stock',
            'description' => "{$r['name']} is below its reorder level (current stock: {$r['total_stock']}).",
            'link' => "/inventory/{$r['id']}",
        ], $rows);
    }

    public static function unverifiedActivities(?int $organizationId): array
    {
        $where = $organizationId !== null ? 'AND f.organization_id = :org_id' : '';
        $sql = "SELECT wt.id, wt.title, wt.updated_at, w.name AS worker_name, w.id AS worker_id
            FROM worker_tasks wt
            JOIN workers w ON w.id = wt.worker_id
            JOIN farms f ON f.id = w.farm_id
            WHERE wt.status = 'completed' AND wt.updated_at <= DATE_SUB(NOW(), INTERVAL 3 DAY) {$where}";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($organizationId !== null ? ['org_id' => $organizationId] : []);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => [
            'type' => 'unverified_activity',
            'severity' => 'warning',
            'title' => 'Task awaiting verification',
            'description' => "\"{$r['title']}\" completed by {$r['worker_name']} has not been verified since {$r['updated_at']}.",
            'link' => "/workers/{$r['worker_id']}",
        ], $rows);
    }

    private static function cropCycleIdFromInput(int $cropInputId): int
    {
        $stmt = Database::connection()->prepare('SELECT crop_cycle_id FROM crop_inputs WHERE id = :id');
        $stmt->execute(['id' => $cropInputId]);
        return (int) $stmt->fetchColumn();
    }
}

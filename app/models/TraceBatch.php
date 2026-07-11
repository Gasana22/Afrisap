<?php

namespace App\Models;

use App\Core\Database;

class TraceBatch
{
    public static function all(): array
    {
        $pdo = Database::connection();
        return $pdo->query("SELECT tb.*,
                CASE WHEN tb.batch_type = 'crop' THEN ct.name ELSE CONCAT(a.species, COALESCE(CONCAT(' - ', a.name), '')) END AS product_name,
                CASE WHEN tb.batch_type = 'crop' THEN f1.name ELSE f2.name END AS farm_name,
                (SELECT COUNT(*) FROM trace_qr_codes WHERE trace_batch_id = tb.id) AS has_qr
            FROM trace_batches tb
            LEFT JOIN crop_cycles cc ON cc.id = tb.crop_cycle_id
            LEFT JOIN crop_types ct ON ct.id = cc.crop_type_id
            LEFT JOIN plots p ON p.id = cc.plot_id
            LEFT JOIN blocks b ON b.id = p.block_id
            LEFT JOIN farms f1 ON f1.id = b.farm_id
            LEFT JOIN animals a ON a.id = tb.animal_id
            LEFT JOIN farms f2 ON f2.id = a.farm_id
            ORDER BY tb.created_at DESC")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM trace_batches WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $batch = $stmt->fetch();
        if (!$batch) {
            return null;
        }
        return self::hydrate($batch);
    }

    public static function findByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT tb.* FROM trace_batches tb
            JOIN trace_qr_codes q ON q.trace_batch_id = tb.id WHERE q.public_token = :token');
        $stmt->execute(['token' => $token]);
        $batch = $stmt->fetch();
        if (!$batch) {
            return null;
        }
        return self::hydrate($batch);
    }

    public static function findIdByCropCycle(int $cropCycleId): ?int
    {
        $stmt = Database::connection()->prepare('SELECT id FROM trace_batches WHERE crop_cycle_id = :id');
        $stmt->execute(['id' => $cropCycleId]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    public static function findIdByAnimal(int $animalId): ?int
    {
        $stmt = Database::connection()->prepare('SELECT id FROM trace_batches WHERE animal_id = :id');
        $stmt->execute(['id' => $animalId]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    public static function createForCropCycle(int $cropCycleId): int
    {
        $existing = self::findIdByCropCycle($cropCycleId);
        if ($existing) {
            return $existing;
        }

        $stmt = Database::connection()->prepare('SELECT batch_code FROM crop_cycles WHERE id = :id');
        $stmt->execute(['id' => $cropCycleId]);
        $batchCode = $stmt->fetchColumn();

        $pdo = Database::connection();
        $insert = $pdo->prepare("INSERT INTO trace_batches (batch_type, crop_cycle_id, batch_code, status) VALUES ('crop', :cycle_id, :code, 'active')");
        $insert->execute(['cycle_id' => $cropCycleId, 'code' => $batchCode]);
        return (int) $pdo->lastInsertId();
    }

    public static function createForAnimal(int $animalId): int
    {
        $existing = self::findIdByAnimal($animalId);
        if ($existing) {
            return $existing;
        }

        $stmt = Database::connection()->prepare('SELECT animal_code FROM animals WHERE id = :id');
        $stmt->execute(['id' => $animalId]);
        $batchCode = $stmt->fetchColumn();

        $pdo = Database::connection();
        $insert = $pdo->prepare("INSERT INTO trace_batches (batch_type, animal_id, batch_code, status) VALUES ('livestock', :animal_id, :code, 'active')");
        $insert->execute(['animal_id' => $animalId, 'code' => $batchCode]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateStatus(int $id, string $status): void
    {
        Database::connection()->prepare('UPDATE trace_batches SET status = :status WHERE id = :id')
            ->execute(['id' => $id, 'status' => $status]);
    }

    private static function hydrate(array $batch): array
    {
        if ($batch['batch_type'] === 'crop') {
            $batch['crop_cycle'] = CropCycle::find((int) $batch['crop_cycle_id']);
        } else {
            $batch['animal'] = Animal::find((int) $batch['animal_id']);
        }
        return $batch;
    }

    public static function timeline(array $batch): array
    {
        $events = $batch['batch_type'] === 'crop'
            ? self::cropTimeline((int) $batch['crop_cycle_id'])
            : self::livestockTimeline((int) $batch['animal_id']);

        $events = array_merge($events, self::journeyEvents((int) $batch['id']));

        usort($events, fn($a, $b) => strcmp((string) $a['date'], (string) $b['date']));
        return $events;
    }

    private static function cropTimeline(int $cropCycleId): array
    {
        $pdo = Database::connection();
        $events = [];

        $stmt = $pdo->prepare('SELECT * FROM crop_inputs WHERE crop_cycle_id = :id AND purchase_date IS NOT NULL');
        $stmt->execute(['id' => $cropCycleId]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = ['date' => $r['purchase_date'], 'category' => 'Procurement',
                'description' => ucfirst($r['input_type']) . ': ' . $r['quantity'] . ' ' . $r['unit'] . ' from ' . ($r['supplier_name'] ?: 'unknown supplier'),
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
        }

        $stmt = $pdo->prepare('SELECT * FROM nursery_records WHERE crop_cycle_id = :id');
        $stmt->execute(['id' => $cropCycleId]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = ['date' => $r['record_date'], 'category' => 'Nursery',
                'description' => "Germination {$r['germination_rate']}%, Survival {$r['survival_rate']}%" . ($r['treatment'] ? ", Treatment: {$r['treatment']}" : ''),
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
        }

        $stmt = $pdo->prepare('SELECT * FROM field_activities WHERE crop_cycle_id = :id');
        $stmt->execute(['id' => $cropCycleId]);
        foreach ($stmt->fetchAll() as $r) {
            $photo = ActivityPhoto::forActivity((int) $r['id']);
            $events[] = ['date' => $r['activity_date'], 'category' => 'Field Operation',
                'description' => ucfirst($r['activity_type']) . ($r['worker_name'] ? " by {$r['worker_name']}" : ''),
                'gps_lat' => $r['gps_lat'], 'gps_lng' => $r['gps_lng'], 'photo_path' => $photo[0]['file_path'] ?? null];
        }

        $stmt = $pdo->prepare('SELECT * FROM monitoring_records WHERE crop_cycle_id = :id');
        $stmt->execute(['id' => $cropCycleId]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = ['date' => $r['record_date'], 'category' => 'Monitoring',
                'description' => ucfirst($r['type']) . ($r['description'] ? ": {$r['description']}" : '') . ($r['severity'] ? " (severity: {$r['severity']})" : ''),
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => $r['photo_path']];
        }

        $stmt = $pdo->prepare('SELECT * FROM harvests WHERE crop_cycle_id = :id');
        $stmt->execute(['id' => $cropCycleId]);
        $harvests = $stmt->fetchAll();
        foreach ($harvests as $r) {
            $events[] = ['date' => $r['harvest_date'], 'category' => 'Harvest',
                'description' => "{$r['quantity']} {$r['unit']}" . ($r['quality_grade'] ? ", grade {$r['quality_grade']}" : ''),
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];

            $saleStmt = $pdo->prepare('SELECT * FROM crop_sales WHERE harvest_id = :id');
            $saleStmt->execute(['id' => $r['id']]);
            foreach ($saleStmt->fetchAll() as $sale) {
                $events[] = ['date' => $sale['sale_date'], 'category' => 'Sale',
                    'description' => "Sold {$sale['quantity']} to {$sale['buyer_name']} @ {$sale['unit_price']} = {$sale['revenue']}",
                    'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
            }
        }

        return $events;
    }

    private static function livestockTimeline(int $animalId): array
    {
        $pdo = Database::connection();
        $events = [];

        $stmt = $pdo->prepare('SELECT * FROM animal_vaccinations WHERE animal_id = :id');
        $stmt->execute(['id' => $animalId]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = ['date' => $r['date_administered'], 'category' => 'Vaccination',
                'description' => $r['vaccine_name'] . ($r['administered_by'] ? " by {$r['administered_by']}" : ''),
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
        }

        $stmt = $pdo->prepare('SELECT * FROM animal_feedings WHERE animal_id = :id');
        $stmt->execute(['id' => $animalId]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = ['date' => $r['feeding_date'], 'category' => 'Feeding',
                'description' => "{$r['feed_type']}: {$r['quantity']} {$r['unit']}",
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
        }

        $stmt = $pdo->prepare('SELECT * FROM animal_weights WHERE animal_id = :id');
        $stmt->execute(['id' => $animalId]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = ['date' => $r['recorded_date'], 'category' => 'Weight',
                'description' => "{$r['weight_kg']} kg", 'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
        }

        $stmt = $pdo->prepare('SELECT * FROM animal_treatments WHERE animal_id = :id');
        $stmt->execute(['id' => $animalId]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = ['date' => $r['treatment_date'], 'category' => 'Treatment',
                'description' => "{$r['condition_name']}: {$r['treatment']}" . ($r['administered_by'] ? " by {$r['administered_by']}" : ''),
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
        }

        $stmt = $pdo->prepare('SELECT * FROM animal_breeding WHERE animal_id = :id');
        $stmt->execute(['id' => $animalId]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = ['date' => $r['breeding_date'], 'category' => 'Breeding',
                'description' => 'Mate: ' . ($r['mate_description'] ?: 'unrecorded') . ", outcome: {$r['outcome']}",
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
        }

        $stmt = $pdo->prepare('SELECT * FROM animal_production WHERE animal_id = :id');
        $stmt->execute(['id' => $animalId]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = ['date' => $r['production_date'], 'category' => 'Production',
                'description' => "{$r['production_type']}: {$r['quantity']} {$r['unit']}",
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
        }

        $stmt = $pdo->prepare('SELECT * FROM animal_mortality WHERE animal_id = :id');
        $stmt->execute(['id' => $animalId]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = ['date' => $r['death_date'], 'category' => 'Mortality',
                'description' => 'Cause: ' . ($r['cause'] ?: 'unrecorded'),
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
        }

        $stmt = $pdo->prepare('SELECT * FROM animal_sales WHERE animal_id = :id');
        $stmt->execute(['id' => $animalId]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = ['date' => $r['sale_date'], 'category' => 'Sale',
                'description' => "Sold to {$r['buyer_name']} for {$r['sale_price']}",
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
        }

        return $events;
    }

    private static function journeyEvents(int $traceBatchId): array
    {
        $events = [];
        foreach (ProductJourney::forBatch($traceBatchId) as $r) {
            $events[] = ['date' => $r['stage_date'], 'category' => 'Chain of Custody',
                'description' => ucfirst($r['stage']) . ($r['location'] ? " at {$r['location']}" : '') . " (by {$r['responsible_user_name']})",
                'gps_lat' => null, 'gps_lng' => null, 'photo_path' => null];
        }
        return $events;
    }

    public static function profit(array $batch): array
    {
        $pdo = Database::connection();

        if ($batch['batch_type'] === 'crop') {
            $cropCycleId = (int) $batch['crop_cycle_id'];

            $revenueStmt = $pdo->prepare('SELECT COALESCE(SUM(cs.revenue), 0) FROM crop_sales cs
                JOIN harvests h ON h.id = cs.harvest_id WHERE h.crop_cycle_id = :id');
            $revenueStmt->execute(['id' => $cropCycleId]);
            $revenue = (float) $revenueStmt->fetchColumn();

            $inputCostStmt = $pdo->prepare('SELECT COALESCE(SUM(cost), 0) FROM crop_inputs WHERE crop_cycle_id = :id');
            $inputCostStmt->execute(['id' => $cropCycleId]);
            $inputCost = (float) $inputCostStmt->fetchColumn();

            $activityCostStmt = $pdo->prepare('SELECT COALESCE(SUM(cost), 0) FROM field_activities WHERE crop_cycle_id = :id');
            $activityCostStmt->execute(['id' => $cropCycleId]);
            $activityCost = (float) $activityCostStmt->fetchColumn();

            $cost = $inputCost + $activityCost;
        } else {
            $animalId = (int) $batch['animal_id'];

            $revenueStmt = $pdo->prepare('SELECT COALESCE(sale_price, 0) FROM animal_sales WHERE animal_id = :id');
            $revenueStmt->execute(['id' => $animalId]);
            $revenue = (float) $revenueStmt->fetchColumn();

            $feedCostStmt = $pdo->prepare('SELECT COALESCE(SUM(cost), 0) FROM animal_feedings WHERE animal_id = :id');
            $feedCostStmt->execute(['id' => $animalId]);
            $feedCost = (float) $feedCostStmt->fetchColumn();

            $treatmentCostStmt = $pdo->prepare('SELECT COALESCE(SUM(cost), 0) FROM animal_treatments WHERE animal_id = :id');
            $treatmentCostStmt->execute(['id' => $animalId]);
            $treatmentCost = (float) $treatmentCostStmt->fetchColumn();

            $cost = $feedCost + $treatmentCost;
        }

        return ['revenue' => $revenue, 'cost' => $cost, 'profit' => $revenue - $cost];
    }

    public static function gpsPoints(array $batch): array
    {
        $pdo = Database::connection();
        $points = [];

        if ($batch['batch_type'] === 'crop') {
            $cropCycleId = (int) $batch['crop_cycle_id'];

            $plotStmt = $pdo->prepare('SELECT p.gps_lat, p.gps_lng, p.plot_code FROM crop_cycles cc
                JOIN plots p ON p.id = cc.plot_id WHERE cc.id = :id AND p.gps_lat IS NOT NULL');
            $plotStmt->execute(['id' => $cropCycleId]);
            if ($plot = $plotStmt->fetch()) {
                $points[] = ['lat' => $plot['gps_lat'], 'lng' => $plot['gps_lng'], 'label' => 'Plot ' . $plot['plot_code']];
            }

            $actStmt = $pdo->prepare('SELECT gps_lat, gps_lng, activity_type, activity_date FROM field_activities
                WHERE crop_cycle_id = :id AND gps_lat IS NOT NULL ORDER BY activity_date');
            $actStmt->execute(['id' => $cropCycleId]);
            foreach ($actStmt->fetchAll() as $a) {
                $points[] = ['lat' => $a['gps_lat'], 'lng' => $a['gps_lng'], 'label' => ucfirst($a['activity_type']) . ' (' . $a['activity_date'] . ')'];
            }
        } else {
            $farmStmt = $pdo->prepare('SELECT f.gps_lat, f.gps_lng, f.name FROM animals a
                JOIN farms f ON f.id = a.farm_id WHERE a.id = :id AND f.gps_lat IS NOT NULL');
            $farmStmt->execute(['id' => (int) $batch['animal_id']]);
            if ($farm = $farmStmt->fetch()) {
                $points[] = ['lat' => $farm['gps_lat'], 'lng' => $farm['gps_lng'], 'label' => $farm['name']];
            }
        }

        return $points;
    }
}

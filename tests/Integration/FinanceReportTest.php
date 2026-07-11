<?php

namespace Tests\Integration;

use App\Models\FinanceReport;

class FinanceReportTest extends DatabaseTestCase
{
    public function test_summary_aggregates_crop_sale_revenue_and_input_cost(): void
    {
        $farmId = $this->createFarm();
        $blockId = $this->createBlock($farmId);
        $plotId = $this->createPlot($blockId);
        $cropTypeId = $this->createCropType();

        $this->pdo->prepare("INSERT INTO crop_cycles (batch_code, plot_id, crop_type_id, status) VALUES ('TEST-001', :plot_id, :crop_type_id, 'field')")
            ->execute(['plot_id' => $plotId, 'crop_type_id' => $cropTypeId]);
        $cycleId = (int) $this->pdo->lastInsertId();

        // Cost side: a crop input.
        $this->pdo->prepare("INSERT INTO crop_inputs (crop_cycle_id, input_type, cost, purchase_date) VALUES (:cycle_id, 'seed', 20000, '2026-01-05')")
            ->execute(['cycle_id' => $cycleId]);

        // Income side: harvest -> sale.
        $this->pdo->prepare("INSERT INTO harvests (crop_cycle_id, harvest_date, quantity) VALUES (:cycle_id, '2026-06-01', 100)")
            ->execute(['cycle_id' => $cycleId]);
        $harvestId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare("INSERT INTO crop_sales (harvest_id, buyer_name, quantity, unit_price, revenue, sale_date) VALUES (:harvest_id, 'Buyer X', 100, 1500, 150000, '2026-06-05')")
            ->execute(['harvest_id' => $harvestId]);

        $summary = FinanceReport::summary($farmId);

        $this->assertSame(150000.0, $summary['total_income']);
        $this->assertSame(20000.0, $summary['total_expenses']);
        $this->assertSame(130000.0, $summary['net_profit']);
    }

    public function test_summary_scoped_to_farm_excludes_other_farms(): void
    {
        $farmA = $this->createFarm('Farm A');
        $farmB = $this->createFarm('Farm B');

        $adminUserId = $this->findOrCreateAdminUser();

        $this->pdo->prepare("INSERT INTO income (farm_id, description, amount, income_date, recorded_by) VALUES (:farm_id, 'Manual income', 5000, '2026-06-01', :user_id)")
            ->execute(['farm_id' => $farmA, 'user_id' => $adminUserId]);
        $this->pdo->prepare("INSERT INTO income (farm_id, description, amount, income_date, recorded_by) VALUES (:farm_id, 'Manual income', 9999, '2026-06-01', :user_id)")
            ->execute(['farm_id' => $farmB, 'user_id' => $adminUserId]);

        $summary = FinanceReport::summary($farmA);

        $this->assertSame(5000.0, $summary['total_income']);
    }

    public function test_summary_respects_date_range_filter(): void
    {
        $farmId = $this->createFarm();
        $adminUserId = $this->findOrCreateAdminUser();

        $this->pdo->prepare("INSERT INTO income (farm_id, description, amount, income_date, recorded_by) VALUES (:farm_id, 'In range', 1000, '2026-06-15', :user_id)")
            ->execute(['farm_id' => $farmId, 'user_id' => $adminUserId]);
        $this->pdo->prepare("INSERT INTO income (farm_id, description, amount, income_date, recorded_by) VALUES (:farm_id, 'Out of range', 7000, '2025-01-01', :user_id)")
            ->execute(['farm_id' => $farmId, 'user_id' => $adminUserId]);

        $summary = FinanceReport::summary($farmId, '2026-06-01', '2026-06-30');

        $this->assertSame(1000.0, $summary['total_income']);
    }
}

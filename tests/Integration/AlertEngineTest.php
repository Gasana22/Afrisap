<?php

namespace Tests\Integration;

use App\Models\AlertEngine;

class AlertEngineTest extends DatabaseTestCase
{
    public function test_expired_input_is_detected(): void
    {
        $farmId = $this->createFarm();
        $blockId = $this->createBlock($farmId);
        $plotId = $this->createPlot($blockId);
        $cropTypeId = $this->createCropType();

        $this->pdo->prepare("INSERT INTO crop_cycles (batch_code, plot_id, crop_type_id, status) VALUES ('TEST-EXP', :plot_id, :crop_type_id, 'field')")
            ->execute(['plot_id' => $plotId, 'crop_type_id' => $cropTypeId]);
        $cycleId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare("INSERT INTO crop_inputs (crop_cycle_id, input_type, expiry_date) VALUES (:cycle_id, 'chemical', '2020-01-01')")
            ->execute(['cycle_id' => $cycleId]);

        $alerts = AlertEngine::expiredInputs();

        $this->assertNotEmpty($alerts);
        $this->assertSame('expired_input', $alerts[0]['type']);
    }

    public function test_future_expiry_does_not_trigger_alert(): void
    {
        $farmId = $this->createFarm();
        $blockId = $this->createBlock($farmId);
        $plotId = $this->createPlot($blockId);
        $cropTypeId = $this->createCropType();

        $this->pdo->prepare("INSERT INTO crop_cycles (batch_code, plot_id, crop_type_id, status) VALUES ('TEST-FUT', :plot_id, :crop_type_id, 'field')")
            ->execute(['plot_id' => $plotId, 'crop_type_id' => $cropTypeId]);
        $cycleId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare("INSERT INTO crop_inputs (crop_cycle_id, input_type, expiry_date) VALUES (:cycle_id, 'chemical', '2099-01-01')")
            ->execute(['cycle_id' => $cycleId]);

        $this->assertEmpty(AlertEngine::expiredInputs());
    }

    /**
     * Regression test: inventoryVariance() originally used HAVING against a base-table
     * column with no GROUP BY, which MySQL rejects outright ("Unknown column ... in
     * HAVING") -- it took down every page that renders the sidebar (which calls
     * AlertEngine::count()). This just needs to not throw and to return the right rows.
     */
    public function test_inventory_below_reorder_level_is_detected_without_sql_error(): void
    {
        $farmId = $this->createFarm();

        $this->pdo->exec("INSERT INTO inventory_items (name, category, reorder_level) VALUES ('Test Fertilizer', 'fertilizer', 100)");
        $itemId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare('INSERT INTO inventory_stock (item_id, farm_id, quantity_on_hand) VALUES (:item_id, :farm_id, 30)')
            ->execute(['item_id' => $itemId, 'farm_id' => $farmId]);

        $alerts = AlertEngine::inventoryVariance();

        $this->assertNotEmpty($alerts);
        $this->assertSame('inventory_variance', $alerts[0]['type']);
    }

    public function test_inventory_above_reorder_level_does_not_trigger_alert(): void
    {
        $farmId = $this->createFarm();

        $this->pdo->exec("INSERT INTO inventory_items (name, category, reorder_level) VALUES ('Well Stocked Item', 'seed', 10)");
        $itemId = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare('INSERT INTO inventory_stock (item_id, farm_id, quantity_on_hand) VALUES (:item_id, :farm_id, 500)')
            ->execute(['item_id' => $itemId, 'farm_id' => $farmId]);

        $this->assertEmpty(AlertEngine::inventoryVariance());
    }

    public function test_all_returns_combined_list_without_throwing(): void
    {
        // Smoke test: every sub-query in AlertEngine::all() runs cleanly against an
        // empty-ish dataset (this is what fires on every single page load via the sidebar).
        $this->assertIsArray(AlertEngine::all());
    }
}

<?php

namespace Tests\Integration;

use App\Models\CropCycle;

class CropCycleBatchCodeTest extends DatabaseTestCase
{
    public function test_batch_code_matches_srs_format(): void
    {
        $farmId = $this->createFarm('Masongora Cocoa Farm');
        $blockId = $this->createBlock($farmId, 'Block B');
        $plotId = $this->createPlot($blockId, 'P1');
        $cropTypeId = $this->createCropType('Cocoa');

        // farms.id is an AUTO_INCREMENT that does not roll back with the test transaction,
        // so the exact FARM number varies run to run -- read the code the app actually
        // assigned rather than assuming FARM01.
        $farmCode = $this->pdo->query("SELECT code FROM farms WHERE id = {$farmId}")->fetchColumn();

        $id = CropCycle::create([
            'plot_id' => $plotId,
            'crop_type_id' => $cropTypeId,
            'season_id' => null,
            'budget' => '',
            'expected_yield' => '',
            'start_date' => '2026-02-01',
            'status' => 'planning',
        ]);

        $cycle = CropCycle::find($id);

        // Shape matches the SRS example exactly: COCOA-2026-FARM01-BLOCKB-001
        $this->assertMatchesRegularExpression('/^COCOA-2026-FARM\d{2,}-BLOCKB-\d{3}$/', $cycle['batch_code']);
        $this->assertSame("COCOA-2026-{$farmCode}-BLOCKB-001", $cycle['batch_code']);
    }

    public function test_batch_code_sequence_increments_within_same_prefix(): void
    {
        $farmId = $this->createFarm();
        $blockId = $this->createBlock($farmId, 'Block A');
        $cropTypeId = $this->createCropType('Maize');

        $plot1 = $this->createPlot($blockId, 'P1');
        $plot2 = $this->createPlot($blockId, 'P2');

        $id1 = CropCycle::create(['plot_id' => $plot1, 'crop_type_id' => $cropTypeId, 'season_id' => null, 'budget' => '', 'expected_yield' => '', 'start_date' => '2026-01-01', 'status' => 'planning']);
        $id2 = CropCycle::create(['plot_id' => $plot2, 'crop_type_id' => $cropTypeId, 'season_id' => null, 'budget' => '', 'expected_yield' => '', 'start_date' => '2026-01-01', 'status' => 'planning']);

        $code1 = CropCycle::find($id1)['batch_code'];
        $code2 = CropCycle::find($id2)['batch_code'];

        $this->assertStringEndsWith('-001', $code1);
        $this->assertStringEndsWith('-002', $code2);
    }

    public function test_batch_code_uses_start_date_year(): void
    {
        $farmId = $this->createFarm();
        $blockId = $this->createBlock($farmId);
        $plotId = $this->createPlot($blockId);
        $cropTypeId = $this->createCropType('Coffee');

        $id = CropCycle::create([
            'plot_id' => $plotId,
            'crop_type_id' => $cropTypeId,
            'season_id' => null,
            'budget' => '',
            'expected_yield' => '',
            'start_date' => '2027-06-15',
            'status' => 'planning',
        ]);

        $this->assertStringContainsString('-2027-', CropCycle::find($id)['batch_code']);
    }
}

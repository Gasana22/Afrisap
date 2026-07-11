<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\FileUpload;
use App\Core\Validator;
use App\Models\ActivityPhoto;
use App\Models\CropCycle;
use App\Models\CropInput;
use App\Models\CropSale;
use App\Models\CropType;
use App\Models\FieldActivity;
use App\Models\Harvest;
use App\Models\MonitoringRecord;
use App\Models\NurseryRecord;
use App\Models\Plot;
use App\Models\Season;
use App\Models\TraceBatch;
use App\Models\YieldForecast;

class CropCycleController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $result = CropCycle::paginated($page, 25, Auth::organizationId());
        $this->view('crops/index', [
            'pageTitle' => 'Crop Management',
            'cycles' => $result['rows'],
            'page' => $page,
            'totalPages' => $result['totalPages'],
        ]);
    }

    /** The tenant-isolation gate for every action below: fetching another
     * organization's crop cycle (or one of its children, once we know its
     * cycle id) by id redirects to "not found" instead of exposing it. */
    private function requireOwnedCycle(int $cycleId): array
    {
        $cycle = CropCycle::findInOrganization($cycleId, Auth::organizationId());
        if (!$cycle) {
            $this->flash('danger', 'Crop cycle not found.');
            $this->redirect('/crops');
        }
        return $cycle;
    }

    public function create(): void
    {
        $this->view('crops/create', [
            'pageTitle' => 'Start Crop Cycle',
            'plots' => Plot::forOrganization(Auth::organizationId()),
            'cropTypes' => CropType::all(),
            'seasons' => Season::all(),
        ]);
    }

    public function store(): void
    {
        $validator = (new Validator($_POST))
            ->required('plot_id', 'Plot')
            ->required('crop_type_id', 'Crop type')
            ->numeric('budget', 'Budget')
            ->numeric('expected_yield', 'Expected yield');

        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/crops/create');
        }

        $plot = Plot::findWithContext((int) $this->input('plot_id'));
        if (!$plot || !Auth::organizationOwnsFarm((int) $plot['farm_id'])) {
            $this->flash('danger', 'Plot not found.');
            $this->redirect('/crops/create');
        }

        $id = CropCycle::create([
            'plot_id' => (int) $this->input('plot_id'),
            'crop_type_id' => (int) $this->input('crop_type_id'),
            'season_id' => $this->input('season_id') ?: null,
            'budget' => $this->input('budget', ''),
            'expected_yield' => $this->input('expected_yield', ''),
            'start_date' => $this->input('start_date'),
            'status' => 'planning',
        ]);

        AuditLogger::log('create', 'crop_cycles', (string) $id, null, CropCycle::find($id));
        TraceBatch::createForCropCycle($id);

        $this->flash('success', 'Crop cycle started with batch code ' . CropCycle::find($id)['batch_code'] . '.');
        $this->redirect('/crops/' . $id);
    }

    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $cycle = $this->requireOwnedCycle($id);

        $this->view('crops/show', [
            'pageTitle' => $cycle['batch_code'],
            'cycle' => $cycle,
            'inputs' => CropInput::forCycle($id),
            'nurseryRecords' => NurseryRecord::forCycle($id),
            'activities' => FieldActivity::forCycle($id),
            'monitoringRecords' => MonitoringRecord::forCycle($id),
            'forecasts' => YieldForecast::forCycle($id),
            'harvests' => Harvest::forCycle($id),
        ]);
    }

    public function edit(array $params): void
    {
        $cycle = $this->requireOwnedCycle((int) $params['id']);

        $this->view('crops/edit', ['pageTitle' => 'Edit ' . $cycle['batch_code'], 'cycle' => $cycle, 'seasons' => Season::all()]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $before = $this->requireOwnedCycle($id);

        $validator = (new Validator($_POST))->numeric('budget', 'Budget')->numeric('expected_yield', 'Expected yield')
            ->in('status', ['planning', 'procurement', 'nursery', 'field', 'monitoring', 'harvested', 'closed'], 'Status');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/crops/{$id}/edit");
        }

        CropCycle::update($id, [
            'season_id' => $this->input('season_id') ?: null,
            'budget' => $this->input('budget', ''),
            'expected_yield' => $this->input('expected_yield', ''),
            'start_date' => $this->input('start_date'),
            'status' => $this->input('status'),
        ]);

        AuditLogger::log('update', 'crop_cycles', (string) $id, $before, CropCycle::find($id));

        $this->flash('success', 'Crop cycle updated.');
        $this->redirect("/crops/{$id}");
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $before = $this->requireOwnedCycle($id);
        CropCycle::delete($id);
        AuditLogger::log('delete', 'crop_cycles', (string) $id, $before, null);

        $this->flash('success', 'Crop cycle deleted.');
        $this->redirect('/crops');
    }

    // --- Procurement (crop inputs) ---

    public function addInput(array $params): void
    {
        $cycleId = (int) $params['id'];
        $this->requireOwnedCycle($cycleId);
        $validator = (new Validator($_POST))->required('input_type', 'Input type');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/crops/{$cycleId}");
        }

        $inputId = CropInput::create($cycleId, [
            'input_type' => $this->input('input_type'),
            'supplier_name' => $this->input('supplier_name'),
            'quantity' => $this->input('quantity', ''),
            'unit' => $this->input('unit'),
            'cost' => $this->input('cost', ''),
            'purchase_date' => $this->input('purchase_date'),
            'expiry_date' => $this->input('expiry_date'),
        ]);
        AuditLogger::log('create', 'crop_inputs', (string) $inputId, null, CropInput::find($inputId));

        $this->flash('success', 'Input recorded.');
        $this->redirect("/crops/{$cycleId}");
    }

    public function deleteInput(array $params): void
    {
        $input = CropInput::find((int) $params['id']);
        if (!$input) {
            $this->redirect('/crops');
        }
        $this->requireOwnedCycle((int) $input['crop_cycle_id']);

        CropInput::delete((int) $params['id']);
        AuditLogger::log('delete', 'crop_inputs', $params['id'], $input, null);
        $this->redirect('/crops/' . $input['crop_cycle_id']);
    }

    // --- Nursery ---

    public function addNursery(array $params): void
    {
        $cycleId = (int) $params['id'];
        $this->requireOwnedCycle($cycleId);
        $validator = (new Validator($_POST))->required('record_date', 'Record date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/crops/{$cycleId}");
        }

        $recordId = NurseryRecord::create($cycleId, [
            'record_date' => $this->input('record_date'),
            'germination_rate' => $this->input('germination_rate', ''),
            'treatment' => $this->input('treatment'),
            'survival_rate' => $this->input('survival_rate', ''),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'nursery_records', (string) $recordId, null, NurseryRecord::find($recordId));

        $this->flash('success', 'Nursery record added.');
        $this->redirect("/crops/{$cycleId}");
    }

    public function deleteNursery(array $params): void
    {
        $record = NurseryRecord::find((int) $params['id']);
        if (!$record) {
            $this->redirect('/crops');
        }
        $this->requireOwnedCycle((int) $record['crop_cycle_id']);

        NurseryRecord::delete((int) $params['id']);
        AuditLogger::log('delete', 'nursery_records', $params['id'], $record, null);
        $this->redirect('/crops/' . $record['crop_cycle_id']);
    }

    // --- Field activities ---

    public function addActivity(array $params): void
    {
        $cycleId = (int) $params['id'];
        $this->requireOwnedCycle($cycleId);
        $validator = (new Validator($_POST))->required('activity_type', 'Activity type')->required('activity_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/crops/{$cycleId}");
        }

        $activityId = FieldActivity::create($cycleId, [
            'activity_type' => $this->input('activity_type'),
            'activity_date' => $this->input('activity_date'),
            'worker_name' => $this->input('worker_name'),
            'gps_lat' => $this->input('gps_lat', ''),
            'gps_lng' => $this->input('gps_lng', ''),
            'cost' => $this->input('cost', ''),
            'notes' => $this->input('notes'),
            'status' => $this->input('status', 'pending'),
        ]);

        try {
            $photoPath = FileUpload::storeImage('photo', 'activities');
            if ($photoPath) {
                ActivityPhoto::create($activityId, $photoPath);
            }
        } catch (\RuntimeException $e) {
            $this->flash('warning', 'Activity saved, but photo upload failed: ' . $e->getMessage());
        }

        AuditLogger::log('create', 'field_activities', (string) $activityId, null, FieldActivity::find($activityId));

        $this->flash('success', 'Field activity recorded.');
        $this->redirect("/crops/{$cycleId}");
    }

    public function updateActivityStatus(array $params): void
    {
        $activity = FieldActivity::find((int) $params['id']);
        if (!$activity) {
            $this->redirect('/crops');
        }
        $this->requireOwnedCycle((int) $activity['crop_cycle_id']);

        $status = $this->input('status');
        $validator = (new Validator(['status' => $status]))->in('status', ['pending', 'ongoing', 'completed'], 'Status');
        if ($validator->fails()) {
            $this->redirect('/crops/' . $activity['crop_cycle_id']);
        }

        FieldActivity::updateStatus((int) $params['id'], $status);
        AuditLogger::log('update_status', 'field_activities', $params['id'], $activity, ['status' => $status]);

        $this->flash('success', 'Activity status updated.');
        $this->redirect('/crops/' . $activity['crop_cycle_id']);
    }

    public function deleteActivity(array $params): void
    {
        $activity = FieldActivity::find((int) $params['id']);
        if (!$activity) {
            $this->redirect('/crops');
        }
        $this->requireOwnedCycle((int) $activity['crop_cycle_id']);

        FieldActivity::delete((int) $params['id']);
        AuditLogger::log('delete', 'field_activities', $params['id'], $activity, null);
        $this->redirect('/crops/' . $activity['crop_cycle_id']);
    }

    // --- Monitoring ---

    public function addMonitoring(array $params): void
    {
        $cycleId = (int) $params['id'];
        $this->requireOwnedCycle($cycleId);
        $validator = (new Validator($_POST))->required('type', 'Type')->required('record_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/crops/{$cycleId}");
        }

        $photoPath = null;
        try {
            $photoPath = FileUpload::storeImage('photo', 'monitoring');
        } catch (\RuntimeException $e) {
            $this->flash('warning', 'Monitoring record saved, but photo upload failed: ' . $e->getMessage());
        }

        $recordId = MonitoringRecord::create($cycleId, [
            'type' => $this->input('type'),
            'record_date' => $this->input('record_date'),
            'description' => $this->input('description'),
            'severity' => $this->input('severity'),
        ], $photoPath);

        AuditLogger::log('create', 'monitoring_records', (string) $recordId, null, MonitoringRecord::find($recordId));

        $this->flash('success', 'Monitoring record added.');
        $this->redirect("/crops/{$cycleId}");
    }

    public function deleteMonitoring(array $params): void
    {
        $record = MonitoringRecord::find((int) $params['id']);
        if (!$record) {
            $this->redirect('/crops');
        }
        $this->requireOwnedCycle((int) $record['crop_cycle_id']);

        MonitoringRecord::delete((int) $params['id']);
        AuditLogger::log('delete', 'monitoring_records', $params['id'], $record, null);
        $this->redirect('/crops/' . $record['crop_cycle_id']);
    }

    // --- Yield forecast ---

    public function addForecast(array $params): void
    {
        $cycleId = (int) $params['id'];
        $this->requireOwnedCycle($cycleId);
        $validator = (new Validator($_POST))->required('forecast_date', 'Date')->required('estimated_yield', 'Estimated yield')
            ->numeric('estimated_yield', 'Estimated yield');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/crops/{$cycleId}");
        }

        $forecastId = YieldForecast::create($cycleId, [
            'forecast_date' => $this->input('forecast_date'),
            'estimated_yield' => $this->input('estimated_yield'),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'yield_forecasts', (string) $forecastId, null, YieldForecast::find($forecastId));

        $this->flash('success', 'Yield forecast recorded.');
        $this->redirect("/crops/{$cycleId}");
    }

    public function deleteForecast(array $params): void
    {
        $forecast = YieldForecast::find((int) $params['id']);
        if (!$forecast) {
            $this->redirect('/crops');
        }
        $this->requireOwnedCycle((int) $forecast['crop_cycle_id']);

        YieldForecast::delete((int) $params['id']);
        AuditLogger::log('delete', 'yield_forecasts', $params['id'], $forecast, null);
        $this->redirect('/crops/' . $forecast['crop_cycle_id']);
    }

    // --- Harvest ---

    public function addHarvest(array $params): void
    {
        $cycleId = (int) $params['id'];
        $this->requireOwnedCycle($cycleId);
        $validator = (new Validator($_POST))->required('harvest_date', 'Date')->required('quantity', 'Quantity')->numeric('quantity', 'Quantity');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/crops/{$cycleId}");
        }

        $harvestId = Harvest::create($cycleId, [
            'harvest_date' => $this->input('harvest_date'),
            'quantity' => $this->input('quantity'),
            'unit' => $this->input('unit'),
            'quality_grade' => $this->input('quality_grade'),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'harvests', (string) $harvestId, null, Harvest::find($harvestId));

        CropCycle::update($cycleId, array_merge(CropCycle::find($cycleId), ['status' => 'harvested']));

        $this->flash('success', 'Harvest recorded.');
        $this->redirect("/crops/{$cycleId}");
    }

    public function deleteHarvest(array $params): void
    {
        $harvest = Harvest::find((int) $params['id']);
        if (!$harvest) {
            $this->redirect('/crops');
        }
        $this->requireOwnedCycle((int) $harvest['crop_cycle_id']);

        Harvest::delete((int) $params['id']);
        AuditLogger::log('delete', 'harvests', $params['id'], $harvest, null);
        $this->redirect('/crops/' . $harvest['crop_cycle_id']);
    }

    // --- Sales ---

    public function addSale(array $params): void
    {
        $harvestId = (int) $params['harvestId'];
        $harvest = Harvest::find($harvestId);
        if (!$harvest) {
            $this->redirect('/crops');
        }
        $this->requireOwnedCycle((int) $harvest['crop_cycle_id']);

        $validator = (new Validator($_POST))
            ->required('buyer_name', 'Buyer')
            ->required('quantity', 'Quantity')->numeric('quantity', 'Quantity')
            ->required('unit_price', 'Unit price')->numeric('unit_price', 'Unit price')
            ->required('sale_date', 'Sale date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/crops/' . $harvest['crop_cycle_id']);
        }

        $saleId = CropSale::create($harvestId, [
            'buyer_name' => $this->input('buyer_name'),
            'quantity' => $this->input('quantity'),
            'unit_price' => $this->input('unit_price'),
            'sale_date' => $this->input('sale_date'),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'crop_sales', (string) $saleId, null, ['harvest_id' => $harvestId]);

        $this->flash('success', 'Sale recorded.');
        $this->redirect('/crops/' . $harvest['crop_cycle_id']);
    }

    public function deleteSale(array $params): void
    {
        $sale = CropSale::find((int) $params['id']);
        if (!$sale) {
            $this->redirect('/crops');
        }
        $harvest = Harvest::find((int) $sale['harvest_id']);
        if (!$harvest) {
            $this->redirect('/crops');
        }
        $this->requireOwnedCycle((int) $harvest['crop_cycle_id']);

        CropSale::delete((int) $params['id']);
        AuditLogger::log('delete', 'crop_sales', $params['id'], $sale, null);
        $this->redirect('/crops/' . $harvest['crop_cycle_id']);
    }
}

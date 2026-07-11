<?php

namespace App\Controllers\Public;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Farm;

class AssetController extends Controller
{
    public function index(): void
    {
        $this->view('public/assets/index', ['pageTitle' => 'Assets', 'assets' => Asset::forOrganization(Auth::organizationId())]);
    }

    /** The tenant-isolation gate for every action below. */
    private function requireOwnedAsset(int $id): array
    {
        $asset = Asset::findInOrganization($id, Auth::organizationId());
        if (!$asset) {
            $this->flash('danger', 'Asset not found.');
            $this->redirect('/farm-assets');
        }
        return $asset;
    }

    public function create(): void
    {
        $this->view('public/assets/create', ['pageTitle' => 'Add Asset', 'farms' => Farm::forOrganization(Auth::organizationId())]);
    }

    public function store(): void
    {
        $validator = (new Validator($_POST))
            ->required('farm_id', 'Farm')
            ->required('type', 'Type')
            ->required('name', 'Name')
            ->numeric('purchase_value', 'Purchase value');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/farm-assets/create');
        }

        if (!Auth::organizationOwnsFarm((int) $this->input('farm_id'))) {
            $this->flash('danger', 'Farm not found.');
            $this->redirect('/farm-assets/create');
        }

        $id = Asset::create([
            'farm_id' => (int) $this->input('farm_id'),
            'type' => $this->input('type'),
            'name' => $this->input('name'),
            'identifier' => $this->input('identifier'),
            'purchase_date' => $this->input('purchase_date'),
            'purchase_value' => $this->input('purchase_value', ''),
            'status' => $this->input('status', 'active'),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'assets', (string) $id, null, Asset::find($id));

        $this->flash('success', 'Asset added.');
        $this->redirect('/farm-assets/' . $id);
    }

    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $asset = $this->requireOwnedAsset($id);

        $this->view('public/assets/show', [
            'pageTitle' => $asset['name'],
            'asset' => $asset,
            'maintenanceRecords' => AssetMaintenance::forAsset($id),
        ]);
    }

    public function edit(array $params): void
    {
        $asset = $this->requireOwnedAsset((int) $params['id']);

        $this->view('public/assets/edit', ['pageTitle' => 'Edit ' . $asset['name'], 'asset' => $asset]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $before = $this->requireOwnedAsset($id);

        $validator = (new Validator($_POST))->required('type', 'Type')->required('name', 'Name')->numeric('purchase_value', 'Purchase value');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/farm-assets/{$id}/edit");
        }

        Asset::update($id, [
            'type' => $this->input('type'),
            'name' => $this->input('name'),
            'identifier' => $this->input('identifier'),
            'purchase_date' => $this->input('purchase_date'),
            'purchase_value' => $this->input('purchase_value', ''),
            'status' => $this->input('status', 'active'),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('update', 'assets', (string) $id, $before, Asset::find($id));

        $this->flash('success', 'Asset updated.');
        $this->redirect("/farm-assets/{$id}");
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $before = $this->requireOwnedAsset($id);

        if (Asset::hasMaintenance($id)) {
            $this->flash('danger', 'This asset has maintenance history and cannot be deleted. Mark it retired instead.');
            $this->redirect("/farm-assets/{$id}");
        }

        Asset::delete($id);
        AuditLogger::log('delete', 'assets', (string) $id, $before, null);

        $this->flash('success', 'Asset deleted.');
        $this->redirect('/farm-assets');
    }

    public function addMaintenance(array $params): void
    {
        $assetId = (int) $params['id'];
        $this->requireOwnedAsset($assetId);
        $validator = (new Validator($_POST))
            ->required('maintenance_date', 'Date')
            ->required('description', 'Description')
            ->numeric('cost', 'Cost');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/farm-assets/{$assetId}");
        }

        $recordId = AssetMaintenance::create($assetId, [
            'maintenance_date' => $this->input('maintenance_date'),
            'description' => $this->input('description'),
            'cost' => $this->input('cost', ''),
            'next_due_date' => $this->input('next_due_date'),
            'performed_by' => $this->input('performed_by'),
        ]);
        AuditLogger::log('create', 'asset_maintenance', (string) $recordId, null, AssetMaintenance::find($recordId));

        $this->flash('success', 'Maintenance recorded.');
        $this->redirect("/farm-assets/{$assetId}");
    }
}

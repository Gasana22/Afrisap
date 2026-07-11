<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\Block;
use App\Models\Farm;
use App\Models\User;

class FarmController extends Controller
{
    public function index(): void
    {
        $this->view('farms/index', ['pageTitle' => 'Farm Structure', 'farms' => Farm::forOrganization(Auth::organizationId())]);
    }

    public function create(): void
    {
        $this->view('farms/create', [
            'pageTitle' => 'Add Farm',
            'owners' => User::forOrganization(Auth::organizationId()),
        ]);
    }

    public function store(): void
    {
        $validator = (new Validator($_POST))
            ->required('name', 'Farm name')
            ->numeric('size_hectares', 'Size')
            ->numeric('gps_lat', 'Latitude')
            ->numeric('gps_lng', 'Longitude');

        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/farms/create');
        }

        $id = Farm::create([
            'name' => $this->input('name'),
            'size_hectares' => $this->input('size_hectares', ''),
            'gps_lat' => $this->input('gps_lat', ''),
            'gps_lng' => $this->input('gps_lng', ''),
            'district' => $this->input('district'),
            'village' => $this->input('village'),
            'owner_id' => $this->input('owner_id') ?: null,
            'organization_id' => Auth::organizationId(),
            'status' => $this->input('status', 'active'),
        ]);

        AuditLogger::log('create', 'farms', (string) $id, null, Farm::find($id));

        $this->flash('success', 'Farm created.');
        $this->redirect('/farms/' . $id);
    }

    public function show(array $params): void
    {
        $farm = Farm::findInOrganization((int) $params['id'], Auth::organizationId());
        if (!$farm) {
            $this->flash('danger', 'Farm not found.');
            $this->redirect('/farms');
        }

        $blocks = Farm::blocks((int) $params['id']);
        $blocksWithPlots = array_map(fn($b) => $b + ['plots' => Block::plots((int) $b['id'])], $blocks);

        $this->view('farms/show', [
            'pageTitle' => $farm['name'],
            'farm' => $farm,
            'blocks' => $blocksWithPlots,
        ]);
    }

    public function edit(array $params): void
    {
        $farm = Farm::findInOrganization((int) $params['id'], Auth::organizationId());
        if (!$farm) {
            $this->flash('danger', 'Farm not found.');
            $this->redirect('/farms');
        }

        $this->view('farms/edit', [
            'pageTitle' => 'Edit ' . $farm['name'],
            'farm' => $farm,
            'owners' => User::forOrganization(Auth::organizationId()),
        ]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $before = Farm::findInOrganization($id, Auth::organizationId());
        if (!$before) {
            $this->flash('danger', 'Farm not found.');
            $this->redirect('/farms');
        }

        $validator = (new Validator($_POST))
            ->required('name', 'Farm name')
            ->numeric('size_hectares', 'Size')
            ->numeric('gps_lat', 'Latitude')
            ->numeric('gps_lng', 'Longitude');

        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/farms/{$id}/edit");
        }

        Farm::update($id, [
            'name' => $this->input('name'),
            'size_hectares' => $this->input('size_hectares', ''),
            'gps_lat' => $this->input('gps_lat', ''),
            'gps_lng' => $this->input('gps_lng', ''),
            'district' => $this->input('district'),
            'village' => $this->input('village'),
            'owner_id' => $this->input('owner_id') ?: null,
            'status' => $this->input('status', 'active'),
        ]);

        AuditLogger::log('update', 'farms', (string) $id, $before, Farm::find($id));

        $this->flash('success', 'Farm updated.');
        $this->redirect("/farms/{$id}");
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $before = Farm::findInOrganization($id, Auth::organizationId());
        if (!$before) {
            $this->flash('danger', 'Farm not found.');
            $this->redirect('/farms');
        }

        Farm::delete($id);
        AuditLogger::log('delete', 'farms', (string) $id, $before, null);

        $this->flash('success', 'Farm deleted.');
        $this->redirect('/farms');
    }
}

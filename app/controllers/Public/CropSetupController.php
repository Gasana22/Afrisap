<?php

namespace App\Controllers\Public;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\CropType;
use App\Models\Season;

class CropSetupController extends Controller
{
    public function index(): void
    {
        $this->view('public/crops/setup', [
            'pageTitle' => 'Crop Setup',
            'cropTypes' => CropType::all(),
            'seasons' => Season::all(),
        ]);
    }

    public function storeType(): void
    {
        $validator = (new Validator($_POST))->required('name', 'Crop type name');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/crops/setup');
        }

        $id = CropType::create($this->input('name'));
        AuditLogger::log('create', 'crop_types', (string) $id, null, CropType::find($id));

        $this->flash('success', 'Crop type added.');
        $this->redirect('/crops/setup');
    }

    public function destroyType(array $params): void
    {
        $id = (int) $params['id'];
        $before = CropType::find($id);
        CropType::delete($id);
        AuditLogger::log('delete', 'crop_types', (string) $id, $before, null);

        $this->flash('success', 'Crop type removed.');
        $this->redirect('/crops/setup');
    }

    public function storeSeason(): void
    {
        $validator = (new Validator($_POST))->required('name', 'Season name')->required('start_date', 'Start date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/crops/setup');
        }

        $id = Season::create([
            'name' => $this->input('name'),
            'start_date' => $this->input('start_date'),
            'end_date' => $this->input('end_date'),
        ]);
        AuditLogger::log('create', 'seasons', (string) $id, null, Season::find($id));

        $this->flash('success', 'Season added.');
        $this->redirect('/crops/setup');
    }

    public function destroySeason(array $params): void
    {
        $id = (int) $params['id'];
        $before = Season::find($id);
        Season::delete($id);
        AuditLogger::log('delete', 'seasons', (string) $id, $before, null);

        $this->flash('success', 'Season removed.');
        $this->redirect('/crops/setup');
    }
}

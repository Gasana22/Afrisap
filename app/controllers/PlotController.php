<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\Block;
use App\Models\Plot;

class PlotController extends Controller
{
    public function store(array $params): void
    {
        $blockId = (int) $params['blockId'];
        $block = Block::find($blockId);
        if (!$block || !Auth::organizationOwnsFarm((int) $block['farm_id'])) {
            $this->flash('danger', 'Block not found.');
            $this->redirect('/farms');
        }

        $validator = (new Validator($_POST))
            ->required('plot_code', 'Plot code')
            ->numeric('size_hectares', 'Size')
            ->numeric('gps_lat', 'Latitude')
            ->numeric('gps_lng', 'Longitude');

        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/farms/' . $block['farm_id']);
        }

        $id = Plot::create($blockId, [
            'plot_code' => $this->input('plot_code'),
            'size_hectares' => $this->input('size_hectares', ''),
            'gps_lat' => $this->input('gps_lat', ''),
            'gps_lng' => $this->input('gps_lng', ''),
            'current_crop_type' => $this->input('current_crop_type'),
        ]);

        AuditLogger::log('create', 'plots', (string) $id, null, Plot::find($id));

        $this->flash('success', 'Plot added.');
        $this->redirect('/farms/' . $block['farm_id']);
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $plot = Plot::find($id);
        if (!$plot) {
            $this->redirect('/farms');
        }

        $block = Block::find((int) $plot['block_id']);
        if (!$block || !Auth::organizationOwnsFarm((int) $block['farm_id'])) {
            $this->flash('danger', 'Plot not found.');
            $this->redirect('/farms');
        }

        Plot::delete($id);
        AuditLogger::log('delete', 'plots', (string) $id, $plot, null);

        $this->flash('success', 'Plot deleted.');
        $this->redirect('/farms/' . $block['farm_id']);
    }
}

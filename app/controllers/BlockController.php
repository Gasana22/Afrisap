<?php

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\Block;

class BlockController extends Controller
{
    public function store(array $params): void
    {
        $farmId = (int) $params['farmId'];

        $validator = (new Validator($_POST))->required('name', 'Block name');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/farms/{$farmId}");
        }

        $id = Block::create($farmId, $this->input('name'), $this->input('description'));
        AuditLogger::log('create', 'blocks', (string) $id, null, Block::find($id));

        $this->flash('success', 'Block added.');
        $this->redirect("/farms/{$farmId}");
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $block = Block::find($id);
        if (!$block) {
            $this->redirect('/farms');
        }

        Block::delete($id);
        AuditLogger::log('delete', 'blocks', (string) $id, $block, null);

        $this->flash('success', 'Block deleted.');
        $this->redirect('/farms/' . $block['farm_id']);
    }
}

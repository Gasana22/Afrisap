<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\Farm;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use RuntimeException;

class InventoryController extends Controller
{
    public function index(): void
    {
        $this->view('inventory/index', ['pageTitle' => 'Inventory', 'items' => InventoryItem::all()]);
    }

    public function create(): void
    {
        $this->view('inventory/create', ['pageTitle' => 'Add Inventory Item']);
    }

    public function store(): void
    {
        $validator = (new Validator($_POST))
            ->required('name', 'Item name')
            ->required('category', 'Category')
            ->numeric('reorder_level', 'Reorder level');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/inventory/create');
        }

        $id = InventoryItem::create([
            'name' => $this->input('name'),
            'category' => $this->input('category'),
            'unit' => $this->input('unit'),
            'reorder_level' => $this->input('reorder_level', ''),
        ]);
        AuditLogger::log('create', 'inventory_items', (string) $id, null, InventoryItem::find($id));

        $this->flash('success', 'Inventory item added.');
        $this->redirect('/inventory/' . $id);
    }

    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $item = InventoryItem::find($id);
        if (!$item) {
            $this->flash('danger', 'Item not found.');
            $this->redirect('/inventory');
        }

        $this->view('inventory/show', [
            'pageTitle' => $item['name'],
            'item' => $item,
            'stockByFarm' => StockMovement::stockByFarm($id),
            'movements' => StockMovement::forItem($id),
            'farms' => Farm::all(),
        ]);
    }

    public function edit(array $params): void
    {
        $item = InventoryItem::find((int) $params['id']);
        if (!$item) {
            $this->flash('danger', 'Item not found.');
            $this->redirect('/inventory');
        }

        $this->view('inventory/edit', ['pageTitle' => 'Edit ' . $item['name'], 'item' => $item]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $before = InventoryItem::find($id);
        if (!$before) {
            $this->flash('danger', 'Item not found.');
            $this->redirect('/inventory');
        }

        $validator = (new Validator($_POST))->required('name', 'Item name')->required('category', 'Category')->numeric('reorder_level', 'Reorder level');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/inventory/{$id}/edit");
        }

        InventoryItem::update($id, [
            'name' => $this->input('name'),
            'category' => $this->input('category'),
            'unit' => $this->input('unit'),
            'reorder_level' => $this->input('reorder_level', ''),
        ]);
        AuditLogger::log('update', 'inventory_items', (string) $id, $before, InventoryItem::find($id));

        $this->flash('success', 'Item updated.');
        $this->redirect("/inventory/{$id}");
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        if (InventoryItem::hasMovements($id)) {
            $this->flash('danger', 'This item has stock movement history and cannot be deleted.');
            $this->redirect("/inventory/{$id}");
        }

        $before = InventoryItem::find($id);
        InventoryItem::delete($id);
        AuditLogger::log('delete', 'inventory_items', (string) $id, $before, null);

        $this->flash('success', 'Item deleted.');
        $this->redirect('/inventory');
    }

    public function stockIn(array $params): void
    {
        $itemId = (int) $params['id'];
        $validator = (new Validator($_POST))
            ->required('farm_id', 'Farm')
            ->required('quantity', 'Quantity')->numeric('quantity', 'Quantity')
            ->required('movement_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/inventory/{$itemId}");
        }

        StockMovement::recordIn($itemId, (int) $this->input('farm_id'), (float) $this->input('quantity'), [
            'reference' => $this->input('reference'),
            'movement_date' => $this->input('movement_date'),
            'notes' => $this->input('notes'),
        ], Auth::id());

        AuditLogger::log('stock_in', 'stock_movements', $itemId, null, ['farm_id' => $this->input('farm_id'), 'quantity' => $this->input('quantity')]);

        $this->flash('success', 'Stock in recorded.');
        $this->redirect("/inventory/{$itemId}");
    }

    public function stockOut(array $params): void
    {
        $itemId = (int) $params['id'];
        $validator = (new Validator($_POST))
            ->required('farm_id', 'Farm')
            ->required('quantity', 'Quantity')->numeric('quantity', 'Quantity')
            ->required('movement_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/inventory/{$itemId}");
        }

        try {
            StockMovement::recordOut($itemId, (int) $this->input('farm_id'), (float) $this->input('quantity'), [
                'reference' => $this->input('reference'),
                'movement_date' => $this->input('movement_date'),
                'notes' => $this->input('notes'),
            ], Auth::id());
        } catch (RuntimeException $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect("/inventory/{$itemId}");
        }

        AuditLogger::log('stock_out', 'stock_movements', $itemId, null, ['farm_id' => $this->input('farm_id'), 'quantity' => $this->input('quantity')]);

        $this->flash('success', 'Stock out recorded.');
        $this->redirect("/inventory/{$itemId}");
    }

    public function transfer(array $params): void
    {
        $itemId = (int) $params['id'];
        $validator = (new Validator($_POST))
            ->required('from_farm_id', 'From farm')
            ->required('to_farm_id', 'To farm')
            ->required('quantity', 'Quantity')->numeric('quantity', 'Quantity')
            ->required('movement_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/inventory/{$itemId}");
        }

        if ($this->input('from_farm_id') === $this->input('to_farm_id')) {
            $this->flash('danger', 'Source and destination farm must be different.');
            $this->redirect("/inventory/{$itemId}");
        }

        try {
            StockMovement::recordTransfer($itemId, (int) $this->input('from_farm_id'), (int) $this->input('to_farm_id'), (float) $this->input('quantity'), [
                'reference' => $this->input('reference'),
                'movement_date' => $this->input('movement_date'),
                'notes' => $this->input('notes'),
            ], Auth::id());
        } catch (RuntimeException $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect("/inventory/{$itemId}");
        }

        AuditLogger::log('stock_transfer', 'stock_movements', $itemId, null, [
            'from_farm_id' => $this->input('from_farm_id'), 'to_farm_id' => $this->input('to_farm_id'), 'quantity' => $this->input('quantity'),
        ]);

        $this->flash('success', 'Stock transfer recorded.');
        $this->redirect("/inventory/{$itemId}");
    }
}

<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\Delivery;
use App\Models\Farm;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;

class PurchaseOrderController extends Controller
{
    public function index(): void
    {
        $this->view('procurement/orders/index', ['pageTitle' => 'Purchase Orders', 'orders' => PurchaseOrder::all()]);
    }

    public function create(): void
    {
        $this->view('procurement/orders/create', [
            'pageTitle' => 'Create Purchase Order',
            'suppliers' => Supplier::all(),
            'farms' => Farm::all(),
        ]);
    }

    public function store(): void
    {
        $validator = (new Validator($_POST))
            ->required('supplier_id', 'Supplier')
            ->required('farm_id', 'Farm')
            ->required('order_date', 'Order date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/purchase-orders/create');
        }

        $itemNames = $this->input('item_name', []);
        $quantities = $this->input('quantity', []);
        $units = $this->input('unit', []);
        $unitCosts = $this->input('unit_cost', []);

        $items = [];
        foreach ($itemNames as $i => $name) {
            if (trim((string) $name) === '') {
                continue;
            }
            $items[] = [
                'item_name' => $name,
                'quantity' => $quantities[$i] ?? 0,
                'unit' => $units[$i] ?? null,
                'unit_cost' => $unitCosts[$i] ?? 0,
            ];
        }

        if (empty($items)) {
            $this->flash('danger', 'Add at least one line item.');
            $this->redirect('/purchase-orders/create');
        }

        $id = PurchaseOrder::create([
            'supplier_id' => (int) $this->input('supplier_id'),
            'farm_id' => (int) $this->input('farm_id'),
            'order_date' => $this->input('order_date'),
            'expected_date' => $this->input('expected_date'),
            'notes' => $this->input('notes'),
        ], $items, Auth::id());

        AuditLogger::log('create', 'purchase_orders', (string) $id, null, PurchaseOrder::find($id));

        $this->flash('success', 'Purchase order created.');
        $this->redirect('/purchase-orders/' . $id);
    }

    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $order = PurchaseOrder::find($id);
        if (!$order) {
            $this->flash('danger', 'Purchase order not found.');
            $this->redirect('/purchase-orders');
        }

        $this->view('procurement/orders/show', [
            'pageTitle' => 'PO #' . $id,
            'order' => $order,
            'items' => PurchaseOrder::items($id),
            'deliveries' => Delivery::forPurchaseOrder($id),
            'payments' => SupplierPayment::forPurchaseOrder($id),
        ]);
    }

    public function updateStatus(array $params): void
    {
        $id = (int) $params['id'];
        $order = PurchaseOrder::find($id);
        if (!$order) {
            $this->redirect('/purchase-orders');
        }

        $status = $this->input('status');
        $validator = (new Validator(['status' => $status]))
            ->in('status', ['draft', 'ordered', 'partially_received', 'received', 'cancelled'], 'Status');
        if ($validator->fails()) {
            $this->redirect("/purchase-orders/{$id}");
        }

        PurchaseOrder::updateStatus($id, $status);
        AuditLogger::log('update_status', 'purchase_orders', (string) $id, $order, ['status' => $status]);

        $this->flash('success', 'Purchase order status updated.');
        $this->redirect("/purchase-orders/{$id}");
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $order = PurchaseOrder::find($id);
        if (!$order) {
            $this->redirect('/purchase-orders');
        }

        if ($order['status'] !== 'draft') {
            $this->flash('danger', 'Only a draft purchase order can be deleted.');
            $this->redirect("/purchase-orders/{$id}");
        }

        PurchaseOrder::delete($id);
        AuditLogger::log('delete', 'purchase_orders', (string) $id, $order, null);

        $this->flash('success', 'Purchase order deleted.');
        $this->redirect('/purchase-orders');
    }

    public function addDelivery(array $params): void
    {
        $poId = (int) $params['id'];
        $validator = (new Validator($_POST))->required('delivery_date', 'Delivery date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/purchase-orders/{$poId}");
        }

        $deliveryId = Delivery::create($poId, [
            'delivery_date' => $this->input('delivery_date'),
            'received_by' => $this->input('received_by'),
            'condition_notes' => $this->input('condition_notes'),
        ]);
        AuditLogger::log('create', 'deliveries', (string) $deliveryId, null, ['purchase_order_id' => $poId]);

        $this->flash('success', 'Delivery recorded.');
        $this->redirect("/purchase-orders/{$poId}");
    }

    public function addPayment(array $params): void
    {
        $poId = (int) $params['id'];
        $validator = (new Validator($_POST))
            ->required('amount', 'Amount')->numeric('amount', 'Amount')
            ->required('payment_date', 'Payment date')
            ->required('method', 'Method');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/purchase-orders/{$poId}");
        }

        $paymentId = SupplierPayment::create($poId, [
            'amount' => $this->input('amount'),
            'payment_date' => $this->input('payment_date'),
            'method' => $this->input('method'),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'supplier_payments', (string) $paymentId, null, ['purchase_order_id' => $poId]);

        $this->flash('success', 'Payment recorded.');
        $this->redirect("/purchase-orders/{$poId}");
    }
}

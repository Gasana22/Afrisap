<?php
require_once __DIR__ . '/includes/auth-check.php';

$poId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT po.*, f.name AS farm_name, f.organization_id, s.name AS supplier_name
     FROM purchase_orders po JOIN farms f ON f.id = po.farm_id JOIN suppliers s ON s.id = po.supplier_id
     WHERE po.id = :id'
);
$stmt->execute(['id' => $poId]);
$po = $stmt->fetch();

if (!$po || (!is_platform_user() && (int) $po['organization_id'] !== (int) current_organization_id())) {
    http_response_code(404);
    exit('404 — purchase order not found.');
}

$error = flash('error');
$success = flash('success');

$statusLabels = [
    'draft' => 'Draft', 'ordered' => 'Ordered', 'partially_received' => 'Partially received',
    'received' => 'Received', 'cancelled' => 'Cancelled',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('procurement.manage');
    $action = $_POST['action'] ?? '';

    if ($action === 'add_item') {
        db()->prepare(
            'INSERT INTO purchase_order_items (purchase_order_id, item_name, quantity, unit, unit_cost)
             VALUES (:po, :name, :qty, :unit, :cost)'
        )->execute([
            'po' => $poId,
            'name' => trim($_POST['item_name'] ?? ''),
            'qty' => (float) ($_POST['quantity'] ?? 0),
            'unit' => trim($_POST['unit'] ?? '') ?: null,
            'cost' => (float) ($_POST['unit_cost'] ?? 0),
        ]);
        flash('success', 'Item added.');
        redirect('/admin/purchase-order-view.php?id=' . $poId);
    }

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        if (isset($statusLabels[$status])) {
            db()->prepare('UPDATE purchase_orders SET status = :status WHERE id = :id')
                ->execute(['status' => $status, 'id' => $poId]);
            flash('success', 'Status updated.');
        }
        redirect('/admin/purchase-order-view.php?id=' . $poId);
    }

    if ($action === 'add_delivery') {
        db()->prepare(
            'INSERT INTO deliveries (purchase_order_id, delivery_date, received_by, condition_notes)
             VALUES (:po, :date, :received_by, :notes)'
        )->execute([
            'po' => $poId,
            'date' => ($_POST['delivery_date'] ?? '') ?: date('Y-m-d'),
            'received_by' => trim($_POST['received_by'] ?? '') ?: null,
            'notes' => trim($_POST['condition_notes'] ?? '') ?: null,
        ]);
        flash('success', 'Delivery logged.');
        redirect('/admin/purchase-order-view.php?id=' . $poId);
    }

    if ($action === 'add_payment') {
        db()->prepare(
            'INSERT INTO supplier_payments (purchase_order_id, amount, payment_date, method, notes)
             VALUES (:po, :amount, :date, :method, :notes)'
        )->execute([
            'po' => $poId,
            'amount' => (float) ($_POST['amount'] ?? 0),
            'date' => ($_POST['payment_date'] ?? '') ?: date('Y-m-d'),
            'method' => $_POST['method'] ?? 'cash',
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Payment recorded.');
        redirect('/admin/purchase-order-view.php?id=' . $poId);
    }
}

$itemsStmt = db()->prepare('SELECT * FROM purchase_order_items WHERE purchase_order_id = :po');
$itemsStmt->execute(['po' => $poId]);
$items = $itemsStmt->fetchAll();

$deliveriesStmt = db()->prepare('SELECT * FROM deliveries WHERE purchase_order_id = :po ORDER BY delivery_date DESC');
$deliveriesStmt->execute(['po' => $poId]);
$deliveries = $deliveriesStmt->fetchAll();

$paymentsStmt = db()->prepare('SELECT * FROM supplier_payments WHERE purchase_order_id = :po ORDER BY payment_date DESC');
$paymentsStmt->execute(['po' => $poId]);
$payments = $paymentsStmt->fetchAll();

$itemsTotal = array_sum(array_map(fn ($i) => $i['quantity'] * $i['unit_cost'], $items));
$paidTotal = array_sum(array_column($payments, 'amount'));

$pageTitle = 'PO #' . $poId;
$activePage = 'purchase-orders';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/purchase-orders.php">&larr; All purchase orders</a></p>
<h1><?= e($po['supplier_name']) ?> — <?= e($po['farm_name']) ?></h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Status</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/purchase-order-view.php?id=<?= $poId ?>" style="display:flex; gap:0.5rem;">
        <input type="hidden" name="action" value="update_status">
        <select name="status" style="flex:1; padding:0.5rem;">
            <?php foreach ($statusLabels as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $po['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Update</button>
    </form>
    <p class="muted" style="margin-bottom:0;">Items total: <?= number_format($itemsTotal, 2) ?> · Paid: <?= number_format($paidTotal, 2) ?> · Balance: <?= number_format($itemsTotal - $paidTotal, 2) ?></p>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Line items</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Item</th><th>Quantity</th><th>Unit cost</th><th>Line total</th></tr></thead>
        <tbody>
            <?php if (!$items): ?><tr><td colspan="4">No items yet.</td></tr><?php endif; ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['item_name']) ?></td>
                    <td><?= e($item['quantity'] . ' ' . ($item['unit'] ?? '')) ?></td>
                    <td><?= e($item['unit_cost']) ?></td>
                    <td><?= number_format($item['quantity'] * $item['unit_cost'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Add a line item</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/purchase-order-view.php?id=<?= $poId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_item">
            <label>Item name</label>
            <input type="text" name="item_name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Quantity</label>
            <input type="number" step="0.01" name="quantity" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Unit</label>
            <input type="text" name="unit" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Unit cost</label>
            <input type="number" step="0.01" name="unit_cost" required style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Add</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Deliveries</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Date</th><th>Received by</th><th>Condition notes</th></tr></thead>
        <tbody>
            <?php if (!$deliveries): ?><tr><td colspan="3">None yet.</td></tr><?php endif; ?>
            <?php foreach ($deliveries as $d): ?>
                <tr><td><?= e($d['delivery_date']) ?></td><td><?= e($d['received_by'] ?? '—') ?></td><td><?= e($d['condition_notes'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Log a delivery</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/purchase-order-view.php?id=<?= $poId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_delivery">
            <label>Date</label>
            <input type="date" name="delivery_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Received by</label>
            <input type="text" name="received_by" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Condition notes</label>
            <input type="text" name="condition_notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Payments</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Date</th><th>Amount</th><th>Method</th></tr></thead>
        <tbody>
            <?php if (!$payments): ?><tr><td colspan="3">None yet.</td></tr><?php endif; ?>
            <?php foreach ($payments as $p): ?>
                <tr><td><?= e($p['payment_date']) ?></td><td><?= e($p['amount']) ?></td><td><?= e($p['method']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Record a payment</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/purchase-order-view.php?id=<?= $poId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_payment">
            <label>Amount</label>
            <input type="number" step="0.01" name="amount" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Date</label>
            <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Method</label>
            <select name="method" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option>
                <option value="mobile_money">Mobile money</option><option value="cheque">Cheque</option>
            </select>
            <label>Notes</label>
            <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

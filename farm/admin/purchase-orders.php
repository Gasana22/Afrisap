<?php
require_once __DIR__ . '/includes/auth-check.php';
require_tenant_user();

$farmIds = visible_farm_ids();
$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('procurement.manage');

    $farmId = (int) ($_POST['farm_id'] ?? 0);
    $supplierId = (int) ($_POST['supplier_id'] ?? 0);
    $orderDate = ($_POST['order_date'] ?? '') ?: date('Y-m-d');
    $expectedDate = ($_POST['expected_date'] ?? '') ?: null;

    if (!in_array($farmId, $farmIds, true) || !$supplierId) {
        $error = 'A valid farm and supplier are required.';
    } else {
        db()->prepare(
            'INSERT INTO purchase_orders (supplier_id, farm_id, order_date, expected_date, status, notes, created_by)
             VALUES (:supplier, :farm, :order_date, :expected, "draft", :notes, :by)'
        )->execute([
            'supplier' => $supplierId,
            'farm' => $farmId,
            'order_date' => $orderDate,
            'expected' => $expectedDate,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'by' => current_user()['id'],
        ]);
        flash('success', 'Purchase order created — add line items on the next screen.');
        redirect('/admin/purchase-order-view.php?id=' . db()->lastInsertId());
    }
}

$purchaseOrders = [];
$farms = [];
$suppliers = [];

if ($farmIds) {
    $stmt = db()->prepare(
        'SELECT po.*, f.name AS farm_name, s.name AS supplier_name FROM purchase_orders po
         JOIN farms f ON f.id = po.farm_id JOIN suppliers s ON s.id = po.supplier_id
         WHERE po.farm_id IN (' . in_placeholders($farmIds) . ') ORDER BY po.created_at DESC'
    );
    $stmt->execute($farmIds);
    $purchaseOrders = $stmt->fetchAll();

    $farmStmt = db()->prepare('SELECT id, name FROM farms WHERE id IN (' . in_placeholders($farmIds) . ') ORDER BY name');
    $farmStmt->execute($farmIds);
    $farms = $farmStmt->fetchAll();
}

$supplierStmt = db()->prepare('SELECT id, name FROM suppliers WHERE organization_id = :org ORDER BY name');
$supplierStmt->execute(['org' => current_organization_id()]);
$suppliers = $supplierStmt->fetchAll();

$statusLabels = [
    'draft' => 'Draft', 'ordered' => 'Ordered', 'partially_received' => 'Partially received',
    'received' => 'Received', 'cancelled' => 'Cancelled',
];

$pageTitle = 'Purchase Orders';
$activePage = 'purchase-orders';
require __DIR__ . '/includes/header.php';
?>

<h1>Purchase Orders</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<?php if (!$suppliers): ?>
    <div class="alert alert-error">No suppliers yet. <a href="<?= BASE_URL ?>/admin/suppliers.php">Add one</a> first.</div>
<?php elseif (!$farms): ?>
    <div class="alert alert-error">No farms available yet.</div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Create a purchase order</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/purchase-orders.php">
        <label>Farm</label>
        <select name="farm_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="">Select…</option>
            <?php foreach ($farms as $f): ?><option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?>
        </select>
        <label>Supplier</label>
        <select name="supplier_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="">Select…</option>
            <?php foreach ($suppliers as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
        </select>
        <label>Order date</label>
        <input type="date" name="order_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Expected date</label>
        <input type="date" name="expected_date" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Notes</label>
        <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
        <button type="submit" class="btn">Create &amp; add items</button>
    </form>
</div>

<table>
    <thead><tr><th>Farm</th><th>Supplier</th><th>Order date</th><th>Status</th><th></th></tr></thead>
    <tbody>
        <?php if (!$purchaseOrders): ?><tr><td colspan="5">No purchase orders yet.</td></tr><?php endif; ?>
        <?php foreach ($purchaseOrders as $po): ?>
            <tr>
                <td><?= e($po['farm_name']) ?></td>
                <td><?= e($po['supplier_name']) ?></td>
                <td><?= e($po['order_date']) ?></td>
                <td><?= e($statusLabels[$po['status']] ?? $po['status']) ?></td>
                <td><a href="<?= BASE_URL ?>/admin/purchase-order-view.php?id=<?= (int) $po['id'] ?>">Manage</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>

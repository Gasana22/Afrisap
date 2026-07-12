<?php
require_once __DIR__ . '/includes/auth-check.php';

function adjust_stock(int $itemId, int $farmId, float $delta): void
{
    db()->prepare(
        'INSERT INTO inventory_stock (item_id, farm_id, quantity_on_hand) VALUES (:item, :farm, :delta)
         ON DUPLICATE KEY UPDATE quantity_on_hand = quantity_on_hand + VALUES(quantity_on_hand)'
    )->execute(['item' => $itemId, 'farm' => $farmId, 'delta' => $delta]);
}

function current_stock(int $itemId, int $farmId): float
{
    $stmt = db()->prepare('SELECT quantity_on_hand FROM inventory_stock WHERE item_id = :item AND farm_id = :farm');
    $stmt->execute(['item' => $itemId, 'farm' => $farmId]);
    return (float) ($stmt->fetchColumn() ?: 0);
}

$farmIds = visible_farm_ids();
$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('inventory.manage');
    $action = $_POST['action'] ?? '';

    if ($action === 'add_item') {
        $organizationId = is_platform_user() ? (int) ($_POST['organization_id'] ?? 0) : current_organization_id();
        $name = trim($_POST['name'] ?? '');

        if (!$organizationId || $name === '') {
            $error = 'Organization and name are required.';
        } else {
            db()->prepare(
                'INSERT INTO inventory_items (organization_id, name, category, unit, reorder_level)
                 VALUES (:org, :name, :category, :unit, :reorder)'
            )->execute([
                'org' => $organizationId,
                'name' => $name,
                'category' => $_POST['category'] ?? 'other',
                'unit' => trim($_POST['unit'] ?? '') ?: null,
                'reorder' => ($_POST['reorder_level'] ?? '') !== '' ? (float) $_POST['reorder_level'] : null,
            ]);
            flash('success', 'Item added.');
            redirect('/admin/inventory.php');
        }
    }

    if ($action === 'record_movement') {
        $itemId = (int) ($_POST['item_id'] ?? 0);
        $farmId = (int) ($_POST['farm_id'] ?? 0);
        $type = $_POST['type'] ?? '';
        $quantity = (float) ($_POST['quantity'] ?? 0);
        $relatedFarmId = ($_POST['related_farm_id'] ?? '') !== '' ? (int) $_POST['related_farm_id'] : null;
        $reference = trim($_POST['reference'] ?? '') ?: null;
        $notes = trim($_POST['notes'] ?? '') ?: null;
        $movementDate = ($_POST['movement_date'] ?? '') ?: date('Y-m-d');

        if (!in_array($farmId, $farmIds, true) || $quantity <= 0 || !in_array($type, ['in', 'out', 'transfer'], true)) {
            $error = 'A valid farm, quantity, and movement type are required.';
        } elseif ($type === 'transfer' && (!$relatedFarmId || !in_array($relatedFarmId, $farmIds, true) || $relatedFarmId === $farmId)) {
            $error = 'Pick a different destination farm to transfer to.';
        } elseif ($type === 'out' && current_stock($itemId, $farmId) < $quantity) {
            $error = 'Not enough stock on hand for that quantity.';
        } elseif ($type === 'transfer' && current_stock($itemId, $farmId) < $quantity) {
            $error = 'Not enough stock on hand at the source farm.';
        } else {
            $performedBy = current_user()['id'];

            if ($type === 'in') {
                db()->prepare(
                    'INSERT INTO stock_movements (item_id, farm_id, type, quantity, reference, performed_by, movement_date, notes)
                     VALUES (:item, :farm, "in", :qty, :ref, :by, :date, :notes)'
                )->execute(['item' => $itemId, 'farm' => $farmId, 'qty' => $quantity, 'ref' => $reference, 'by' => $performedBy, 'date' => $movementDate, 'notes' => $notes]);
                adjust_stock($itemId, $farmId, $quantity);
            } elseif ($type === 'out') {
                db()->prepare(
                    'INSERT INTO stock_movements (item_id, farm_id, type, quantity, reference, performed_by, movement_date, notes)
                     VALUES (:item, :farm, "out", :qty, :ref, :by, :date, :notes)'
                )->execute(['item' => $itemId, 'farm' => $farmId, 'qty' => $quantity, 'ref' => $reference, 'by' => $performedBy, 'date' => $movementDate, 'notes' => $notes]);
                adjust_stock($itemId, $farmId, -$quantity);
            } else {
                db()->prepare(
                    'INSERT INTO stock_movements (item_id, farm_id, type, quantity, related_farm_id, reference, performed_by, movement_date, notes)
                     VALUES (:item, :farm, "transfer_out", :qty, :related, :ref, :by, :date, :notes)'
                )->execute(['item' => $itemId, 'farm' => $farmId, 'qty' => $quantity, 'related' => $relatedFarmId, 'ref' => $reference, 'by' => $performedBy, 'date' => $movementDate, 'notes' => $notes]);
                adjust_stock($itemId, $farmId, -$quantity);

                db()->prepare(
                    'INSERT INTO stock_movements (item_id, farm_id, type, quantity, related_farm_id, reference, performed_by, movement_date, notes)
                     VALUES (:item, :farm, "transfer_in", :qty, :related, :ref, :by, :date, :notes)'
                )->execute(['item' => $itemId, 'farm' => $relatedFarmId, 'qty' => $quantity, 'related' => $farmId, 'ref' => $reference, 'by' => $performedBy, 'date' => $movementDate, 'notes' => $notes]);
                adjust_stock($itemId, $relatedFarmId, $quantity);
            }

            // Low-stock check applies to the farm stock decreased (out, or transfer's source).
            if ($type !== 'in') {
                $itemRow = db()->prepare('SELECT name, reorder_level, organization_id FROM inventory_items WHERE id = :id');
                $itemRow->execute(['id' => $itemId]);
                $itemInfo = $itemRow->fetch();
                if ($itemInfo && $itemInfo['reorder_level'] !== null && current_stock($itemId, $farmId) < (float) $itemInfo['reorder_level']) {
                    notify_organization($itemInfo['organization_id'], 'Low stock', $itemInfo['name'] . ' is below its reorder level.', 'warning', BASE_URL . '/admin/inventory.php');
                }
            }

            audit_log($type, 'stock_movements', null, null, ['item_id' => $itemId, 'farm_id' => $farmId, 'quantity' => $quantity]);
            flash('success', 'Stock movement recorded.');
            redirect('/admin/inventory.php');
        }
    }
}

if (is_platform_user()) {
    $items = db()->query('SELECT i.*, o.name AS organization_name FROM inventory_items i LEFT JOIN organizations o ON o.id = i.organization_id ORDER BY i.name')->fetchAll();
    $organizations = db()->query('SELECT id, name FROM organizations ORDER BY name')->fetchAll();
} else {
    $stmt = db()->prepare('SELECT * FROM inventory_items WHERE organization_id = :org ORDER BY name');
    $stmt->execute(['org' => current_organization_id()]);
    $items = $stmt->fetchAll();
    $organizations = [];
}

$farms = [];
$stockByItemFarm = [];
if ($farmIds) {
    $farmStmt = db()->prepare('SELECT id, name FROM farms WHERE id IN (' . in_placeholders($farmIds) . ') ORDER BY name');
    $farmStmt->execute($farmIds);
    $farms = $farmStmt->fetchAll();

    $stockStmt = db()->prepare('SELECT * FROM inventory_stock WHERE farm_id IN (' . in_placeholders($farmIds) . ')');
    $stockStmt->execute($farmIds);
    foreach ($stockStmt->fetchAll() as $row) {
        $stockByItemFarm[$row['item_id']][$row['farm_id']] = $row['quantity_on_hand'];
    }
}

$pageTitle = 'Inventory';
$activePage = 'inventory';
require __DIR__ . '/includes/header.php';
?>

<h1>Inventory</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Add an item</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/inventory.php">
        <input type="hidden" name="action" value="add_item">
        <?php if (is_platform_user()): ?>
            <label>Organization</label>
            <select name="organization_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="">Select…</option>
                <?php foreach ($organizations as $org): ?><option value="<?= (int) $org['id'] ?>"><?= e($org['name']) ?></option><?php endforeach; ?>
            </select>
        <?php endif; ?>
        <label>Name</label>
        <input type="text" name="name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Category</label>
        <select name="category" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="seed">Seed</option><option value="fertilizer">Fertilizer</option><option value="chemical">Chemical</option>
            <option value="equipment">Equipment</option><option value="other">Other</option>
        </select>
        <label>Unit</label>
        <input type="text" name="unit" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Reorder level</label>
        <input type="number" step="0.01" name="reorder_level" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
        <button type="submit" class="btn">Add</button>
    </form>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Stock on hand</h2>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <?php foreach ($farms as $f): ?><th><?= e($f['name']) ?></th><?php endforeach; ?>
                <th>Reorder level</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$items): ?><tr><td colspan="<?= 2 + count($farms) ?>">No items yet.</td></tr><?php endif; ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['name']) ?></td>
                    <?php foreach ($farms as $f): ?>
                        <?php $qty = $stockByItemFarm[$item['id']][$f['id']] ?? 0; ?>
                        <td<?= $item['reorder_level'] !== null && $qty < $item['reorder_level'] ? ' style="color:#a33; font-weight:bold;"' : '' ?>><?= e($qty) ?></td>
                    <?php endforeach; ?>
                    <td><?= e($item['reorder_level'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($items && $farms): ?>
<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Record a stock movement</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/inventory.php">
        <input type="hidden" name="action" value="record_movement">
        <label>Item</label>
        <select name="item_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <?php foreach ($items as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e($item['name']) ?></option><?php endforeach; ?>
        </select>
        <label>Type</label>
        <select name="type" id="movement_type" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;" onchange="document.getElementById('related_farm_field').style.display = this.value === 'transfer' ? 'block' : 'none';">
            <option value="in">Stock in</option><option value="out">Stock out</option><option value="transfer">Transfer between farms</option>
        </select>
        <label>Farm</label>
        <select name="farm_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <?php foreach ($farms as $f): ?><option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?>
        </select>
        <div id="related_farm_field" style="display:none;">
            <label>Transfer to</label>
            <select name="related_farm_id" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="">Select…</option>
                <?php foreach ($farms as $f): ?><option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <label>Quantity</label>
        <input type="number" step="0.01" name="quantity" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Reference</label>
        <input type="text" name="reference" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Date</label>
        <input type="date" name="movement_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Notes</label>
        <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
        <button type="submit" class="btn">Record</button>
    </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/auth-check.php';
require_tenant_user();

$cropCycleId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT cc.*, ct.name AS crop_type_name, se.name AS season_name,
            p.plot_code, b.name AS block_name, f.id AS farm_id, f.name AS farm_name, f.organization_id
     FROM crop_cycles cc
     JOIN plots p ON p.id = cc.plot_id
     JOIN blocks b ON b.id = p.block_id
     JOIN farms f ON f.id = b.farm_id
     JOIN crop_types ct ON ct.id = cc.crop_type_id
     LEFT JOIN seasons se ON se.id = cc.season_id
     WHERE cc.id = :id'
);
$stmt->execute(['id' => $cropCycleId]);
$cycle = $stmt->fetch();

if (!$cycle || (int) $cycle['organization_id'] !== (int) current_organization_id()) {
    http_response_code(404);
    exit('404 — crop cycle not found.');
}

$error = flash('error');
$success = flash('success');

$statusLabels = [
    'planning' => 'Planning', 'procurement' => 'Procurement', 'nursery' => 'Nursery',
    'field' => 'Field', 'monitoring' => 'Monitoring', 'harvested' => 'Harvested', 'closed' => 'Closed',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('crops.manage');
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        if (isset($statusLabels[$status])) {
            db()->prepare('UPDATE crop_cycles SET status = :status WHERE id = :id')
                ->execute(['status' => $status, 'id' => $cropCycleId]);
            audit_log('update_status', 'crop_cycles', (string) $cropCycleId, ['status' => $cycle['status']], ['status' => $status]);
            flash('success', 'Status updated.');
        }
        redirect('/admin/crop-view.php?id=' . $cropCycleId);
    }

    if ($action === 'add_input') {
        db()->prepare(
            'INSERT INTO crop_inputs (crop_cycle_id, input_type, supplier_name, quantity, unit, cost, purchase_date, expiry_date)
             VALUES (:cc, :type, :supplier, :qty, :unit, :cost, :purchase, :expiry)'
        )->execute([
            'cc' => $cropCycleId,
            'type' => $_POST['input_type'] ?? 'seed',
            'supplier' => trim($_POST['supplier_name'] ?? '') ?: null,
            'qty' => ($_POST['quantity'] ?? '') !== '' ? (float) $_POST['quantity'] : null,
            'unit' => trim($_POST['unit'] ?? '') ?: null,
            'cost' => ($_POST['cost'] ?? '') !== '' ? (float) $_POST['cost'] : null,
            'purchase' => ($_POST['purchase_date'] ?? '') ?: null,
            'expiry' => ($_POST['expiry_date'] ?? '') ?: null,
        ]);
        flash('success', 'Input recorded.');
        redirect('/admin/crop-view.php?id=' . $cropCycleId);
    }

    if ($action === 'add_nursery') {
        db()->prepare(
            'INSERT INTO nursery_records (crop_cycle_id, record_date, germination_rate, treatment, survival_rate, notes)
             VALUES (:cc, :date, :germ, :treatment, :survival, :notes)'
        )->execute([
            'cc' => $cropCycleId,
            'date' => ($_POST['record_date'] ?? '') ?: date('Y-m-d'),
            'germ' => ($_POST['germination_rate'] ?? '') !== '' ? (float) $_POST['germination_rate'] : null,
            'treatment' => trim($_POST['treatment'] ?? '') ?: null,
            'survival' => ($_POST['survival_rate'] ?? '') !== '' ? (float) $_POST['survival_rate'] : null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Nursery record added.');
        redirect('/admin/crop-view.php?id=' . $cropCycleId);
    }

    if ($action === 'add_activity') {
        db()->prepare(
            'INSERT INTO field_activities (crop_cycle_id, activity_type, activity_date, worker_name, gps_lat, gps_lng, cost, notes, status)
             VALUES (:cc, :type, :date, :worker, :lat, :lng, :cost, :notes, :status)'
        )->execute([
            'cc' => $cropCycleId,
            'type' => $_POST['activity_type'] ?? 'other',
            'date' => ($_POST['activity_date'] ?? '') ?: date('Y-m-d'),
            'worker' => trim($_POST['worker_name'] ?? '') ?: null,
            'lat' => ($_POST['gps_lat'] ?? '') !== '' ? (float) $_POST['gps_lat'] : null,
            'lng' => ($_POST['gps_lng'] ?? '') !== '' ? (float) $_POST['gps_lng'] : null,
            'cost' => ($_POST['cost'] ?? '') !== '' ? (float) $_POST['cost'] : null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'status' => $_POST['status'] ?? 'pending',
        ]);
        flash('success', 'Field activity logged.');
        redirect('/admin/crop-view.php?id=' . $cropCycleId);
    }

    if ($action === 'add_monitoring') {
        db()->prepare(
            'INSERT INTO monitoring_records (crop_cycle_id, type, record_date, description, severity, photo_path)
             VALUES (:cc, :type, :date, :description, :severity, :photo)'
        )->execute([
            'cc' => $cropCycleId,
            'type' => $_POST['type'] ?? 'growth',
            'date' => ($_POST['record_date'] ?? '') ?: date('Y-m-d'),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'severity' => ($_POST['severity'] ?? '') !== '' ? $_POST['severity'] : null,
            'photo' => trim($_POST['photo_path'] ?? '') ?: null,
        ]);
        flash('success', 'Monitoring record added.');
        redirect('/admin/crop-view.php?id=' . $cropCycleId);
    }

    if ($action === 'add_forecast') {
        db()->prepare(
            'INSERT INTO yield_forecasts (crop_cycle_id, forecast_date, estimated_yield, notes)
             VALUES (:cc, :date, :yield, :notes)'
        )->execute([
            'cc' => $cropCycleId,
            'date' => ($_POST['forecast_date'] ?? '') ?: date('Y-m-d'),
            'yield' => (float) ($_POST['estimated_yield'] ?? 0),
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Forecast recorded.');
        redirect('/admin/crop-view.php?id=' . $cropCycleId);
    }

    if ($action === 'add_harvest') {
        db()->prepare(
            'INSERT INTO harvests (crop_cycle_id, harvest_date, quantity, unit, quality_grade, notes)
             VALUES (:cc, :date, :qty, :unit, :grade, :notes)'
        )->execute([
            'cc' => $cropCycleId,
            'date' => ($_POST['harvest_date'] ?? '') ?: date('Y-m-d'),
            'qty' => (float) ($_POST['quantity'] ?? 0),
            'unit' => trim($_POST['unit'] ?? '') ?: null,
            'grade' => trim($_POST['quality_grade'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        audit_log('create', 'harvests', (string) db()->lastInsertId(), null, ['crop_cycle_id' => $cropCycleId]);
        flash('success', 'Harvest recorded.');
        redirect('/admin/crop-view.php?id=' . $cropCycleId);
    }

    if ($action === 'add_sale') {
        $harvestId = (int) ($_POST['harvest_id'] ?? 0);
        $quantity = (float) ($_POST['quantity'] ?? 0);
        $unitPrice = (float) ($_POST['unit_price'] ?? 0);

        // Confirm the harvest belongs to this crop cycle before recording a sale against it.
        $harvestCheck = db()->prepare('SELECT id FROM harvests WHERE id = :id AND crop_cycle_id = :cc');
        $harvestCheck->execute(['id' => $harvestId, 'cc' => $cropCycleId]);

        if (!$harvestCheck->fetch()) {
            $error = 'Select a valid harvest for this sale.';
        } else {
            db()->prepare(
                'INSERT INTO crop_sales (harvest_id, buyer_name, quantity, unit_price, revenue, sale_date, notes)
                 VALUES (:harvest_id, :buyer, :qty, :price, :revenue, :date, :notes)'
            )->execute([
                'harvest_id' => $harvestId,
                'buyer' => trim($_POST['buyer_name'] ?? ''),
                'qty' => $quantity,
                'price' => $unitPrice,
                'revenue' => $quantity * $unitPrice,
                'date' => ($_POST['sale_date'] ?? '') ?: date('Y-m-d'),
                'notes' => trim($_POST['notes'] ?? '') ?: null,
            ]);
            audit_log('create', 'crop_sales', (string) db()->lastInsertId(), null, ['harvest_id' => $harvestId, 'revenue' => $quantity * $unitPrice]);
            flash('success', 'Sale recorded.');
            redirect('/admin/crop-view.php?id=' . $cropCycleId);
        }
    }
}

function fetchAllFor(string $table, int $cropCycleId, string $order = 'id DESC'): array
{
    $stmt = db()->prepare("SELECT * FROM $table WHERE crop_cycle_id = :cc ORDER BY $order");
    $stmt->execute(['cc' => $cropCycleId]);
    return $stmt->fetchAll();
}

$inputs = fetchAllFor('crop_inputs', $cropCycleId);
$nurseryRecords = fetchAllFor('nursery_records', $cropCycleId);
$activities = fetchAllFor('field_activities', $cropCycleId);
$monitoringRecords = fetchAllFor('monitoring_records', $cropCycleId);
$forecasts = fetchAllFor('yield_forecasts', $cropCycleId);
$harvests = fetchAllFor('harvests', $cropCycleId, 'harvest_date DESC');

$salesStmt = db()->prepare(
    'SELECT cs.* FROM crop_sales cs JOIN harvests h ON h.id = cs.harvest_id WHERE h.crop_cycle_id = :cc ORDER BY cs.sale_date DESC'
);
$salesStmt->execute(['cc' => $cropCycleId]);
$sales = $salesStmt->fetchAll();

$pageTitle = $cycle['batch_code'];
$activePage = 'crops';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/crops.php">&larr; All crop cycles</a></p>
<h1><?= e($cycle['crop_type_name']) ?> — <?= e($cycle['batch_code']) ?></h1>
<p class="muted"><?= e($cycle['farm_name'] . ' / ' . $cycle['block_name'] . ' / ' . $cycle['plot_code']) ?><?= $cycle['season_name'] ? ' · ' . e($cycle['season_name']) : '' ?></p>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Status</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/crop-view.php?id=<?= $cropCycleId ?>" style="display:flex; gap:0.5rem;">
        <input type="hidden" name="action" value="update_status">
        <select name="status" style="flex:1; padding:0.5rem;">
            <?php foreach ($statusLabels as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $cycle['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Update</button>
    </form>
    <p class="muted" style="margin-bottom:0;">Budget: <?= $cycle['budget'] !== null ? e((string) $cycle['budget']) : '—' ?> · Expected yield: <?= $cycle['expected_yield'] !== null ? e((string) $cycle['expected_yield']) : '—' ?></p>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Inputs</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Type</th><th>Supplier</th><th>Qty</th><th>Cost</th><th>Purchased</th><th>Expiry</th></tr></thead>
        <tbody>
            <?php if (!$inputs): ?><tr><td colspan="6">None yet.</td></tr><?php endif; ?>
            <?php foreach ($inputs as $row): ?>
                <tr>
                    <td><?= e($row['input_type']) ?></td>
                    <td><?= e($row['supplier_name'] ?? '—') ?></td>
                    <td><?= e(($row['quantity'] ?? '—') . ' ' . ($row['unit'] ?? '')) ?></td>
                    <td><?= e($row['cost'] ?? '—') ?></td>
                    <td><?= e($row['purchase_date'] ?? '—') ?></td>
                    <td><?= e($row['expiry_date'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Record an input</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/crop-view.php?id=<?= $cropCycleId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_input">
            <label>Type</label>
            <select name="input_type" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="seed">Seed</option><option value="fertilizer">Fertilizer</option>
                <option value="chemical">Chemical</option><option value="tool">Tool</option>
            </select>
            <label>Supplier</label>
            <input type="text" name="supplier_name" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Quantity</label>
            <input type="number" step="0.01" name="quantity" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Unit</label>
            <input type="text" name="unit" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Cost</label>
            <input type="number" step="0.01" name="cost" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Purchase date</label>
            <input type="date" name="purchase_date" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Expiry date</label>
            <input type="date" name="expiry_date" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Nursery records</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Date</th><th>Germination %</th><th>Treatment</th><th>Survival %</th><th>Notes</th></tr></thead>
        <tbody>
            <?php if (!$nurseryRecords): ?><tr><td colspan="5">None yet.</td></tr><?php endif; ?>
            <?php foreach ($nurseryRecords as $row): ?>
                <tr>
                    <td><?= e($row['record_date']) ?></td>
                    <td><?= e($row['germination_rate'] ?? '—') ?></td>
                    <td><?= e($row['treatment'] ?? '—') ?></td>
                    <td><?= e($row['survival_rate'] ?? '—') ?></td>
                    <td><?= e($row['notes'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Add a nursery record</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/crop-view.php?id=<?= $cropCycleId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_nursery">
            <label>Date</label>
            <input type="date" name="record_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Germination rate (%)</label>
            <input type="number" step="0.01" name="germination_rate" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Treatment</label>
            <input type="text" name="treatment" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Survival rate (%)</label>
            <input type="number" step="0.01" name="survival_rate" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Notes</label>
            <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Field activities</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Type</th><th>Date</th><th>Worker</th><th>Cost</th><th>Status</th><th>Notes</th></tr></thead>
        <tbody>
            <?php if (!$activities): ?><tr><td colspan="6">None yet.</td></tr><?php endif; ?>
            <?php foreach ($activities as $row): ?>
                <tr>
                    <td><?= e($row['activity_type']) ?></td>
                    <td><?= e($row['activity_date']) ?></td>
                    <td><?= e($row['worker_name'] ?? '—') ?></td>
                    <td><?= e($row['cost'] ?? '—') ?></td>
                    <td><?= e($row['status']) ?></td>
                    <td><?= e($row['notes'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Log a field activity</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/crop-view.php?id=<?= $cropCycleId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_activity">
            <label>Type</label>
            <select name="activity_type" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="planting">Planting</option><option value="weeding">Weeding</option>
                <option value="irrigation">Irrigation</option><option value="spraying">Spraying</option>
                <option value="fertilizing">Fertilizing</option><option value="other">Other</option>
            </select>
            <label>Date</label>
            <input type="date" name="activity_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Worker name</label>
            <input type="text" name="worker_name" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Cost</label>
            <input type="number" step="0.01" name="cost" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Status</label>
            <select name="status" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="pending">Pending</option><option value="ongoing">Ongoing</option><option value="completed">Completed</option>
            </select>
            <label>Notes</label>
            <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Monitoring (disease / pest / growth)</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Type</th><th>Date</th><th>Severity</th><th>Description</th></tr></thead>
        <tbody>
            <?php if (!$monitoringRecords): ?><tr><td colspan="4">None yet.</td></tr><?php endif; ?>
            <?php foreach ($monitoringRecords as $row): ?>
                <tr>
                    <td><?= e($row['type']) ?></td>
                    <td><?= e($row['record_date']) ?></td>
                    <td><?= e($row['severity'] ?? '—') ?></td>
                    <td><?= e($row['description'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Add a monitoring record</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/crop-view.php?id=<?= $cropCycleId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_monitoring">
            <label>Type</label>
            <select name="type" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="disease">Disease</option><option value="pest">Pest</option><option value="growth">Growth</option>
            </select>
            <label>Date</label>
            <input type="date" name="record_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Severity</label>
            <select name="severity" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="">N/A</option><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option>
            </select>
            <label>Description</label>
            <input type="text" name="description" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Photo path (optional)</label>
            <input type="text" name="photo_path" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Yield forecasts</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Date</th><th>Estimated yield</th><th>Notes</th></tr></thead>
        <tbody>
            <?php if (!$forecasts): ?><tr><td colspan="3">None yet.</td></tr><?php endif; ?>
            <?php foreach ($forecasts as $row): ?>
                <tr><td><?= e($row['forecast_date']) ?></td><td><?= e($row['estimated_yield']) ?></td><td><?= e($row['notes'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Add a forecast</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/crop-view.php?id=<?= $cropCycleId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_forecast">
            <label>Date</label>
            <input type="date" name="forecast_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Estimated yield</label>
            <input type="number" step="0.01" name="estimated_yield" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Notes</label>
            <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Harvests</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Date</th><th>Quantity</th><th>Grade</th><th>Notes</th></tr></thead>
        <tbody>
            <?php if (!$harvests): ?><tr><td colspan="4">None yet.</td></tr><?php endif; ?>
            <?php foreach ($harvests as $row): ?>
                <tr>
                    <td><?= e($row['harvest_date']) ?></td>
                    <td><?= e($row['quantity'] . ' ' . ($row['unit'] ?? '')) ?></td>
                    <td><?= e($row['quality_grade'] ?? '—') ?></td>
                    <td><?= e($row['notes'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Record a harvest</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/crop-view.php?id=<?= $cropCycleId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_harvest">
            <label>Date</label>
            <input type="date" name="harvest_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Quantity</label>
            <input type="number" step="0.01" name="quantity" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Unit</label>
            <input type="text" name="unit" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Quality grade</label>
            <input type="text" name="quality_grade" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Notes</label>
            <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Sales</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Date</th><th>Buyer</th><th>Quantity</th><th>Unit price</th><th>Revenue</th></tr></thead>
        <tbody>
            <?php if (!$sales): ?><tr><td colspan="5">None yet.</td></tr><?php endif; ?>
            <?php foreach ($sales as $row): ?>
                <tr>
                    <td><?= e($row['sale_date']) ?></td>
                    <td><?= e($row['buyer_name']) ?></td>
                    <td><?= e($row['quantity']) ?></td>
                    <td><?= e($row['unit_price']) ?></td>
                    <td><?= e($row['revenue']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($harvests): ?>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Record a sale</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/crop-view.php?id=<?= $cropCycleId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_sale">
            <label>Harvest</label>
            <select name="harvest_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <?php foreach ($harvests as $h): ?>
                    <option value="<?= (int) $h['id'] ?>"><?= e($h['harvest_date'] . ' — ' . $h['quantity'] . ' ' . ($h['unit'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Buyer name</label>
            <input type="text" name="buyer_name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Quantity</label>
            <input type="number" step="0.01" name="quantity" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Unit price</label>
            <input type="number" step="0.01" name="unit_price" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Sale date</label>
            <input type="date" name="sale_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Notes</label>
            <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
    <?php else: ?>
        <p class="muted">Record a harvest first before logging a sale.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

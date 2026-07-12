<?php
require_once __DIR__ . '/includes/auth-check.php';
require_tenant_user();

$animalId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT a.*, f.name AS farm_name, f.organization_id FROM animals a JOIN farms f ON f.id = a.farm_id WHERE a.id = :id'
);
$stmt->execute(['id' => $animalId]);
$animal = $stmt->fetch();

if (!$animal || (int) $animal['organization_id'] !== (int) current_organization_id()) {
    http_response_code(404);
    exit('404 — animal not found.');
}

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('livestock.manage');
    $action = $_POST['action'] ?? '';

    if ($action === 'add_vaccination') {
        db()->prepare(
            'INSERT INTO animal_vaccinations (animal_id, vaccine_name, date_administered, next_due_date, administered_by, notes)
             VALUES (:a, :name, :date, :next, :by, :notes)'
        )->execute([
            'a' => $animalId,
            'name' => trim($_POST['vaccine_name'] ?? ''),
            'date' => ($_POST['date_administered'] ?? '') ?: date('Y-m-d'),
            'next' => ($_POST['next_due_date'] ?? '') ?: null,
            'by' => trim($_POST['administered_by'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Vaccination recorded.');
        redirect('/admin/animal-view.php?id=' . $animalId);
    }

    if ($action === 'add_feeding') {
        db()->prepare(
            'INSERT INTO animal_feedings (animal_id, feed_type, quantity, unit, feeding_date, cost, notes)
             VALUES (:a, :type, :qty, :unit, :date, :cost, :notes)'
        )->execute([
            'a' => $animalId,
            'type' => trim($_POST['feed_type'] ?? ''),
            'qty' => ($_POST['quantity'] ?? '') !== '' ? (float) $_POST['quantity'] : null,
            'unit' => trim($_POST['unit'] ?? '') ?: null,
            'date' => ($_POST['feeding_date'] ?? '') ?: date('Y-m-d'),
            'cost' => ($_POST['cost'] ?? '') !== '' ? (float) $_POST['cost'] : null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Feeding recorded.');
        redirect('/admin/animal-view.php?id=' . $animalId);
    }

    if ($action === 'add_weight') {
        db()->prepare(
            'INSERT INTO animal_weights (animal_id, weight_kg, recorded_date, notes) VALUES (:a, :weight, :date, :notes)'
        )->execute([
            'a' => $animalId,
            'weight' => (float) ($_POST['weight_kg'] ?? 0),
            'date' => ($_POST['recorded_date'] ?? '') ?: date('Y-m-d'),
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Weight recorded.');
        redirect('/admin/animal-view.php?id=' . $animalId);
    }

    if ($action === 'add_treatment') {
        db()->prepare(
            'INSERT INTO animal_treatments (animal_id, condition_name, treatment, treatment_date, administered_by, cost, notes)
             VALUES (:a, :condition, :treatment, :date, :by, :cost, :notes)'
        )->execute([
            'a' => $animalId,
            'condition' => trim($_POST['condition_name'] ?? ''),
            'treatment' => trim($_POST['treatment'] ?? ''),
            'date' => ($_POST['treatment_date'] ?? '') ?: date('Y-m-d'),
            'by' => trim($_POST['administered_by'] ?? '') ?: null,
            'cost' => ($_POST['cost'] ?? '') !== '' ? (float) $_POST['cost'] : null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Treatment recorded.');
        redirect('/admin/animal-view.php?id=' . $animalId);
    }

    if ($action === 'add_breeding') {
        db()->prepare(
            'INSERT INTO animal_breeding (animal_id, mate_description, breeding_date, expected_due_date, outcome, offspring_count, notes)
             VALUES (:a, :mate, :date, :due, :outcome, :offspring, :notes)'
        )->execute([
            'a' => $animalId,
            'mate' => trim($_POST['mate_description'] ?? '') ?: null,
            'date' => ($_POST['breeding_date'] ?? '') ?: date('Y-m-d'),
            'due' => ($_POST['expected_due_date'] ?? '') ?: null,
            'outcome' => $_POST['outcome'] ?? 'pending',
            'offspring' => ($_POST['offspring_count'] ?? '') !== '' ? (int) $_POST['offspring_count'] : null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Breeding record added.');
        redirect('/admin/animal-view.php?id=' . $animalId);
    }

    if ($action === 'add_production') {
        db()->prepare(
            'INSERT INTO animal_production (animal_id, production_type, quantity, unit, production_date, notes)
             VALUES (:a, :type, :qty, :unit, :date, :notes)'
        )->execute([
            'a' => $animalId,
            'type' => trim($_POST['production_type'] ?? ''),
            'qty' => (float) ($_POST['quantity'] ?? 0),
            'unit' => trim($_POST['unit'] ?? '') ?: null,
            'date' => ($_POST['production_date'] ?? '') ?: date('Y-m-d'),
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Production recorded.');
        redirect('/admin/animal-view.php?id=' . $animalId);
    }

    if ($action === 'record_mortality') {
        db()->prepare('INSERT INTO animal_mortality (animal_id, death_date, cause, notes) VALUES (:a, :date, :cause, :notes)')
            ->execute([
                'a' => $animalId,
                'date' => ($_POST['death_date'] ?? '') ?: date('Y-m-d'),
                'cause' => trim($_POST['cause'] ?? '') ?: null,
                'notes' => trim($_POST['notes'] ?? '') ?: null,
            ]);
        db()->prepare('UPDATE animals SET status = "deceased" WHERE id = :id')->execute(['id' => $animalId]);
        audit_log('mortality', 'animals', (string) $animalId, ['status' => $animal['status']], ['status' => 'deceased']);
        flash('success', 'Mortality recorded.');
        redirect('/admin/animal-view.php?id=' . $animalId);
    }

    if ($action === 'record_sale') {
        db()->prepare('INSERT INTO animal_sales (animal_id, buyer_name, sale_price, sale_date, notes) VALUES (:a, :buyer, :price, :date, :notes)')
            ->execute([
                'a' => $animalId,
                'buyer' => trim($_POST['buyer_name'] ?? ''),
                'price' => (float) ($_POST['sale_price'] ?? 0),
                'date' => ($_POST['sale_date'] ?? '') ?: date('Y-m-d'),
                'notes' => trim($_POST['notes'] ?? '') ?: null,
            ]);
        db()->prepare('UPDATE animals SET status = "sold" WHERE id = :id')->execute(['id' => $animalId]);
        audit_log('sale', 'animals', (string) $animalId, ['status' => $animal['status']], ['status' => 'sold']);
        flash('success', 'Sale recorded.');
        redirect('/admin/animal-view.php?id=' . $animalId);
    }
}

function fetchAllForAnimal(string $table, int $animalId, string $order = 'id DESC'): array
{
    $stmt = db()->prepare("SELECT * FROM $table WHERE animal_id = :a ORDER BY $order");
    $stmt->execute(['a' => $animalId]);
    return $stmt->fetchAll();
}

$vaccinations = fetchAllForAnimal('animal_vaccinations', $animalId);
$feedings = fetchAllForAnimal('animal_feedings', $animalId);
$weights = fetchAllForAnimal('animal_weights', $animalId, 'recorded_date DESC');
$treatments = fetchAllForAnimal('animal_treatments', $animalId);
$breedings = fetchAllForAnimal('animal_breeding', $animalId);
$productions = fetchAllForAnimal('animal_production', $animalId);

$pageTitle = $animal['animal_code'];
$activePage = 'livestock';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/livestock.php">&larr; All animals</a></p>
<h1><?= e($animal['name'] ?: $animal['species']) ?> <span class="muted">(<?= e($animal['animal_code']) ?>)</span></h1>
<p class="muted"><?= e($animal['species']) ?><?= $animal['breed'] ? ' · ' . e($animal['breed']) : '' ?> · <?= e($animal['farm_name']) ?> · Status: <?= e($animal['status']) ?></p>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<?php if ($animal['status'] === 'active'): ?>
<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0; font-size:1rem;">Outcome</h2>
    <div style="display:flex; gap:1.5rem; flex-wrap:wrap;">
        <details>
            <summary style="cursor:pointer; color:#a33;">Record mortality</summary>
            <form method="POST" action="<?= BASE_URL ?>/admin/animal-view.php?id=<?= $animalId ?>" style="margin-top:0.75rem; max-width:360px;">
                <input type="hidden" name="action" value="record_mortality">
                <label>Death date</label>
                <input type="date" name="death_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <label>Cause</label>
                <input type="text" name="cause" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <label>Notes</label>
                <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
                <button type="submit" class="btn" style="background:#a33;">Record</button>
            </form>
        </details>
        <details>
            <summary style="cursor:pointer; color:#2f5233;">Record sale</summary>
            <form method="POST" action="<?= BASE_URL ?>/admin/animal-view.php?id=<?= $animalId ?>" style="margin-top:0.75rem; max-width:360px;">
                <input type="hidden" name="action" value="record_sale">
                <label>Buyer name</label>
                <input type="text" name="buyer_name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <label>Sale price</label>
                <input type="number" step="0.01" name="sale_price" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <label>Sale date</label>
                <input type="date" name="sale_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <label>Notes</label>
                <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
                <button type="submit" class="btn">Record</button>
            </form>
        </details>
    </div>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Vaccinations</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Vaccine</th><th>Given</th><th>Next due</th><th>By</th></tr></thead>
        <tbody>
            <?php if (!$vaccinations): ?><tr><td colspan="4">None yet.</td></tr><?php endif; ?>
            <?php foreach ($vaccinations as $row): ?>
                <tr><td><?= e($row['vaccine_name']) ?></td><td><?= e($row['date_administered']) ?></td><td><?= e($row['next_due_date'] ?? '—') ?></td><td><?= e($row['administered_by'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Add a vaccination</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/animal-view.php?id=<?= $animalId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_vaccination">
            <label>Vaccine name</label>
            <input type="text" name="vaccine_name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Date administered</label>
            <input type="date" name="date_administered" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Next due date</label>
            <input type="date" name="next_due_date" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Administered by</label>
            <input type="text" name="administered_by" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Feedings</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Feed type</th><th>Quantity</th><th>Date</th><th>Cost</th></tr></thead>
        <tbody>
            <?php if (!$feedings): ?><tr><td colspan="4">None yet.</td></tr><?php endif; ?>
            <?php foreach ($feedings as $row): ?>
                <tr><td><?= e($row['feed_type']) ?></td><td><?= e(($row['quantity'] ?? '—') . ' ' . ($row['unit'] ?? '')) ?></td><td><?= e($row['feeding_date']) ?></td><td><?= e($row['cost'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Add a feeding</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/animal-view.php?id=<?= $animalId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_feeding">
            <label>Feed type</label>
            <input type="text" name="feed_type" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Quantity</label>
            <input type="number" step="0.01" name="quantity" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Unit</label>
            <input type="text" name="unit" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Date</label>
            <input type="date" name="feeding_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Cost</label>
            <input type="number" step="0.01" name="cost" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Weights</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Date</th><th>Weight (kg)</th><th>Notes</th></tr></thead>
        <tbody>
            <?php if (!$weights): ?><tr><td colspan="3">None yet.</td></tr><?php endif; ?>
            <?php foreach ($weights as $row): ?>
                <tr><td><?= e($row['recorded_date']) ?></td><td><?= e($row['weight_kg']) ?></td><td><?= e($row['notes'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Add a weight record</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/animal-view.php?id=<?= $animalId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_weight">
            <label>Weight (kg)</label>
            <input type="number" step="0.01" name="weight_kg" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Date</label>
            <input type="date" name="recorded_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Notes</label>
            <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Treatments</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Condition</th><th>Treatment</th><th>Date</th><th>Cost</th></tr></thead>
        <tbody>
            <?php if (!$treatments): ?><tr><td colspan="4">None yet.</td></tr><?php endif; ?>
            <?php foreach ($treatments as $row): ?>
                <tr><td><?= e($row['condition_name']) ?></td><td><?= e($row['treatment']) ?></td><td><?= e($row['treatment_date']) ?></td><td><?= e($row['cost'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Add a treatment</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/animal-view.php?id=<?= $animalId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_treatment">
            <label>Condition</label>
            <input type="text" name="condition_name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Treatment</label>
            <input type="text" name="treatment" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Date</label>
            <input type="date" name="treatment_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Administered by</label>
            <input type="text" name="administered_by" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Cost</label>
            <input type="number" step="0.01" name="cost" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Breeding</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Date</th><th>Mate</th><th>Expected due</th><th>Outcome</th><th>Offspring</th></tr></thead>
        <tbody>
            <?php if (!$breedings): ?><tr><td colspan="5">None yet.</td></tr><?php endif; ?>
            <?php foreach ($breedings as $row): ?>
                <tr><td><?= e($row['breeding_date']) ?></td><td><?= e($row['mate_description'] ?? '—') ?></td><td><?= e($row['expected_due_date'] ?? '—') ?></td><td><?= e($row['outcome']) ?></td><td><?= e($row['offspring_count'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Add a breeding record</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/animal-view.php?id=<?= $animalId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_breeding">
            <label>Mate description</label>
            <input type="text" name="mate_description" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Breeding date</label>
            <input type="date" name="breeding_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Expected due date</label>
            <input type="date" name="expected_due_date" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Outcome</label>
            <select name="outcome" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="pending">Pending</option><option value="successful">Successful</option><option value="failed">Failed</option>
            </select>
            <label>Offspring count</label>
            <input type="number" name="offspring_count" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Production (milk, eggs, etc.)</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Type</th><th>Quantity</th><th>Date</th></tr></thead>
        <tbody>
            <?php if (!$productions): ?><tr><td colspan="3">None yet.</td></tr><?php endif; ?>
            <?php foreach ($productions as $row): ?>
                <tr><td><?= e($row['production_type']) ?></td><td><?= e($row['quantity'] . ' ' . ($row['unit'] ?? '')) ?></td><td><?= e($row['production_date']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Add a production record</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/animal-view.php?id=<?= $animalId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_production">
            <label>Type</label>
            <input type="text" name="production_type" required placeholder="e.g. milk, eggs" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Quantity</label>
            <input type="number" step="0.01" name="quantity" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Unit</label>
            <input type="text" name="unit" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Date</label>
            <input type="date" name="production_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

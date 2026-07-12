<?php
require_once __DIR__ . '/includes/auth-check.php';

$farmIds = visible_farm_ids();
$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('livestock.manage');

    $farmId = (int) ($_POST['farm_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $species = trim($_POST['species'] ?? '');
    $breed = trim($_POST['breed'] ?? '');
    $tagNumber = trim($_POST['tag_number'] ?? '');
    $gender = $_POST['gender'] ?? 'female';
    $birthDate = ($_POST['birth_date'] ?? '') ?: null;

    if (!in_array($farmId, $farmIds, true) || $species === '') {
        $error = 'A valid farm and species are required.';
    } else {
        $animalCode = 'AN' . strtoupper(bin2hex(random_bytes(4)));
        db()->prepare(
            'INSERT INTO animals (farm_id, animal_code, name, species, breed, tag_number, gender, birth_date, status)
             VALUES (:farm_id, :code, :name, :species, :breed, :tag, :gender, :birth, "active")'
        )->execute([
            'farm_id' => $farmId,
            'code' => $animalCode,
            'name' => $name ?: null,
            'species' => $species,
            'breed' => $breed ?: null,
            'tag' => $tagNumber ?: null,
            'gender' => $gender,
            'birth' => $birthDate,
        ]);

        create_trace_batch('livestock', $animalCode, null, (int) db()->lastInsertId());

        flash('success', 'Animal added.');
        redirect('/admin/livestock.php');
    }
}

$animals = [];
$farms = [];
if ($farmIds) {
    $stmt = db()->prepare(
        'SELECT a.*, f.name AS farm_name FROM animals a JOIN farms f ON f.id = a.farm_id
         WHERE a.farm_id IN (' . in_placeholders($farmIds) . ') ORDER BY a.created_at DESC'
    );
    $stmt->execute($farmIds);
    $animals = $stmt->fetchAll();

    $farmStmt = db()->prepare('SELECT id, name FROM farms WHERE id IN (' . in_placeholders($farmIds) . ') ORDER BY name');
    $farmStmt->execute($farmIds);
    $farms = $farmStmt->fetchAll();
}

$pageTitle = 'Livestock';
$activePage = 'livestock';
require __DIR__ . '/includes/header.php';
?>

<h1>Livestock</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<?php if (!$farms): ?>
    <div class="alert alert-error">No farms available yet. <a href="<?= BASE_URL ?>/admin/farms.php">Add a farm</a> first.</div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Register an animal</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/livestock.php">
        <label>Farm</label>
        <select name="farm_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="">Select…</option>
            <?php foreach ($farms as $f): ?>
                <option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Species</label>
        <input type="text" name="species" required placeholder="e.g. Cattle, Goat, Chicken" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Name (optional)</label>
        <input type="text" name="name" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Breed</label>
        <input type="text" name="breed" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Tag number</label>
        <input type="text" name="tag_number" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Gender</label>
        <select name="gender" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="female">Female</option><option value="male">Male</option>
        </select>
        <label>Birth date</label>
        <input type="date" name="birth_date" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
        <button type="submit" class="btn">Register</button>
    </form>
</div>

<table>
    <thead><tr><th>Code</th><th>Name</th><th>Species</th><th>Farm</th><th>Gender</th><th>Status</th><th></th></tr></thead>
    <tbody>
        <?php if (!$animals): ?><tr><td colspan="7">No animals yet.</td></tr><?php endif; ?>
        <?php foreach ($animals as $a): ?>
            <tr>
                <td><?= e($a['animal_code']) ?></td>
                <td><?= e($a['name'] ?? '—') ?></td>
                <td><?= e($a['species']) ?></td>
                <td><?= e($a['farm_name']) ?></td>
                <td><?= e($a['gender']) ?></td>
                <td><?= e($a['status']) ?></td>
                <td><a href="<?= BASE_URL ?>/admin/animal-view.php?id=<?= (int) $a['id'] ?>">Manage</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>

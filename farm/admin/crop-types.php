<?php
require_once __DIR__ . '/includes/auth-check.php';

// crop_types is a shared reference list (no organization_id column, same as
// the farm-app design notes) -- every tenant sees the same list.

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('crops.manage');

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $error = 'Name is required.';
    } else {
        try {
            db()->prepare('INSERT INTO crop_types (name) VALUES (:name)')->execute(['name' => $name]);
            flash('success', 'Crop type added.');
            redirect('/admin/crop-types.php');
        } catch (PDOException $e) {
            $error = 'That crop type already exists.';
        }
    }
}

$cropTypes = db()->query('SELECT * FROM crop_types ORDER BY name')->fetchAll();

$pageTitle = 'Crop Types';
$activePage = 'crop-types';
require __DIR__ . '/includes/header.php';
?>

<h1>Crop Types</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:360px;">
    <form method="POST" action="<?= BASE_URL ?>/admin/crop-types.php">
        <label for="name">New crop type</label>
        <input type="text" id="name" name="name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <button type="submit" class="btn">Add</button>
    </form>
</div>

<table>
    <thead><tr><th>Name</th><th>Added</th></tr></thead>
    <tbody>
        <?php if (!$cropTypes): ?><tr><td colspan="2">No crop types yet.</td></tr><?php endif; ?>
        <?php foreach ($cropTypes as $ct): ?>
            <tr><td><?= e($ct['name']) ?></td><td><?= e($ct['created_at']) ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>

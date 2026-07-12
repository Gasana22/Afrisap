<?php
require_once __DIR__ . '/includes/auth-check.php';

// Like crop_types, seasons is a shared reference list -- no organization_id.

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('crops.manage');

    $name = trim($_POST['name'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

    if ($name === '' || $startDate === '') {
        $error = 'Name and start date are required.';
    } else {
        db()->prepare('INSERT INTO seasons (name, start_date, end_date) VALUES (:name, :start, :end)')
            ->execute(['name' => $name, 'start' => $startDate, 'end' => $endDate ?: null]);
        flash('success', 'Season added.');
        redirect('/admin/seasons.php');
    }
}

$seasons = db()->query('SELECT * FROM seasons ORDER BY start_date DESC')->fetchAll();

$pageTitle = 'Seasons';
$activePage = 'seasons';
require __DIR__ . '/includes/header.php';
?>

<h1>Seasons</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:360px;">
    <form method="POST" action="<?= BASE_URL ?>/admin/seasons.php">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label for="start_date">Start date</label>
        <input type="date" id="start_date" name="start_date" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label for="end_date">End date</label>
        <input type="date" id="end_date" name="end_date" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
        <button type="submit" class="btn">Add</button>
    </form>
</div>

<table>
    <thead><tr><th>Name</th><th>Start</th><th>End</th></tr></thead>
    <tbody>
        <?php if (!$seasons): ?><tr><td colspan="3">No seasons yet.</td></tr><?php endif; ?>
        <?php foreach ($seasons as $s): ?>
            <tr><td><?= e($s['name']) ?></td><td><?= e($s['start_date']) ?></td><td><?= e($s['end_date'] ?? '—') ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>

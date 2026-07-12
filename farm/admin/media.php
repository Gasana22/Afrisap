<?php
require_once __DIR__ . '/includes/auth-check.php';

$farmIds = visible_farm_ids();
$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('media.manage');

    $organizationId = is_platform_user() ? (int) ($_POST['organization_id'] ?? 0) : current_organization_id();
    $farmId = ($_POST['farm_id'] ?? '') !== '' ? (int) $_POST['farm_id'] : null;
    $title = trim($_POST['title'] ?? '');
    $filePath = trim($_POST['file_path'] ?? '');

    if ((!is_platform_user() && !$organizationId) || $title === '' || $filePath === '') {
        $error = 'Title and file path are required.';
    } elseif ($farmId !== null && !in_array($farmId, $farmIds, true)) {
        $error = 'Invalid farm selected.';
    } else {
        db()->prepare(
            'INSERT INTO media_files (farm_id, organization_id, category, title, file_path, uploaded_by, notes)
             VALUES (:farm, :org, :category, :title, :path, :by, :notes)'
        )->execute([
            'farm' => $farmId,
            'org' => $organizationId ?: null,
            'category' => $_POST['category'] ?? 'other',
            'title' => $title,
            'path' => $filePath,
            'by' => current_user()['id'],
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'File attached.');
        redirect('/admin/media.php');
    }
}

if (is_platform_user()) {
    $files = db()->query('SELECT m.*, f.name AS farm_name FROM media_files m LEFT JOIN farms f ON f.id = m.farm_id ORDER BY m.uploaded_at DESC')->fetchAll();
    $organizations = db()->query('SELECT id, name FROM organizations ORDER BY name')->fetchAll();
} else {
    $stmt = db()->prepare(
        'SELECT m.*, f.name AS farm_name FROM media_files m LEFT JOIN farms f ON f.id = m.farm_id
         WHERE m.organization_id = :org ORDER BY m.uploaded_at DESC'
    );
    $stmt->execute(['org' => current_organization_id()]);
    $files = $stmt->fetchAll();
    $organizations = [];
}

$farms = [];
if ($farmIds) {
    $farmStmt = db()->prepare('SELECT id, name FROM farms WHERE id IN (' . in_placeholders($farmIds) . ') ORDER BY name');
    $farmStmt->execute($farmIds);
    $farms = $farmStmt->fetchAll();
}

$pageTitle = 'Media';
$activePage = 'media';
require __DIR__ . '/includes/header.php';
?>

<h1>Media</h1>
<p class="muted">Photos, receipts, contracts and certificates not tied to a specific crop cycle, animal, or purchase order.</p>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Attach a file</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/media.php">
        <?php if (is_platform_user()): ?>
            <label>Organization</label>
            <select name="organization_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="">Select…</option>
                <?php foreach ($organizations as $org): ?><option value="<?= (int) $org['id'] ?>"><?= e($org['name']) ?></option><?php endforeach; ?>
            </select>
        <?php endif; ?>
        <label>Farm (optional)</label>
        <select name="farm_id" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="">Organization-wide</option>
            <?php foreach ($farms as $f): ?><option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?>
        </select>
        <label>Category</label>
        <select name="category" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="photo">Photo</option><option value="receipt">Receipt</option>
            <option value="contract">Contract</option><option value="certificate">Certificate</option><option value="other">Other</option>
        </select>
        <label>Title</label>
        <input type="text" name="title" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>File path / URL</label>
        <input type="text" name="file_path" required placeholder="storage/uploads/..." style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Notes</label>
        <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
        <button type="submit" class="btn">Attach</button>
    </form>
</div>

<table>
    <thead><tr><th>Title</th><th>Category</th><th>Farm</th><th>File</th><th>Uploaded</th></tr></thead>
    <tbody>
        <?php if (!$files): ?><tr><td colspan="5">No files yet.</td></tr><?php endif; ?>
        <?php foreach ($files as $f): ?>
            <tr>
                <td><?= e($f['title']) ?></td>
                <td><?= e($f['category']) ?></td>
                <td><?= e($f['farm_name'] ?? 'Organization-wide') ?></td>
                <td><?= e($f['file_path']) ?></td>
                <td><?= e($f['uploaded_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>

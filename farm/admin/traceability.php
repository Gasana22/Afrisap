<?php
require_once __DIR__ . '/includes/auth-check.php';

$farmIds = visible_farm_ids();

$batches = [];
if ($farmIds) {
    $stmt = db()->prepare(
        "SELECT tb.*, COALESCE(f1.name, f2.name) AS farm_name, ct.name AS crop_type_name, an.species
         FROM trace_batches tb
         LEFT JOIN crop_cycles cc ON cc.id = tb.crop_cycle_id
         LEFT JOIN plots p ON p.id = cc.plot_id
         LEFT JOIN blocks b ON b.id = p.block_id
         LEFT JOIN farms f1 ON f1.id = b.farm_id
         LEFT JOIN crop_types ct ON ct.id = cc.crop_type_id
         LEFT JOIN animals an ON an.id = tb.animal_id
         LEFT JOIN farms f2 ON f2.id = an.farm_id
         WHERE COALESCE(f1.id, f2.id) IN (" . in_placeholders($farmIds) . ')
         ORDER BY tb.created_at DESC'
    );
    $stmt->execute($farmIds);
    $batches = $stmt->fetchAll();
}

$pageTitle = 'Traceability';
$activePage = 'traceability';
require __DIR__ . '/includes/header.php';
?>

<h1>Traceability</h1>
<p class="muted">A trace batch is created automatically for every crop cycle and every animal registered.</p>

<table>
    <thead><tr><th>Batch code</th><th>Type</th><th>What</th><th>Farm</th><th>Status</th><th></th></tr></thead>
    <tbody>
        <?php if (!$batches): ?><tr><td colspan="6">No trace batches yet.</td></tr><?php endif; ?>
        <?php foreach ($batches as $b): ?>
            <tr>
                <td><?= e($b['batch_code']) ?></td>
                <td><?= e($b['batch_type']) ?></td>
                <td><?= e($b['crop_type_name'] ?? $b['species'] ?? '—') ?></td>
                <td><?= e($b['farm_name'] ?? '—') ?></td>
                <td><?= e($b['status']) ?></td>
                <td><a href="<?= BASE_URL ?>/admin/trace-view.php?id=<?= (int) $b['id'] ?>">Manage</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Livestock</h4>
    <?php if (Auth::hasPermission('livestock.create')): ?>
    <a href="/livestock/create" class="btn btn-success"><i class="bi bi-plus-lg"></i> Register Animal</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr><th>Animal ID</th><th>Name</th><th>Species / Breed</th><th>Gender</th><th>Farm</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (empty($animals)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No animals registered yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($animals as $a): ?>
                <tr>
                    <td><a href="/livestock/<?= (int) $a['id'] ?>" class="font-monospace"><?= htmlspecialchars($a['animal_code']) ?></a></td>
                    <td><?= htmlspecialchars($a['name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($a['species']) ?><?= $a['breed'] ? ' / ' . htmlspecialchars($a['breed']) : '' ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($a['gender']) ?></td>
                    <td><?= htmlspecialchars($a['farm_name']) ?></td>
                    <td>
                        <?php $badge = ['active' => 'success', 'sold' => 'secondary', 'deceased' => 'dark'][$a['status']] ?? 'secondary'; ?>
                        <span class="badge bg-<?= $badge ?> text-capitalize"><?= htmlspecialchars($a['status']) ?></span>
                    </td>
                    <td class="text-end"><a href="/livestock/<?= (int) $a['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

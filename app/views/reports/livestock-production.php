<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= htmlspecialchars($title) ?></h4>
    <div class="d-flex gap-2">
        <a href="/reports/livestock-production?format=pdf" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        <a href="/reports/livestock-production?format=excel" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        <a href="/reports" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Animal ID</th><th>Species</th><th>Name</th><th>Farm</th><th>Total Production</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?><tr><td colspan="6" class="text-center text-muted py-4">No animals yet.</td></tr><?php endif; ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="font-monospace"><?= htmlspecialchars($r['animal_code']) ?></td>
                    <td><?= htmlspecialchars($r['species']) ?></td>
                    <td><?= htmlspecialchars($r['name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['farm_name']) ?></td>
                    <td><?= htmlspecialchars($r['total_production']) ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($r['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

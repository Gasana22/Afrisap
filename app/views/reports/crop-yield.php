<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= htmlspecialchars($title) ?></h4>
    <div class="d-flex gap-2">
        <a href="/reports/crop-yield?format=pdf" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        <a href="/reports/crop-yield?format=excel" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        <a href="/reports" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Batch Code</th><th>Crop Type</th><th>Farm</th><th>Plot</th><th>Hectares</th><th>Total Yield</th><th>Yield/Hectare</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?><tr><td colspan="8" class="text-center text-muted py-4">No crop cycles yet.</td></tr><?php endif; ?>
                <?php foreach ($rows as $r): $perHectare = $r['size_hectares'] > 0 ? round($r['total_yield'] / $r['size_hectares'], 2) : 0; ?>
                <tr>
                    <td class="font-monospace"><?= htmlspecialchars($r['batch_code']) ?></td>
                    <td><?= htmlspecialchars($r['crop_type']) ?></td>
                    <td><?= htmlspecialchars($r['farm_name']) ?></td>
                    <td><?= htmlspecialchars($r['plot_code']) ?></td>
                    <td><?= htmlspecialchars($r['size_hectares'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($r['total_yield']) ?></td>
                    <td><?= $perHectare ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($r['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

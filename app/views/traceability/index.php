<h4 class="mb-4">Traceability</h4>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Batch Code</th><th>Type</th><th>Product</th><th>Farm</th><th>Status</th><th>QR</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($batches)): ?><tr><td colspan="7" class="text-center text-muted py-4">No batches yet. Crop cycles and animals automatically get a traceability record when created.</td></tr><?php endif; ?>
                <?php foreach ($batches as $b): ?>
                <tr>
                    <td><a href="/traceability/<?= (int) $b['id'] ?>" class="font-monospace"><?= htmlspecialchars($b['batch_code']) ?></a></td>
                    <td class="text-capitalize"><?= htmlspecialchars($b['batch_type']) ?></td>
                    <td><?= htmlspecialchars($b['product_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($b['farm_name'] ?? '—') ?></td>
                    <td>
                        <?php $statusBadge = ['active' => 'success', 'completed' => 'secondary', 'recalled' => 'danger'][$b['status']] ?? 'secondary'; ?>
                        <span class="badge bg-<?= $statusBadge ?> text-capitalize"><?= htmlspecialchars($b['status']) ?></span>
                    </td>
                    <td><?= $b['has_qr'] ? '<i class="bi bi-qr-code text-success"></i>' : '<span class="text-muted small">not generated</span>' ?></td>
                    <td class="text-end"><a href="/traceability/<?= (int) $b['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

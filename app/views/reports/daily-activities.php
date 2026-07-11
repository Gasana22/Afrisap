<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= htmlspecialchars($title) ?></h4>
    <div class="d-flex gap-2">
        <a href="/reports/daily-activities?format=pdf&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        <a href="/reports/daily-activities?format=excel&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        <a href="/reports" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<form method="get" action="/reports/daily-activities" class="row g-2 mb-3">
    <div class="col-md-3"><input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>"></div>
    <div class="col-md-3"><input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>"></div>
    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-success w-100">Filter</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Date</th><th>Batch Code</th><th>Farm</th><th>Activity</th><th>Worker</th><th>Status</th><th class="text-end">Cost</th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted py-4">No activities in this date range.</td></tr><?php endif; ?>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['activity_date']) ?></td>
                    <td class="font-monospace"><?= htmlspecialchars($r['batch_code']) ?></td>
                    <td><?= htmlspecialchars($r['farm_name']) ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($r['activity_type']) ?></td>
                    <td><?= htmlspecialchars($r['worker_name'] ?? '—') ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($r['status']) ?></td>
                    <td class="text-end"><?= htmlspecialchars($r['cost'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<h4 class="mb-4">Platform Audit Log</h4>
<p class="text-muted small mb-3">Cross-tenant activity — every organization's actions, in one place.</p>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead>
                <tr><th>Date</th><th>Organization</th><th>User</th><th>Action</th><th>Table</th><th>Record</th><th>IP</th></tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No audit records yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="text-nowrap"><?= htmlspecialchars($log['created_at']) ?></td>
                    <td><?= htmlspecialchars($log['organization_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                    <td><?= htmlspecialchars($log['table_name']) ?></td>
                    <td><?= htmlspecialchars($log['record_id'] ?? '—') ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="/platform/audit-logs?page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

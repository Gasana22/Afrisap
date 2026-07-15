<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$tableFilter = clean_string($_GET['table_name'] ?? '');
$actionFilter = clean_string($_GET['action'] ?? '');

$tableOptions = db_all(
    'SELECT DISTINCT table_name FROM audit_logs WHERE organization_id = :org AND table_name IS NOT NULL ORDER BY table_name ASC',
    ['org' => $orgId]
);
$actionOptions = db_all(
    'SELECT DISTINCT action FROM audit_logs WHERE organization_id = :org ORDER BY action ASC',
    ['org' => $orgId]
);

$logs = audit_trail($orgId, $tableFilter !== '' ? $tableFilter : null, 200);

if ($actionFilter !== '') {
    $logs = array_values(array_filter($logs, fn ($log) => $log['action'] === $actionFilter));
}

render_header(['title' => 'Audit Trail', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/traceability/batches.php') ?>">Traceability</a></li>
        <li class="breadcrumb-item active">Audit Trail</li>
      </ol></nav>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Audit Trail</h4>
          <p class="text-muted small mb-0">Who changed what, and when, across your organization's data.</p>
        </div>
      </div>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Table</label>
            <select name="table_name" class="form-select">
              <option value="">All Tables</option>
              <?php foreach ($tableOptions as $t): ?>
                <option value="<?= e($t['table_name']) ?>" <?= $tableFilter === $t['table_name'] ? 'selected' : '' ?>><?= e(humanize($t['table_name'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small">Action</label>
            <select name="action" class="form-select">
              <option value="">All Actions</option>
              <?php foreach ($actionOptions as $a): ?>
                <option value="<?= e($a['action']) ?>" <?= $actionFilter === $a['action'] ? 'selected' : '' ?>><?= e(humanize($a['action'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Filter</button>
            <a href="<?= base_url('org-admin/traceability/audit.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="content-card">
        <?php if (!$logs): ?>
          <?php render_empty_state('No audit log entries match your filters.', 'bi-clock-history'); ?>
        <?php else: ?>
          <table class="table-app">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Table</th>
                <th>Record ID</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td class="text-nowrap"><?= format_date($log['created_at'], 'd M Y H:i:s') ?></td>
                  <td><?= e($log['user_name'] ?: 'System') ?></td>
                  <td><span class="badge <?= status_badge_class($log['action']) ?>"><?= e(humanize($log['action'])) ?></span></td>
                  <td><?= e(humanize($log['table_name'] ?: '-')) ?></td>
                  <td><?= $log['record_id'] !== null ? (int) $log['record_id'] : '-' ?></td>
                  <td class="text-end">
                    <?php if ($log['old_values'] || $log['new_values']): ?>
                      <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#logDetail<?= $log['id'] ?>">
                        <i class="bi bi-eye"></i> Details
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php if ($log['old_values'] || $log['new_values']): ?>
                  <tr class="collapse" id="logDetail<?= $log['id'] ?>">
                    <td colspan="6">
                      <div class="row g-2">
                        <?php if ($log['old_values']): ?>
                          <div class="col-md-6">
                            <div class="small text-muted mb-1">Old Values</div>
                            <pre class="small bg-light p-2 rounded"><?= e(json_encode(json_decode($log['old_values']), JSON_PRETTY_PRINT)) ?></pre>
                          </div>
                        <?php endif; ?>
                        <?php if ($log['new_values']): ?>
                          <div class="col-md-6">
                            <div class="small text-muted mb-1">New Values</div>
                            <pre class="small bg-light p-2 rounded"><?= e(json_encode(json_decode($log['new_values']), JSON_PRETTY_PRINT)) ?></pre>
                          </div>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>

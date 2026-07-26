<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$farmId = (int) clean_int($_GET['farm_id'] ?? 0);

$extraSql = '';
$params = [];
if ($farmId) {
    $extraSql = ' AND t.farm_id = :farm_id';
    $params['farm_id'] = $farmId;
}

$tasks = db_all(
    "SELECT t.*, w.name AS worker_name FROM tasks t
     JOIN workers w ON w.id = t.assigned_to
     WHERE t.organization_id = :org_id $extraSql
     ORDER BY t.deadline ASC",
    array_merge(['org_id' => $orgId], $params)
);

$farms = tenant_all('farms', $orgId, 'ORDER BY name ASC');

$columns = [
    'pending' => 'Pending',
    'in_progress' => 'In Progress',
    'completed' => 'Completed',
    'verified' => 'Verified',
];

$grouped = array_fill_keys(array_keys($columns), []);
foreach ($tasks as $task) {
    if (isset($grouped[$task['status']])) {
        $grouped[$task['status']][] = $task;
    }
}

render_header(['title' => 'Task Board', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Task Board</h4>
          <p class="text-muted small mb-0">Track task progress across your workforce</p>
        </div>
        <a href="<?= base_url('org-admin/workers/assign.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Task</a>
      </div>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Farm</label>
            <select name="farm_id" class="form-select" onchange="this.form.submit()">
              <option value="">All Farms</option>
              <?php foreach ($farms as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= $farmId === (int) $f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      </div>

      <?php if (!$tasks): ?>
        <div class="content-card"><?php render_empty_state('No tasks yet. Assign your first task to get started.', 'bi-list-task'); ?></div>
      <?php else: ?>
        <div class="kanban-board">
          <?php foreach ($columns as $status => $label): ?>
            <div class="kanban-column">
              <h6><?= e($label) ?> (<?= count($grouped[$status]) ?>)</h6>
              <?php foreach ($grouped[$status] as $task): ?>
                <div class="kanban-card priority-<?= e($task['priority']) ?>">
                  <div class="fw-semibold mb-1"><?= e($task['title']) ?></div>
                  <div class="small mb-1">
                    <span class="badge <?= status_badge_class($task['priority']) ?>"><?= e(humanize($task['priority'])) ?></span>
                  </div>
                  <div class="small text-muted"><i class="bi bi-person"></i> <?= e($task['worker_name']) ?></div>
                  <div class="small text-muted"><i class="bi bi-calendar-event"></i> <?= $task['deadline'] ? format_date($task['deadline']) : 'No deadline' ?></div>
                </div>
              <?php endforeach; ?>
              <?php if (!$grouped[$status]): ?>
                <p class="text-muted small mb-0">No tasks.</p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>

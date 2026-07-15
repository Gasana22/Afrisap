<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$errors = [];
$input = [
    'title' => '', 'description' => '', 'task_type' => '', 'assigned_to' => '',
    'farm_id' => '', 'block_id' => '', 'priority' => 'medium', 'deadline' => '',
];

$activeWorkers = tenant_all('workers', $orgId, "AND status = 'active' ORDER BY name ASC");
$farms = tenant_all('farms', $orgId, 'ORDER BY name ASC');
$blocks = db_all(
    'SELECT b.* FROM blocks b JOIN farms f ON f.id = b.farm_id WHERE f.organization_id = :org_id ORDER BY b.name ASC',
    ['org_id' => $orgId]
);

if (is_post() && csrf_verify()) {
    $input = [
        'title' => clean_string($_POST['title'] ?? ''),
        'description' => clean_string($_POST['description'] ?? ''),
        'task_type' => clean_string($_POST['task_type'] ?? ''),
        'assigned_to' => $_POST['assigned_to'] ?? '',
        'farm_id' => $_POST['farm_id'] ?? '',
        'block_id' => $_POST['block_id'] ?? '',
        'priority' => $_POST['priority'] ?? 'medium',
        'deadline' => $_POST['deadline'] ?? '',
    ];

    $errors = validate($input, [
        'title' => 'required|max:255',
        'task_type' => 'required|max:100',
        'assigned_to' => 'required',
        'priority' => 'in:low,medium,high,urgent',
        'deadline' => 'date',
    ]);

    $assignedToId = clean_int($input['assigned_to']);
    $assignedToWorker = $assignedToId ? tenant_find('workers', $orgId, $assignedToId) : null;
    if (!$assignedToWorker) {
        $errors['assigned_to'] = 'Please select a valid worker.';
    }

    if (!$errors) {
        // tasks.assigned_by is NOT NULL and references workers.id, but the org-admin user
        // performing this action is a `users`/organization_users record, not necessarily a
        // worker. There is no clean way in the current schema to represent "assigned by an
        // org-admin who isn't a worker". Pragmatic workaround: try to find a workers row that
        // is linked to the current user's account; if none exists, fall back to recording the
        // assignee itself as the assigner (self-assigned by the system on the manager's behalf).
        // This is a deliberate compromise, not a bug.
        $managerWorker = db_one(
            'SELECT id FROM workers WHERE organization_id = :org_id AND user_id = :user_id LIMIT 1',
            ['org_id' => $orgId, 'user_id' => $currentUser['id']]
        );
        $assignedBy = $managerWorker ? (int) $managerWorker['id'] : (int) $assignedToWorker['id'];

        $newData = [
            'assigned_to' => (int) $assignedToWorker['id'],
            'assigned_by' => $assignedBy,
            'farm_id' => clean_int($input['farm_id']),
            'block_id' => clean_int($input['block_id']),
            'task_type' => $input['task_type'],
            'title' => $input['title'],
            'description' => $input['description'],
            'priority' => $input['priority'],
            'deadline' => $input['deadline'] ?: null,
        ];
        $taskId = tenant_insert('tasks', $orgId, $newData);
        audit_log($orgId, $currentUser['id'], 'create', 'tasks', $taskId, null, $newData);
        notify($orgId, null, 'task_assigned', 'New Task Assigned', $input['title'] . ' has been assigned.', base_url('org-admin/workers/tasks.php'));
        session_flash('success', 'Task assigned successfully.');
        redirect('org-admin/workers/tasks.php');
    }
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Assign Task', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="content-card" style="max-width:760px;">
        <h5 class="mb-3">Assign New Task</h5>
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required value="<?= e($input['title']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= e($input['description']) ?></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Task Type</label>
              <input type="text" name="task_type" class="form-control" placeholder="e.g. Irrigation, Harvesting" required value="<?= e($input['task_type']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Assign To</label>
              <select name="assigned_to" class="form-select" required>
                <option value="">-- Select worker --</option>
                <?php foreach ($activeWorkers as $w): ?>
                  <option value="<?= (int) $w['id'] ?>" <?= (string) $input['assigned_to'] === (string) $w['id'] ? 'selected' : '' ?>><?= e($w['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!$activeWorkers): ?><p class="text-muted small mt-1">No active workers found. <a href="<?= base_url('org-admin/workers/create.php') ?>">Add one first</a>.</p><?php endif; ?>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Farm <span class="text-muted small">(optional)</span></label>
              <select name="farm_id" id="farmSelect" class="form-select">
                <option value="">-- None --</option>
                <?php foreach ($farms as $f): ?>
                  <option value="<?= (int) $f['id'] ?>" <?= (string) $input['farm_id'] === (string) $f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Block <span class="text-muted small">(optional)</span></label>
              <select name="block_id" id="blockSelect" class="form-select">
                <option value="">-- None --</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Priority</label>
              <select name="priority" class="form-select">
                <?php foreach (['low', 'medium', 'high', 'urgent'] as $p): ?>
                  <option value="<?= $p ?>" <?= $input['priority'] === $p ? 'selected' : '' ?>><?= e(humanize($p)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Deadline</label>
              <input type="date" name="deadline" class="form-control" value="<?= e((string) $input['deadline']) ?>">
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Assign Task</button>
            <a href="<?= base_url('org-admin/workers/tasks.php') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<script>
const blocksByFarm = <?= json_encode(array_reduce($blocks, function ($carry, $b) {
    $carry[(int) $b['farm_id']][] = ['id' => (int) $b['id'], 'name' => $b['name']];
    return $carry;
}, [])) ?>;
const selectedBlockId = <?= json_encode($input['block_id'] !== '' ? (int) $input['block_id'] : null) ?>;

function refreshBlocks() {
  const farmId = document.getElementById('farmSelect').value;
  const blockSelect = document.getElementById('blockSelect');
  blockSelect.innerHTML = '<option value="">-- None --</option>';
  const list = blocksByFarm[farmId] || [];
  list.forEach(b => {
    const opt = document.createElement('option');
    opt.value = b.id;
    opt.textContent = b.name;
    if (selectedBlockId && b.id === selectedBlockId) opt.selected = true;
    blockSelect.appendChild(opt);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('farmSelect').addEventListener('change', refreshBlocks);
  refreshBlocks();
});
</script>
<?php render_footer(['context' => 'org-admin']); ?>

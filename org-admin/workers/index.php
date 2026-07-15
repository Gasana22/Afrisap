<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$search = clean_string($_GET['search'] ?? '');
$role = clean_string($_GET['role'] ?? '');
$department = clean_string($_GET['department'] ?? '');

$extraSql = '';
$params = [];
if ($search !== '') {
    $extraSql .= ' AND (name LIKE :search OR employee_id LIKE :search)';
    $params['search'] = '%' . $search . '%';
}
if ($role !== '') {
    $extraSql .= ' AND role = :role';
    $params['role'] = $role;
}
if ($department !== '') {
    $extraSql .= ' AND department = :department';
    $params['department'] = $department;
}
$extraSql .= ' ORDER BY name ASC';

$workers = tenant_all('workers', $orgId, $extraSql, $params);
$roles = db_all('SELECT DISTINCT role FROM workers WHERE organization_id = :org_id AND role IS NOT NULL AND role != "" ORDER BY role', ['org_id' => $orgId]);
$departments = db_all('SELECT DISTINCT department FROM workers WHERE organization_id = :org_id AND department IS NOT NULL AND department != "" ORDER BY department', ['org_id' => $orgId]);

render_header(['title' => 'Workers', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Workers</h4>
          <p class="text-muted small mb-0">Manage your farm workforce</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= base_url('org-admin/workers/tasks.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-kanban"></i> Tasks Board</a>
          <a href="<?= base_url('org-admin/workers/payroll.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-cash-stack"></i> Payroll</a>
          <a href="<?= base_url('org-admin/workers/create.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Worker</a>
        </div>
      </div>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Name or employee ID" value="<?= e($search) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Role</label>
            <select name="role" class="form-select">
              <option value="">All Roles</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?= e($r['role']) ?>" <?= $role === $r['role'] ? 'selected' : '' ?>><?= e($r['role']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Department</label>
            <select name="department" class="form-select">
              <option value="">All Departments</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= e($d['department']) ?>" <?= $department === $d['department'] ? 'selected' : '' ?>><?= e($d['department']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
            <a href="<?= base_url('org-admin/workers/index.php') ?>" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <div class="content-card">
        <?php if (!$workers): ?>
          <?php render_empty_state('No workers found. Add your first worker to get started.', 'bi-person-workspace'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr>
                  <th>Employee ID</th>
                  <th>Name</th>
                  <th>Phone</th>
                  <th>Role</th>
                  <th>Department</th>
                  <th>Hire Date</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($workers as $w): ?>
                  <tr>
                    <td><?= e($w['employee_id'] ?: '-') ?></td>
                    <td><?= e($w['name']) ?></td>
                    <td><?= e($w['phone'] ?: '-') ?></td>
                    <td><?= e($w['role'] ?: '-') ?></td>
                    <td><?= e($w['department'] ?: '-') ?></td>
                    <td><?= format_date($w['hire_date']) ?></td>
                    <td><?php render_status_badge($w['status']); ?></td>
                    <td class="text-end">
                      <a href="<?= base_url('org-admin/workers/view.php?id=' . $w['id']) ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i> View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>

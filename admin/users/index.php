<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../lib.php';

$admin = require_platform_admin();

if (is_post() && csrf_verify()) {
    $id = clean_int($_POST['id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    if ($id && in_array($newStatus, ['active', 'inactive'], true) && $id !== (int) $admin['id']) {
        $user = db_one('SELECT * FROM users WHERE id = :id', ['id' => $id]);
        if ($user) {
            db_update('users', ['status' => $newStatus], 'id = :id', ['id' => $id]);
            audit_log(null, $admin['id'], 'update', 'users', $id, ['status' => $user['status']], ['status' => $newStatus]);
            session_flash('success', 'User status updated to ' . humanize($newStatus) . '.');
        }
    } elseif ($id === (int) $admin['id']) {
        session_flash('error', 'You cannot change your own status.');
    }
    redirect('admin/users/index.php');
}

$search = clean_string($_GET['search'] ?? '');
$where = 'WHERE 1 = 1';
$params = [];
if ($search !== '') {
    $where .= ' AND (name LIKE :search OR email LIKE :search2)';
    $params['search'] = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
}

$pagination = paginate_params(25);
$totalRows = (int) db_value("SELECT COUNT(*) FROM users $where", $params);
$limit = (int) $pagination['per_page'];
$offset = (int) $pagination['offset'];

$users = db_all("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset", $params);

render_header(['title' => 'Platform Users', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <h4 class="mb-3">Platform Users</h4>

      <div class="content-card mb-3">
        <form method="get" class="row g-2 align-items-end">
          <div class="col-md-5">
            <label class="form-label small">Search by name or email</label>
            <input type="text" name="search" class="form-control" value="<?= e($search) ?>">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary w-100">Search</button>
          </div>
        </form>
      </div>

      <div class="content-card">
        <?php if (!$users): ?>
          <?php render_empty_state('No users found.', 'bi-people'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th>Last Login</th><th class="text-end">Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                  <tr>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                      <?php if ($u['is_super_admin']): ?><span class="badge badge-danger">Super Admin</span><?php endif; ?>
                      <?php if ($u['is_admin']): ?><span class="badge badge-info">Admin</span><?php endif; ?>
                      <?php if (!$u['is_admin'] && !$u['is_super_admin']): ?><span class="text-muted small">-</span><?php endif; ?>
                    </td>
                    <td><?php render_status_badge($u['status']); ?></td>
                    <td><?= $u['last_login'] ? format_date($u['last_login'], 'd M Y H:i') : 'Never' ?></td>
                    <td class="text-end">
                      <?php if ((int) $u['id'] !== (int) $admin['id']): ?>
                        <form method="post" class="d-inline">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= $u['id'] ?>">
                          <?php if ($u['status'] === 'active'): ?>
                            <input type="hidden" name="new_status" value="inactive">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Deactivate</button>
                          <?php else: ?>
                            <input type="hidden" name="new_status" value="active">
                            <button type="submit" class="btn btn-sm btn-outline-success">Activate</button>
                          <?php endif; ?>
                        </form>
                      <?php else: ?>
                        <span class="text-muted small">(you)</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php render_pagination($pagination['page'], $pagination['per_page'], $totalRows, base_url('admin/users/index.php'), array_filter(['search' => $search])); ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>

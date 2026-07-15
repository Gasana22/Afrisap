<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../lib.php';

$admin = require_platform_admin();

$pagination = paginate_params(30);
$totalRows = (int) db_value('SELECT COUNT(*) FROM audit_logs');
$limit = (int) $pagination['per_page'];
$offset = (int) $pagination['offset'];

$logs = db_all(
    "SELECT al.*, u.name AS user_name, o.name AS org_name
     FROM audit_logs al
     LEFT JOIN users u ON u.id = al.user_id
     LEFT JOIN organizations o ON o.id = al.organization_id
     ORDER BY al.created_at DESC
     LIMIT $limit OFFSET $offset"
);

render_header(['title' => 'Platform Activity', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <h4 class="mb-3">Platform Activity Log</h4>

      <div class="content-card">
        <?php if (!$logs): ?>
          <?php render_empty_state('No activity recorded yet.', 'bi-clock-history'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr><th>When</th><th>User</th><th>Action</th><th>Table</th><th>Organization</th></tr>
              </thead>
              <tbody>
                <?php foreach ($logs as $log): ?>
                  <tr>
                    <td><?= format_date($log['created_at'], 'd M Y H:i') ?></td>
                    <td><?= e($log['user_name'] ?? 'System') ?></td>
                    <td><?= e(humanize($log['action'])) ?></td>
                    <td><?= e(humanize($log['table_name'] ?? '-')) ?></td>
                    <td><?= e($log['org_name'] ?? '-') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php render_pagination($pagination['page'], $pagination['per_page'], $totalRows, base_url('admin/users/activity.php'), []); ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>

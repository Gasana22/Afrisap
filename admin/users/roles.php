<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$admin = require_platform_admin();

$roleRows = db_all("SELECT setting_key, setting_value FROM system_settings WHERE setting_group = 'roles' ORDER BY setting_key ASC");

$roles = [];
foreach ($roleRows as $row) {
    $role = str_replace('role_permissions.', '', $row['setting_key']);
    $roles[$role] = json_decode($row['setting_value'] ?? '[]', true) ?: [];
}

render_header(['title' => 'Roles & Permissions', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <h4 class="mb-1">Roles &amp; Permissions</h4>
      <p class="text-muted small mb-3">This documents the default organization-level role permission matrix, seeded platform-wide and used as the starting point for every tenant. Read-only here - each organization can further customize under its own Settings.</p>

      <div class="content-card">
        <?php if (!$roles): ?>
          <?php render_empty_state('No role permission data seeded yet.', 'bi-shield-lock'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead><tr><th style="width:220px;">Role</th><th>Permissions</th></tr></thead>
              <tbody>
                <?php foreach ($roles as $role => $permissions): ?>
                  <tr>
                    <td><strong><?= e(humanize($role)) ?></strong></td>
                    <td>
                      <?php if ($permissions === ['*']): ?>
                        <span class="badge badge-success">Full Access (All Permissions)</span>
                      <?php else: ?>
                        <?php foreach ($permissions as $perm): ?>
                          <span class="badge badge-secondary me-1 mb-1"><?= e($perm) ?></span>
                        <?php endforeach; ?>
                      <?php endif; ?>
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
<?php render_footer(['context' => 'admin']); ?>

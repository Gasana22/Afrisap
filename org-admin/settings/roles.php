<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user(['owner']);
$organization = current_organization();
$orgId = (int) $organization['id'];

// --- Default role permission matrix (system-wide defaults) ---
$roleSettingRows = db_all("SELECT setting_key, setting_value FROM system_settings WHERE setting_group = 'roles'");
$defaultRolePermissions = [];
foreach ($roleSettingRows as $row) {
    $role = str_replace('role_permissions.', '', $row['setting_key']);
    $defaultRolePermissions[$role] = json_decode((string) $row['setting_value'], true) ?: [];
}

$errors = [];

// --- Save a custom permissions override for a specific member ---
if (is_post() && csrf_verify()) {
    $membershipId = clean_int($_POST['id'] ?? 0);
    $permissionsRaw = trim($_POST['permissions'] ?? '');
    $membership = db_one('SELECT * FROM organization_users WHERE id = :id AND organization_id = :org', ['id' => $membershipId, 'org' => $orgId]);

    if (!$membership) {
        session_flash('error', 'Member not found.');
        redirect('org-admin/settings/roles.php');
    }

    $decoded = json_decode($permissionsRaw, true);
    if ($permissionsRaw !== '' && json_last_error() !== JSON_ERROR_NONE) {
        $errors['permissions'] = 'Permissions must be valid JSON (e.g. a list like ["farms.view", "crops.*"]).';
        session_flash('error', $errors['permissions']);
        redirect('org-admin/settings/roles.php');
    }

    $newValue = $permissionsRaw === '' ? null : json_encode($decoded);
    db_update('organization_users', ['permissions' => $newValue], 'id = :id', ['id' => $membershipId]);
    audit_log($orgId, $currentUser['id'], 'update', 'organization_users', $membershipId, ['permissions' => $membership['permissions']], ['permissions' => $newValue]);
    session_flash('success', 'Permissions updated for ' . $membershipId . '.');
    redirect('org-admin/settings/roles.php');
}

$members = db_all(
    'SELECT ou.*, u.name, u.email
     FROM organization_users ou
     JOIN users u ON u.id = ou.user_id
     WHERE ou.organization_id = :org
     ORDER BY FIELD(ou.role, "owner") DESC, ou.joined_at ASC',
    ['org' => $orgId]
);

render_header(['title' => 'Roles & Permissions', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="mb-3">
        <h4 class="mb-0">Roles &amp; Permissions</h4>
        <p class="text-muted small mb-0">Review default role permissions and optionally override permissions for a specific member.</p>
      </div>

      <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/profile.php') ?>">Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/branding.php') ?>">Branding</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/users.php') ?>">Users</a></li>
        <li class="nav-item"><a class="nav-link active" href="<?= base_url('org-admin/settings/roles.php') ?>">Roles &amp; Permissions</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/integration.php') ?>">Integrations</a></li>
      </ul>

      <div class="content-card mb-4">
        <div class="content-card-header"><h6>Default Role Permission Matrix</h6></div>
        <?php if (!$defaultRolePermissions): ?>
          <?php render_empty_state('Role permission defaults have not been seeded yet.', 'bi-shield-lock'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-app align-middle">
              <thead><tr><th>Role</th><th>Default Permissions</th></tr></thead>
              <tbody>
                <?php foreach ($defaultRolePermissions as $role => $perms): ?>
                  <tr>
                    <td><?php render_status_badge($role); ?></td>
                    <td class="small">
                      <?php foreach ($perms as $p): ?>
                        <span class="badge bg-light text-dark border me-1"><?= e($p) ?></span>
                      <?php endforeach; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>Member Permission Overrides</h6></div>
        <?php if (!$members): ?>
          <?php render_empty_state('No organization members yet.', 'bi-people'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-app align-middle">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Role</th>
                  <th>Custom Permissions</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($members as $m): ?>
                  <tr>
                    <td><?= e($m['name']) ?> <span class="text-muted small d-block"><?= e($m['email']) ?></span></td>
                    <td><?php render_status_badge($m['role']); ?></td>
                    <td class="small text-muted">
                      <?= $m['permissions'] ? '<span class="badge bg-warning-subtle text-dark">Custom override active</span>' : 'Using role default' ?>
                    </td>
                    <td>
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPerms<?= (int) $m['id'] ?>">
                        <i class="bi bi-pencil"></i> Edit Permissions
                      </button>
                    </td>
                  </tr>
                  <?php
                  $currentPerms = $m['permissions'] ?: json_encode($defaultRolePermissions[$m['role']] ?? []);
                  modal_open('editPerms' . $m['id'], 'Edit Permissions - ' . e($m['name']), 'lg');
                  ?>
                  <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <p class="text-muted small">Enter a JSON list of permission strings (e.g. <code>["farms.view", "crops.*"]</code>). Leave blank to fall back to the <?= e(humanize($m['role'])) ?> role default.</p>
                    <textarea name="permissions" class="form-control font-monospace" rows="8"><?= e($currentPerms) ?></textarea>
                  </form>
                  <?php
                  echo '<div class="modal-footer">';
                  echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>';
                  echo '</div>';
                  modal_close();
                  ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.modal form[method="post"]').forEach((form) => {
    const modal = form.closest('.modal');
    if (!modal) return;
    const footer = modal.querySelector('.modal-footer');
    if (footer && !footer.querySelector('.btn-save-perms')) {
      const btn = document.createElement('button');
      btn.type = 'submit';
      btn.className = 'btn btn-primary btn-save-perms';
      btn.textContent = 'Save Permissions';
      footer.appendChild(btn);
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        form.submit();
      });
    }
  });
});
</script>
<?php render_footer(['context' => 'org-admin']); ?>

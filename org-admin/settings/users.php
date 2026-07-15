<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/mailer.php';

$currentUser = require_org_user(['owner']);
$organization = current_organization();
$orgId = (int) $organization['id'];

const ORG_ROLES = ['owner', 'manager', 'agronomist', 'livestock_manager', 'store_manager', 'accountant', 'viewer', 'worker'];

$errors = [];
$input = ['name' => '', 'email' => '', 'role' => 'viewer'];

// --- Remove a member ---
if (is_post() && ($_POST['_method'] ?? '') === 'DELETE' && csrf_verify()) {
    $membershipId = clean_int($_POST['id'] ?? 0);
    $membership = db_one('SELECT * FROM organization_users WHERE id = :id AND organization_id = :org', ['id' => $membershipId, 'org' => $orgId]);
    if ($membership) {
        db_delete('organization_users', 'id = :id', ['id' => $membershipId]);
        audit_log($orgId, $currentUser['id'], 'delete', 'organization_users', $membershipId, $membership, null);
        session_flash('success', 'Member removed from organization.');
    }
    redirect('org-admin/settings/users.php');
}

// --- Change a member's role ---
if (is_post() && ($_POST['_action'] ?? '') === 'change_role' && csrf_verify()) {
    $membershipId = clean_int($_POST['id'] ?? 0);
    $newRole = clean_string($_POST['role'] ?? '');
    $membership = db_one('SELECT * FROM organization_users WHERE id = :id AND organization_id = :org', ['id' => $membershipId, 'org' => $orgId]);
    if ($membership && in_array($newRole, ORG_ROLES, true)) {
        db_update('organization_users', ['role' => $newRole], 'id = :id', ['id' => $membershipId]);
        audit_log($orgId, $currentUser['id'], 'update', 'organization_users', $membershipId, ['role' => $membership['role']], ['role' => $newRole]);
        session_flash('success', 'Member role updated.');
    }
    redirect('org-admin/settings/users.php');
}

// --- Invite a new member ---
if (is_post() && ($_POST['_action'] ?? '') === 'invite' && csrf_verify()) {
    $input = [
        'name' => clean_string($_POST['name'] ?? ''),
        'email' => clean_string($_POST['email'] ?? ''),
        'role' => clean_string($_POST['role'] ?? 'viewer'),
    ];

    $errors = validate($input, [
        'name' => 'required|max:255',
        'email' => 'required|email',
    ]);
    if (!in_array($input['role'], ORG_ROLES, true)) {
        $errors['role'] = 'Please choose a valid role.';
    }

    if (!$errors) {
        $existingUser = db_one('SELECT * FROM users WHERE email = :email', ['email' => $input['email']]);
        $isNewUser = false;

        if ($existingUser) {
            $userId = (int) $existingUser['id'];
            $alreadyMember = db_one(
                'SELECT id FROM organization_users WHERE organization_id = :org AND user_id = :user',
                ['org' => $orgId, 'user' => $userId]
            );
            if ($alreadyMember) {
                $errors['email'] = 'This person is already a member of your organization.';
            }
        } else {
            $isNewUser = true;
            $tempPassword = bin2hex(random_bytes(8));
            $userId = db_insert('users', [
                'email' => $input['email'],
                'password_hash' => hash_password($tempPassword),
                'name' => $input['name'],
                'status' => 'active',
            ]);
        }

        if (!$errors) {
            $membershipId = db_insert('organization_users', [
                'organization_id' => $orgId,
                'user_id' => $userId,
                'role' => $input['role'],
                'invited_by' => $currentUser['id'],
                'status' => 'active',
            ]);
            audit_log($orgId, $currentUser['id'], 'create', 'organization_users', $membershipId, null, ['user_id' => $userId, 'role' => $input['role']]);

            if ($isNewUser) {
                send_mail(
                    $input['email'],
                    $input['name'],
                    "You've been invited to {$organization['name']}",
                    "<p>Hello {$input['name']},</p>"
                    . "<p>You have been added to <strong>{$organization['name']}</strong> on Smart Farm Platform as a <strong>" . humanize($input['role']) . '</strong>.</p>'
                    . "<p>Your temporary password is: <strong>{$tempPassword}</strong></p>"
                    . '<p>Please log in and change your password as soon as possible.</p>'
                );
            } else {
                send_mail(
                    $input['email'],
                    $input['name'],
                    "You've been added to {$organization['name']}",
                    "<p>Hello {$input['name']},</p>"
                    . "<p>Your existing account has been added to <strong>{$organization['name']}</strong> as a <strong>" . humanize($input['role']) . '</strong>. You can switch to this organization next time you log in.</p>'
                );
            }

            session_flash('success', 'Invitation sent.');
            redirect('org-admin/settings/users.php');
        }
    }
}

$GLOBALS['_page_errors'] = $errors;

$members = db_all(
    'SELECT ou.*, u.name, u.email, u.status AS user_status
     FROM organization_users ou
     JOIN users u ON u.id = ou.user_id
     WHERE ou.organization_id = :org
     ORDER BY FIELD(ou.role, "owner") DESC, ou.joined_at ASC',
    ['org' => $orgId]
);

render_header(['title' => 'Organization Users', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="mb-3">
        <h4 class="mb-0">Organization Users</h4>
        <p class="text-muted small mb-0">Manage who has access to your organization and what role they hold.</p>
      </div>

      <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/profile.php') ?>">Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/branding.php') ?>">Branding</a></li>
        <li class="nav-item"><a class="nav-link active" href="<?= base_url('org-admin/settings/users.php') ?>">Users</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/roles.php') ?>">Roles &amp; Permissions</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/integration.php') ?>">Integrations</a></li>
      </ul>

      <div class="content-card mb-4">
        <h5 class="mb-3">Invite a User</h5>
        <form method="post" class="row g-2 align-items-end">
          <?= csrf_field() ?>
          <input type="hidden" name="_action" value="invite">
          <div class="col-md-3">
            <label class="form-label small">Name</label>
            <input type="text" name="name" class="form-control" required value="<?= e($input['name']) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Email</label>
            <input type="email" name="email" class="form-control" required value="<?= e($input['email']) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label small">Role</label>
            <select name="role" class="form-select">
              <?php foreach (ORG_ROLES as $role): ?>
                <option value="<?= e($role) ?>" <?= $input['role'] === $role ? 'selected' : '' ?>><?= e(humanize($role)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-envelope-plus"></i> Invite User</button>
          </div>
        </form>
      </div>

      <div class="content-card">
        <div class="content-card-header"><h6>Members (<?= count($members) ?>)</h6></div>
        <?php if (!$members): ?>
          <?php render_empty_state('No organization members yet.', 'bi-people'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-app align-middle">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Joined</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($members as $m): ?>
                  <tr>
                    <td><?= e($m['name']) ?></td>
                    <td><?= e($m['email']) ?></td>
                    <td>
                      <?php if ($m['role'] === 'owner'): ?>
                        <?php render_status_badge('owner'); ?>
                      <?php else: ?>
                        <form method="post" class="d-flex gap-1">
                          <?= csrf_field() ?>
                          <input type="hidden" name="_action" value="change_role">
                          <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                          <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach (ORG_ROLES as $role): ?>
                              <option value="<?= e($role) ?>" <?= $m['role'] === $role ? 'selected' : '' ?>><?= e(humanize($role)) ?></option>
                            <?php endforeach; ?>
                          </select>
                        </form>
                      <?php endif; ?>
                    </td>
                    <td><?php render_status_badge($m['status']); ?></td>
                    <td><?= format_date($m['joined_at']) ?></td>
                    <td>
                      <?php if ($m['role'] !== 'owner'): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#removeMember<?= (int) $m['id'] ?>"><i class="bi bi-trash"></i></button>
                        <?php confirm_delete_modal('removeMember' . $m['id'], base_url('org-admin/settings/users.php'), e($m['name']), ['id' => $m['id']]); ?>
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
<?php render_footer(['context' => 'org-admin']); ?>

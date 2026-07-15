<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user(['owner', 'manager']);
$organization = current_organization();
$orgId = (int) $organization['id'];

$errors = [];
$input = [
    'name' => $organization['name'] ?? '',
    'email' => $organization['email'] ?? '',
    'phone' => $organization['phone'] ?? '',
];

if (is_post() && csrf_verify()) {
    $input = [
        'name' => clean_string($_POST['name'] ?? ''),
        'email' => clean_string($_POST['email'] ?? ''),
        'phone' => clean_string($_POST['phone'] ?? ''),
    ];

    $errors = validate($input, [
        'name' => 'required|max:255',
        'email' => 'required|email',
    ]);

    if (!$errors) {
        $old = ['name' => $organization['name'], 'email' => $organization['email'], 'phone' => $organization['phone']];
        db_update('organizations', [
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
        ], 'id = :id', ['id' => $orgId]);
        audit_log($orgId, $currentUser['id'], 'update', 'organizations', $orgId, $old, $input);
        session_flash('success', 'Organization profile updated.');
        redirect('org-admin/settings/profile.php');
    }
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Organization Profile', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="mb-3">
        <h4 class="mb-0">Organization Profile</h4>
        <p class="text-muted small mb-0">Basic details about your organization.</p>
      </div>

      <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" href="<?= base_url('org-admin/settings/profile.php') ?>">Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/branding.php') ?>">Branding</a></li>
        <?php if (($currentUser['role'] ?? '') === 'owner'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/users.php') ?>">Users</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/roles.php') ?>">Roles &amp; Permissions</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/integration.php') ?>">Integrations</a></li>
      </ul>

      <div class="content-card" style="max-width:720px;">
        <h5 class="mb-3">Organization Details</h5>
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Organization Name</label>
            <input type="text" name="name" class="form-control" required value="<?= e($input['name']) ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required value="<?= e($input['email']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= e($input['phone']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Subscription Plan</label>
            <input type="text" class="form-control" value="<?= e(humanize($organization['subscription_plan'])) ?>" disabled>
          </div>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>

<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$errors = [];
$input = [
    'name' => '', 'phone' => '', 'email' => '', 'role' => '', 'department' => '',
    'hire_date' => '', 'hourly_rate' => '', 'emergency_contact' => '',
    'account_option' => 'none', 'existing_user_id' => '', 'login_email' => '', 'login_password' => '',
];

// Users already linked to a worker record in this org - excluded from the "link existing user" list.
$linkedUserIds = array_column(db_all('SELECT user_id FROM workers WHERE organization_id = :org_id AND user_id IS NOT NULL', ['org_id' => $orgId]), 'user_id');
$availableUsers = db_all(
    'SELECT u.id, u.name, u.email FROM users u
     JOIN organization_users ou ON ou.user_id = u.id AND ou.organization_id = :org_id
     ORDER BY u.name',
    ['org_id' => $orgId]
);
$availableUsers = array_values(array_filter($availableUsers, fn ($u) => !in_array((int) $u['id'], array_map('intval', $linkedUserIds), true)));

if (is_post() && csrf_verify()) {
    $input = [
        'name' => clean_string($_POST['name'] ?? ''),
        'phone' => clean_string($_POST['phone'] ?? ''),
        'email' => clean_string($_POST['email'] ?? ''),
        'role' => clean_string($_POST['role'] ?? ''),
        'department' => clean_string($_POST['department'] ?? ''),
        'hire_date' => $_POST['hire_date'] ?? '',
        'hourly_rate' => $_POST['hourly_rate'] ?? '',
        'emergency_contact' => clean_string($_POST['emergency_contact'] ?? ''),
        'account_option' => $_POST['account_option'] ?? 'none',
        'existing_user_id' => $_POST['existing_user_id'] ?? '',
        'login_email' => clean_string($_POST['login_email'] ?? ''),
        'login_password' => (string) ($_POST['login_password'] ?? ''),
    ];

    $errors = validate($input, [
        'name' => 'required|max:255',
        'email' => 'email',
        'hourly_rate' => 'numeric',
        'hire_date' => 'date',
    ]);

    $linkUserId = null;

    if ($input['account_option'] === 'existing') {
        $candidate = clean_int($input['existing_user_id']);
        if (!$candidate) {
            $errors['existing_user_id'] = 'Please choose a user to link.';
        } else {
            $linkUserId = $candidate;
        }
    } elseif ($input['account_option'] === 'new') {
        $loginErrors = validate([
            'login_email' => $input['login_email'],
            'login_password' => $input['login_password'],
        ], [
            'login_email' => 'required|email',
            'login_password' => 'required|min:8',
        ]);
        $errors = array_merge($errors, $loginErrors);

        if (!$loginErrors && db_one('SELECT id FROM users WHERE email = :email', ['email' => $input['login_email']])) {
            $errors['login_email'] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        try {
            db()->beginTransaction();

            if ($input['account_option'] === 'new') {
                $linkUserId = db_insert('users', [
                    'email' => $input['login_email'],
                    'password_hash' => hash_password($input['login_password']),
                    'name' => $input['name'],
                    'phone' => $input['phone'],
                    'status' => 'active',
                ]);
                db_insert('organization_users', [
                    'organization_id' => $orgId,
                    'user_id' => $linkUserId,
                    'role' => 'worker',
                    'status' => 'active',
                ]);
            }

            $newData = [
                'employee_id' => generate_employee_id(),
                'user_id' => $linkUserId,
                'name' => $input['name'],
                'phone' => $input['phone'],
                'email' => $input['email'],
                'role' => $input['role'],
                'department' => $input['department'],
                'hire_date' => $input['hire_date'] ?: null,
                'hourly_rate' => clean_float($input['hourly_rate']),
                'emergency_contact' => $input['emergency_contact'],
                'created_by' => $currentUser['id'],
            ];
            $workerId = tenant_insert('workers', $orgId, $newData);

            db()->commit();

            audit_log($orgId, $currentUser['id'], 'create', 'workers', $workerId, null, $newData);
            session_flash('success', 'Worker added successfully.');
            redirect('org-admin/workers/view.php?id=' . $workerId);
        } catch (Throwable $e) {
            db()->rollBack();
            app_log_error('Worker creation failed: ' . $e->getMessage());
            $errors['general'] = 'Something went wrong while adding the worker. Please try again.';
        }
    }
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Add Worker', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="content-card" style="max-width:820px;">
        <h5 class="mb-3">Add New Worker</h5>
        <form method="post">
          <?= csrf_field() ?>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" required value="<?= e($input['name']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= e($input['phone']) ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= e($input['email']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Role</label>
              <input type="text" name="role" class="form-control" placeholder="e.g. Field Worker" value="<?= e($input['role']) ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Department</label>
              <input type="text" name="department" class="form-control" placeholder="e.g. Crop Production" value="<?= e($input['department']) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Hire Date</label>
              <input type="date" name="hire_date" class="form-control" value="<?= e((string) $input['hire_date']) ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Hourly Rate</label>
              <input type="number" step="0.01" name="hourly_rate" class="form-control" value="<?= e((string) $input['hourly_rate']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Emergency Contact</label>
            <input type="text" name="emergency_contact" class="form-control" placeholder="Name and phone number" value="<?= e($input['emergency_contact']) ?>">
          </div>

          <hr>
          <h6 class="mb-2">Platform Login (optional)</h6>
          <p class="text-muted small">Most workers do not need a portal login. You can link this worker to an existing platform user, create a brand-new login for them, or skip this entirely.</p>
          <div class="mb-3">
            <select name="account_option" id="accountOption" class="form-select">
              <option value="none" <?= $input['account_option'] === 'none' ? 'selected' : '' ?>>No portal login</option>
              <option value="existing" <?= $input['account_option'] === 'existing' ? 'selected' : '' ?>>Link an existing user</option>
              <option value="new" <?= $input['account_option'] === 'new' ? 'selected' : '' ?>>Create a new worker login</option>
            </select>
          </div>
          <div id="existingUserField" class="mb-3" style="display:none;">
            <label class="form-label">Existing User</label>
            <select name="existing_user_id" class="form-select">
              <option value="">-- Select a user --</option>
              <?php foreach ($availableUsers as $u): ?>
                <option value="<?= (int) $u['id'] ?>" <?= (string) $input['existing_user_id'] === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <?php if (!$availableUsers): ?><p class="text-muted small mt-1">No unlinked platform users found in your organization.</p><?php endif; ?>
          </div>
          <div id="newLoginFields" class="row" style="display:none;">
            <div class="col-md-6 mb-3">
              <label class="form-label">Login Email</label>
              <input type="email" name="login_email" class="form-control" value="<?= e($input['login_email']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Login Password</label>
              <input type="password" name="login_password" class="form-control" placeholder="Min 8 characters">
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save Worker</button>
            <a href="<?= base_url('org-admin/workers/index.php') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const select = document.getElementById('accountOption');
  const existing = document.getElementById('existingUserField');
  const newFields = document.getElementById('newLoginFields');
  function sync() {
    existing.style.display = select.value === 'existing' ? '' : 'none';
    newFields.style.display = select.value === 'new' ? '' : 'none';
  }
  select.addEventListener('change', sync);
  sync();
});
</script>
<?php render_footer(['context' => 'org-admin']); ?>

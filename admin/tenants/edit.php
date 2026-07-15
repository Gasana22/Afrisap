<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$admin = require_platform_admin();

$id = clean_int($_GET['id'] ?? 0);
$organization = db_one('SELECT * FROM organizations WHERE id = :id', ['id' => $id]);

if (!$organization) {
    session_flash('error', 'Organization not found.');
    redirect('admin/tenants/index.php');
}

$plans = db_all('SELECT * FROM subscription_plans ORDER BY price ASC');

$errors = [];
$input = $organization;

if (is_post() && csrf_verify()) {
    $input = array_merge($input, [
        'name' => clean_string($_POST['name'] ?? ''),
        'email' => clean_string($_POST['email'] ?? ''),
        'phone' => clean_string($_POST['phone'] ?? ''),
        'subscription_plan' => clean_string($_POST['subscription_plan'] ?? 'free'),
    ]);

    $errors = validate($input, [
        'name' => 'required|max:255',
        'email' => 'email',
        'subscription_plan' => 'required|in:free,basic,professional,enterprise',
    ]);

    if (!$errors) {
        db_update('organizations', [
            'name' => $input['name'],
            'email' => $input['email'] ?: null,
            'phone' => $input['phone'] ?: null,
            'subscription_plan' => $input['subscription_plan'],
        ], 'id = :id', ['id' => $id]);

        audit_log($id, $admin['id'], 'update', 'organizations', $id, $organization, $input);
        session_flash('success', 'Organization updated.');
        redirect('admin/tenants/view.php?id=' . $id);
    }
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Edit Organization', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="content-card" style="max-width:720px;">
        <h5 class="mb-3">Edit Organization</h5>
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Organization Name</label>
            <input type="text" name="name" class="form-control" required value="<?= e($input['name']) ?>">
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= e($input['email']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= e($input['phone']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Subscription Plan</label>
            <select name="subscription_plan" class="form-select">
              <?php foreach ($plans as $plan): ?>
                <option value="<?= e($plan['slug']) ?>" <?= $input['subscription_plan'] === $plan['slug'] ? 'selected' : '' ?>><?= e($plan['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= base_url('admin/tenants/view.php?id=' . $id) ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>

<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$admin = require_platform_admin();

$editId = clean_int($_GET['edit'] ?? 0);
$editingPlan = $editId ? db_one('SELECT * FROM subscription_plans WHERE id = :id', ['id' => $editId]) : null;

$errors = [];
$input = $editingPlan ?: [
    'name' => '', 'slug' => '', 'price' => '', 'billing_period' => 'monthly',
    'max_farms' => '', 'max_users' => '', 'max_storage_mb' => '', 'features' => '', 'is_active' => 1,
];
if ($editingPlan) {
    $input['features'] = implode(', ', json_decode($editingPlan['features'] ?? '[]', true) ?: []);
}

if (is_post() && ($_POST['_method'] ?? '') === 'DELETE' && csrf_verify()) {
    $id = clean_int($_POST['id'] ?? 0);
    db_delete('subscription_plans', 'id = :id', ['id' => $id]);
    audit_log(null, $admin['id'], 'delete', 'subscription_plans', $id);
    session_flash('success', 'Plan deleted.');
    redirect('admin/subscriptions/plans.php');
}

if (is_post() && ($_POST['_method'] ?? '') !== 'DELETE' && csrf_verify()) {
    $id = clean_int($_POST['id'] ?? 0);
    $input = [
        'name' => clean_string($_POST['name'] ?? ''),
        'slug' => clean_string($_POST['slug'] ?? ''),
        'price' => $_POST['price'] ?? '',
        'billing_period' => clean_string($_POST['billing_period'] ?? 'monthly'),
        'max_farms' => $_POST['max_farms'] ?? '',
        'max_users' => $_POST['max_users'] ?? '',
        'max_storage_mb' => $_POST['max_storage_mb'] ?? '',
        'features' => clean_string($_POST['features'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($input['slug'] === '') {
        $input['slug'] = slugify($input['name']);
    }

    $errors = validate($input, [
        'name' => 'required|max:255',
        'slug' => 'required|max:50',
        'price' => 'required|numeric',
        'billing_period' => 'required|in:monthly,yearly',
        'max_farms' => 'numeric',
        'max_users' => 'numeric',
        'max_storage_mb' => 'numeric',
    ]);

    if (!$errors) {
        $data = [
            'name' => $input['name'],
            'slug' => $input['slug'],
            'price' => clean_float($input['price']),
            'billing_period' => $input['billing_period'],
            'max_farms' => clean_int($input['max_farms']) ?? 1,
            'max_users' => clean_int($input['max_users']) ?? 5,
            'max_storage_mb' => clean_int($input['max_storage_mb']) ?? 500,
            'features' => json_encode(array_values(array_filter(array_map('trim', explode(',', $input['features']))))),
            'is_active' => $input['is_active'],
        ];

        if ($id) {
            db_update('subscription_plans', $data, 'id = :id', ['id' => $id]);
            audit_log(null, $admin['id'], 'update', 'subscription_plans', $id, null, $data);
            session_flash('success', 'Plan updated.');
        } else {
            $newId = db_insert('subscription_plans', $data);
            audit_log(null, $admin['id'], 'create', 'subscription_plans', $newId, null, $data);
            session_flash('success', 'Plan created.');
        }
        redirect('admin/subscriptions/plans.php');
    }
}

$GLOBALS['_page_errors'] = $errors;

$plans = db_all(
    'SELECT sp.*, (SELECT COUNT(*) FROM organizations o WHERE o.subscription_plan = sp.slug) AS tenant_count
     FROM subscription_plans sp ORDER BY sp.price ASC'
);

render_header(['title' => 'Subscription Plans', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <h4 class="mb-3">Subscription Plans</h4>

      <div class="row g-3">
        <div class="col-lg-5">
          <div class="content-card">
            <h6 class="mb-3"><?= $editingPlan ? 'Edit Plan: ' . e($editingPlan['name']) : 'Add New Plan' ?></h6>
            <form method="post">
              <?= csrf_field() ?>
              <?php if ($editingPlan): ?><input type="hidden" name="id" value="<?= $editingPlan['id'] ?>"><?php endif; ?>
              <div class="mb-2">
                <label class="form-label small">Plan Name</label>
                <input type="text" name="name" class="form-control" required value="<?= e($input['name']) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label small">Slug</label>
                <input type="text" name="slug" class="form-control" placeholder="auto-generated if left blank" value="<?= e($input['slug']) ?>">
              </div>
              <div class="row">
                <div class="col-6 mb-2">
                  <label class="form-label small">Price</label>
                  <input type="number" step="0.01" name="price" class="form-control" required value="<?= e((string) $input['price']) ?>">
                </div>
                <div class="col-6 mb-2">
                  <label class="form-label small">Billing Period</label>
                  <select name="billing_period" class="form-select">
                    <option value="monthly" <?= $input['billing_period'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="yearly" <?= $input['billing_period'] === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-4 mb-2">
                  <label class="form-label small">Max Farms</label>
                  <input type="number" name="max_farms" class="form-control" value="<?= e((string) $input['max_farms']) ?>">
                </div>
                <div class="col-4 mb-2">
                  <label class="form-label small">Max Users</label>
                  <input type="number" name="max_users" class="form-control" value="<?= e((string) $input['max_users']) ?>">
                </div>
                <div class="col-4 mb-2">
                  <label class="form-label small">Max Storage (MB)</label>
                  <input type="number" name="max_storage_mb" class="form-control" value="<?= e((string) $input['max_storage_mb']) ?>">
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label small">Features (comma-separated)</label>
                <textarea name="features" class="form-control" rows="2" placeholder="crop_management, livestock_management, ..."><?= e($input['features']) ?></textarea>
              </div>
              <div class="form-check mb-3">
                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= ($input['is_active'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="isActive">Active</label>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= $editingPlan ? 'Update Plan' : 'Create Plan' ?></button>
                <?php if ($editingPlan): ?>
                  <a href="<?= base_url('admin/subscriptions/plans.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="content-card">
            <h6 class="mb-3">All Plans</h6>
            <?php if (!$plans): ?>
              <?php render_empty_state('No subscription plans yet.'); ?>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-app">
                  <thead><tr><th>Name</th><th>Price</th><th>Limits</th><th>Tenants</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                  <tbody>
                    <?php foreach ($plans as $plan): ?>
                      <tr>
                        <td><?= e($plan['name']) ?><div class="small text-muted"><?= e($plan['slug']) ?></div></td>
                        <td><?= format_money((float) $plan['price'], $plan['currency']) ?>/<?= $plan['billing_period'] === 'monthly' ? 'mo' : 'yr' ?></td>
                        <td class="small"><?= (int) $plan['max_farms'] ?> farms &middot; <?= (int) $plan['max_users'] ?> users</td>
                        <td><?= (int) $plan['tenant_count'] ?></td>
                        <td><?php render_status_badge($plan['is_active'] ? 'active' : 'inactive'); ?></td>
                        <td class="text-end">
                          <a href="<?= base_url('admin/subscriptions/plans.php?edit=' . $plan['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                          <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delPlan<?= $plan['id'] ?>"><i class="bi bi-trash"></i></button>
                        </td>
                      </tr>
                      <?php confirm_delete_modal('delPlan' . $plan['id'], base_url('admin/subscriptions/plans.php'), e($plan['name']), ['id' => $plan['id']]); ?>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'admin']); ?>

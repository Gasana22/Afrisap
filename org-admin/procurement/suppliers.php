<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$errors = [];

if (is_post() && csrf_verify()) {
    if (($_POST['_method'] ?? '') === 'DELETE') {
        $id = clean_int($_POST['id'] ?? 0);
        $existing = tenant_find('suppliers', $orgId, $id);
        if ($existing) {
            tenant_delete('suppliers', $orgId, $id);
            audit_log($orgId, $currentUser['id'], 'delete', 'suppliers', $id, $existing, null);
            session_flash('success', 'Supplier deleted.');
        }
        redirect('org-admin/procurement/suppliers.php');
    }

    $id = clean_int($_POST['id'] ?? 0);
    $input = [
        'name' => clean_string($_POST['name'] ?? ''),
        'contact_person' => clean_string($_POST['contact_person'] ?? ''),
        'phone' => clean_string($_POST['phone'] ?? ''),
        'email' => clean_string($_POST['email'] ?? ''),
        'address' => clean_string($_POST['address'] ?? ''),
        'tax_id' => clean_string($_POST['tax_id'] ?? ''),
        'rating' => $_POST['rating'] ?? '1',
        'payment_terms' => clean_string($_POST['payment_terms'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
    ];

    $errors = validate($input, [
        'name' => 'required|max:255',
        'email' => 'email',
        'rating' => 'numeric',
        'status' => 'in:active,inactive',
    ]);

    if (!$errors) {
        $data = [
            'name' => $input['name'],
            'contact_person' => $input['contact_person'] ?: null,
            'phone' => $input['phone'] ?: null,
            'email' => $input['email'] ?: null,
            'address' => $input['address'] ?: null,
            'tax_id' => $input['tax_id'] ?: null,
            'rating' => max(1, min(5, (int) $input['rating'])),
            'payment_terms' => $input['payment_terms'] ?: null,
            'status' => $input['status'],
        ];

        if ($id) {
            $existing = tenant_find('suppliers', $orgId, $id);
            if ($existing) {
                tenant_update('suppliers', $orgId, $id, $data);
                audit_log($orgId, $currentUser['id'], 'update', 'suppliers', $id, $existing, $data);
                session_flash('success', 'Supplier updated.');
            }
        } else {
            $data['created_by'] = $currentUser['id'];
            $newId = tenant_insert('suppliers', $orgId, $data);
            audit_log($orgId, $currentUser['id'], 'create', 'suppliers', $newId, null, $data);
            session_flash('success', 'Supplier added.');
        }
        redirect('org-admin/procurement/suppliers.php');
    }
}

$GLOBALS['_page_errors'] = $errors;
$suppliers = tenant_all('suppliers', $orgId, 'ORDER BY name ASC');

render_header(['title' => 'Suppliers', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Suppliers</h4>
          <p class="text-muted small mb-0">Manage the vendors you buy farm supplies from</p>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= base_url('org-admin/procurement/orders.php') ?>" class="btn btn-outline-secondary"><i class="bi bi-cart"></i> Purchase Orders</a>
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal"><i class="bi bi-plus-lg"></i> Add Supplier</button>
        </div>
      </div>

      <div class="content-card">
        <?php if (!$suppliers): ?>
          <?php render_empty_state('No suppliers yet. Add your first supplier to get started.', 'bi-truck'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Contact</th>
                  <th>Phone</th>
                  <th>Email</th>
                  <th>Rating</th>
                  <th>Payment Terms</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($suppliers as $s): ?>
                  <tr>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['contact_person'] ?: '-') ?></td>
                    <td><?= e($s['phone'] ?: '-') ?></td>
                    <td><?= e($s['email'] ?: '-') ?></td>
                    <td><?php for ($i = 0; $i < (int) $s['rating']; $i++) { echo '<i class="bi bi-star-fill text-warning"></i>'; } ?></td>
                    <td><?= e($s['payment_terms'] ?: '-') ?></td>
                    <td><?php render_status_badge($s['status']); ?></td>
                    <td class="text-end">
                      <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#supplierModal<?= $s['id'] ?>"><i class="bi bi-pencil"></i></button>
                      <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delSupplier<?= $s['id'] ?>"><i class="bi bi-trash"></i></button>
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

<?php // Add supplier modal ?>
<?php modal_open('supplierModal', 'Add Supplier'); ?>
<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="">
  <div class="mb-2"><label class="form-label small">Name</label><input type="text" name="name" class="form-control" required></div>
  <div class="row">
    <div class="col-md-6 mb-2"><label class="form-label small">Contact Person</label><input type="text" name="contact_person" class="form-control"></div>
    <div class="col-md-6 mb-2"><label class="form-label small">Phone</label><input type="text" name="phone" class="form-control"></div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-2"><label class="form-label small">Email</label><input type="email" name="email" class="form-control"></div>
    <div class="col-md-6 mb-2"><label class="form-label small">Tax ID</label><input type="text" name="tax_id" class="form-control"></div>
  </div>
  <div class="mb-2"><label class="form-label small">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
  <div class="row">
    <div class="col-md-4 mb-2"><label class="form-label small">Rating (1-5)</label><input type="number" min="1" max="5" name="rating" class="form-control" value="3"></div>
    <div class="col-md-4 mb-2"><label class="form-label small">Payment Terms</label><input type="text" name="payment_terms" class="form-control" placeholder="Net 30"></div>
    <div class="col-md-4 mb-2"><label class="form-label small">Status</label>
      <select name="status" class="form-select">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
  </div>
  <button type="submit" class="btn btn-primary w-100 mt-2">Save Supplier</button>
</form>
<?php modal_close(); ?>

<?php foreach ($suppliers as $s): ?>
  <?php modal_open('supplierModal' . $s['id'], 'Edit Supplier'); ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $s['id'] ?>">
    <div class="mb-2"><label class="form-label small">Name</label><input type="text" name="name" class="form-control" required value="<?= e($s['name']) ?>"></div>
    <div class="row">
      <div class="col-md-6 mb-2"><label class="form-label small">Contact Person</label><input type="text" name="contact_person" class="form-control" value="<?= e($s['contact_person']) ?>"></div>
      <div class="col-md-6 mb-2"><label class="form-label small">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($s['phone']) ?>"></div>
    </div>
    <div class="row">
      <div class="col-md-6 mb-2"><label class="form-label small">Email</label><input type="email" name="email" class="form-control" value="<?= e($s['email']) ?>"></div>
      <div class="col-md-6 mb-2"><label class="form-label small">Tax ID</label><input type="text" name="tax_id" class="form-control" value="<?= e($s['tax_id']) ?>"></div>
    </div>
    <div class="mb-2"><label class="form-label small">Address</label><textarea name="address" class="form-control" rows="2"><?= e($s['address']) ?></textarea></div>
    <div class="row">
      <div class="col-md-4 mb-2"><label class="form-label small">Rating (1-5)</label><input type="number" min="1" max="5" name="rating" class="form-control" value="<?= (int) $s['rating'] ?>"></div>
      <div class="col-md-4 mb-2"><label class="form-label small">Payment Terms</label><input type="text" name="payment_terms" class="form-control" value="<?= e($s['payment_terms']) ?>"></div>
      <div class="col-md-4 mb-2"><label class="form-label small">Status</label>
        <select name="status" class="form-select">
          <option value="active" <?= $s['status'] === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $s['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 mt-2">Update Supplier</button>
  </form>
  <?php modal_close(); ?>
  <?php confirm_delete_modal('delSupplier' . $s['id'], base_url('org-admin/procurement/suppliers.php'), e($s['name']), ['id' => $s['id']]); ?>
<?php endforeach; ?>

<?php render_footer(['context' => 'org-admin']); ?>

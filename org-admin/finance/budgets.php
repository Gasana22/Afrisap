<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user(['owner', 'manager', 'accountant']);
$organization = current_organization();
$orgId = (int) $organization['id'];

if (is_post() && csrf_verify()) {
    if (($_POST['_method'] ?? '') === 'DELETE') {
        $id = clean_int($_POST['id'] ?? 0);
        $old = tenant_find('budgets', $orgId, $id);
        if ($old) {
            tenant_delete('budgets', $orgId, $id);
            audit_log($orgId, $currentUser['id'], 'delete', 'budgets', $id, $old, null);
            session_flash('success', 'Budget deleted.');
        }
        redirect('org-admin/finance/budgets.php');
    }

    $id = clean_int($_POST['id'] ?? 0);
    $input = [
        'farm_id' => $_POST['farm_id'] ?? '',
        'fiscal_year' => clean_string($_POST['fiscal_year'] ?? ''),
        'category' => clean_string($_POST['category'] ?? ''),
        'allocated_amount' => $_POST['allocated_amount'] ?? '',
        'notes' => clean_string($_POST['notes'] ?? ''),
    ];

    $errors = validate($input, [
        'fiscal_year' => 'required|max:10',
        'category' => 'required|max:100',
        'allocated_amount' => 'required|numeric',
    ]);

    if (!$errors) {
        $data = [
            'farm_id' => $input['farm_id'] !== '' ? clean_int($input['farm_id']) : null,
            'fiscal_year' => $input['fiscal_year'],
            'category' => $input['category'],
            'allocated_amount' => clean_float($input['allocated_amount']),
            'notes' => $input['notes'] ?: null,
        ];

        try {
            if ($id > 0) {
                $old = tenant_find('budgets', $orgId, $id);
                if ($old) {
                    tenant_update('budgets', $orgId, $id, $data);
                    audit_log($orgId, $currentUser['id'], 'update', 'budgets', $id, $old, $data);
                    session_flash('success', 'Budget updated.');
                } else {
                    session_flash('error', 'Budget not found.');
                }
            } else {
                $data['created_by'] = $currentUser['id'];
                $newId = tenant_insert('budgets', $orgId, $data);
                audit_log($orgId, $currentUser['id'], 'create', 'budgets', $newId, null, $data);
                session_flash('success', 'Budget created.');
            }
        } catch (PDOException $e) {
            session_flash('error', 'A budget for that farm, fiscal year and category already exists.');
        }
        redirect('org-admin/finance/budgets.php');
    }

    $GLOBALS['_page_errors'] = $errors;
}

$farms = tenant_all('farms', $orgId, 'ORDER BY name');
$budgets = tenant_all('budgets', $orgId, 'ORDER BY fiscal_year DESC, category');

// Keep spent_amount in sync with actual recorded expenses.
foreach ($budgets as &$budget) {
    $actualSpend = (float) db_value(
        "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions
         WHERE organization_id = :org_id AND type = 'expense' AND category = :category AND YEAR(transaction_date) = :fiscal_year",
        ['org_id' => $orgId, 'category' => $budget['category'], 'fiscal_year' => $budget['fiscal_year']]
    );
    if ($actualSpend != (float) $budget['spent_amount']) {
        tenant_update('budgets', $orgId, (int) $budget['id'], ['spent_amount' => $actualSpend]);
    }
    $budget['spent_amount'] = $actualSpend;
}
unset($budget);

$farmsById = [];
foreach ($farms as $f) {
    $farmsById[$f['id']] = $f['name'];
}

render_header(['title' => 'Budgets', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0">Budgets</h4>
          <p class="text-muted small mb-0">Plan and track spending against allocated budgets by category and fiscal year.</p>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-4">
          <div class="content-card">
            <h6 class="mb-3">Add Budget</h6>
            <form method="post">
              <?= csrf_field() ?>
              <div class="mb-2">
                <label class="form-label small">Farm <span class="text-muted">(optional)</span></label>
                <select name="farm_id" class="form-select">
                  <option value="">All Farms</option>
                  <?php foreach ($farms as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= e($f['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label small">Fiscal Year</label>
                <input type="text" name="fiscal_year" class="form-control" placeholder="e.g. 2026" required value="<?= e(date('Y')) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label small">Category</label>
                <input type="text" name="category" class="form-control" placeholder="e.g. Inputs" required>
              </div>
              <div class="mb-2">
                <label class="form-label small">Allocated Amount</label>
                <input type="number" step="0.01" min="0" name="allocated_amount" class="form-control" required>
              </div>
              <div class="mb-2">
                <label class="form-label small">Notes <span class="text-muted">(optional)</span></label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
              </div>
              <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Save Budget</button>
            </form>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="content-card">
            <h6 class="mb-3">Budgets</h6>
            <?php if (!$budgets): ?>
              <?php render_empty_state('No budgets set yet. Add one on the left.', 'bi-piggy-bank'); ?>
            <?php else: ?>
              <?php foreach ($budgets as $b): ?>
                <?php
                $allocated = (float) $b['allocated_amount'];
                $spent = (float) $b['spent_amount'];
                $pct = $allocated > 0 ? min(100, round(($spent / $allocated) * 100, 1)) : 0;
                $actualPct = $allocated > 0 ? round(($spent / $allocated) * 100, 1) : 0;
                $barClass = 'bg-success';
                if ($actualPct >= 90) {
                    $barClass = 'bg-danger';
                } elseif ($actualPct >= 70) {
                    $barClass = 'bg-warning';
                }
                ?>
                <div class="py-3 border-bottom">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                      <div class="fw-semibold"><?= e($b['category']) ?> &middot; <span class="text-muted small"><?= e($b['fiscal_year']) ?></span></div>
                      <div class="small text-muted"><?= $b['farm_id'] ? e($farmsById[$b['farm_id']] ?? 'Unknown Farm') : 'All Farms' ?></div>
                    </div>
                    <div class="d-flex gap-2">
                      <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editBudget<?= $b['id'] ?>"><i class="bi bi-pencil"></i></button>
                      <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteBudget<?= $b['id'] ?>"><i class="bi bi-trash"></i></button>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between small mb-1">
                    <span><?= format_money($spent) ?> spent of <?= format_money($allocated) ?></span>
                    <span class="<?= $actualPct >= 90 ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= $actualPct ?>%</span>
                  </div>
                  <div class="progress" style="height:8px;">
                    <div class="progress-bar <?= $barClass ?>" role="progressbar" style="width: <?= $pct ?>%"></div>
                  </div>
                  <?php if ($b['notes']): ?>
                    <div class="small text-muted mt-1"><?= e($b['notes']) ?></div>
                  <?php endif; ?>
                </div>

                <?php modal_open('editBudget' . $b['id'], 'Edit Budget'); ?>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <div class="mb-2">
                    <label class="form-label small">Farm</label>
                    <select name="farm_id" class="form-select">
                      <option value="">All Farms</option>
                      <?php foreach ($farms as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= (int) $b['farm_id'] === (int) $f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-2">
                    <label class="form-label small">Fiscal Year</label>
                    <input type="text" name="fiscal_year" class="form-control" required value="<?= e($b['fiscal_year']) ?>">
                  </div>
                  <div class="mb-2">
                    <label class="form-label small">Category</label>
                    <input type="text" name="category" class="form-control" required value="<?= e($b['category']) ?>">
                  </div>
                  <div class="mb-2">
                    <label class="form-label small">Allocated Amount</label>
                    <input type="number" step="0.01" min="0" name="allocated_amount" class="form-control" required value="<?= e((string) $b['allocated_amount']) ?>">
                  </div>
                  <div class="mb-2">
                    <label class="form-label small">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= e($b['notes']) ?></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Update Budget</button>
                </form>
                <?php modal_close(); ?>
                <?php confirm_delete_modal('deleteBudget' . $b['id'], base_url('org-admin/finance/budgets.php'), e($b['category']) . ' (' . e($b['fiscal_year']) . ')', ['id' => $b['id']]); ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>

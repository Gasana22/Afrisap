<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$admin = require_platform_admin();

$invoices = db_all(
    'SELECT s.*, o.name AS org_name, o.email AS org_email, sp.name AS plan_name
     FROM subscriptions s
     JOIN organizations o ON o.id = s.organization_id
     LEFT JOIN subscription_plans sp ON sp.id = s.plan_id
     ORDER BY s.created_at DESC
     LIMIT 100'
);

render_header(['title' => 'Invoices', 'context' => 'admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('admin', $_SERVER['SCRIPT_NAME'], null); ?>
  <div class="app-main">
    <?php render_app_topbar('admin', $admin, null); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <h4 class="mb-3">Invoices</h4>
      <p class="text-muted small">Each subscription record is treated as one invoice. No external payment gateway is wired up yet - this is a simple printable summary.</p>

      <div class="content-card">
        <?php if (!$invoices): ?>
          <?php render_empty_state('No invoices yet.', 'bi-receipt'); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table-app">
              <thead>
                <tr><th>Invoice #</th><th>Organization</th><th>Plan</th><th>Amount</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($invoices as $inv): ?>
                  <tr>
                    <td>INV-<?= str_pad((string) $inv['id'], 6, '0', STR_PAD_LEFT) ?></td>
                    <td><?= e($inv['org_name']) ?></td>
                    <td><?= e($inv['plan_name'] ?? '-') ?></td>
                    <td><?= format_money((float) $inv['amount']) ?></td>
                    <td><?php render_status_badge($inv['status']); ?></td>
                    <td><?= format_date($inv['created_at']) ?></td>
                    <td class="text-end">
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#invoice<?= $inv['id'] ?>"><i class="bi bi-eye"></i> View</button>
                    </td>
                  </tr>
                  <?php modal_open('invoice' . $inv['id'], 'Invoice INV-' . str_pad((string) $inv['id'], 6, '0', STR_PAD_LEFT)); ?>
                  <div class="invoice-print p-2">
                    <div class="d-flex justify-content-between mb-4">
                      <div>
                        <h5 class="mb-0"><?= e(app_config()['app']['name']) ?></h5>
                        <p class="text-muted small mb-0">Platform Invoice</p>
                      </div>
                      <div class="text-end">
                        <div><strong>Invoice #</strong> INV-<?= str_pad((string) $inv['id'], 6, '0', STR_PAD_LEFT) ?></div>
                        <div><strong>Date</strong> <?= format_date($inv['created_at']) ?></div>
                      </div>
                    </div>
                    <p><strong>Bill To:</strong><br><?= e($inv['org_name']) ?><br><?= e($inv['org_email'] ?: '') ?></p>
                    <table class="table-app w-100">
                      <thead><tr><th>Description</th><th class="text-end">Amount</th></tr></thead>
                      <tbody>
                        <tr>
                          <td><?= e($inv['plan_name'] ?? 'Subscription') ?> plan (<?= e($inv['payment_method'] ? humanize($inv['payment_method']) : 'N/A') ?>)</td>
                          <td class="text-end"><?= format_money((float) $inv['amount']) ?></td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr><th>Total</th><th class="text-end"><?= format_money((float) $inv['amount']) ?></th></tr>
                      </tfoot>
                    </table>
                    <p class="small text-muted mt-2">Status: <?= e(humanize($inv['status'])) ?> &middot; Reference: <?= e($inv['payment_reference'] ?: '-') ?></p>
                    <button type="button" class="btn btn-primary btn-sm no-print" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                  </div>
                  <?php modal_close(); ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<style>
@media print {
  body * { visibility: hidden; }
  .invoice-print, .invoice-print * { visibility: visible; }
  .invoice-print { position: absolute; top: 0; left: 0; width: 100%; }
  .no-print { display: none; }
}
</style>
<?php render_footer(['context' => 'admin']); ?>

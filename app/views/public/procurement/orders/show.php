<?php use App\Core\Auth; use App\Core\Csrf; $canEdit = Auth::hasPermission('procurement.edit'); ?>
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1">Purchase Order #<?= (int) $order['id'] ?></h4>
        <p class="text-muted mb-0">
            <?= htmlspecialchars($order['supplier_name']) ?> &middot; <?= htmlspecialchars($order['farm_name']) ?>
            &middot; Ordered <?= htmlspecialchars($order['order_date']) ?>
            <?php $statusBadge = ['draft' => 'secondary', 'ordered' => 'info', 'partially_received' => 'warning', 'received' => 'success', 'cancelled' => 'dark'][$order['status']] ?? 'secondary'; ?>
            &middot; <span class="badge bg-<?= $statusBadge ?> text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $order['status'])) ?></span>
        </p>
    </div>
    <?php if ($canEdit && $order['status'] === 'draft'): ?>
    <form method="post" action="/purchase-orders/<?= (int) $order['id'] ?>/delete" onsubmit="return confirm('Delete this draft purchase order?');">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm">Delete Draft</button>
    </form>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Total Cost</div><div class="fs-6 fw-bold"><?= number_format((float) $order['total_cost'], 2) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Total Paid</div><div class="fs-6 fw-bold"><?= number_format((float) $order['total_paid'], 2) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Balance Due</div><div class="fs-6 fw-bold"><?= number_format((float) $order['total_cost'] - (float) $order['total_paid'], 2) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Expected Date</div><div class="fs-6 fw-bold"><?= htmlspecialchars($order['expected_date'] ?? '—') ?></div></div></div>
</div>

<?php if ($canEdit): ?>
<div class="card mb-3">
    <div class="card-body">
        <h6 class="small text-muted">Update Status</h6>
        <form method="post" action="/purchase-orders/<?= (int) $order['id'] ?>/status" class="d-flex gap-2">
            <?= Csrf::field() ?>
            <select name="status" class="form-select form-select-sm w-auto">
                <?php foreach (['draft', 'ordered', 'partially_received', 'received', 'cancelled'] as $status): ?>
                <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $status)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-success">Update</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="mb-3">Line Items</h6>
        <table class="table table-sm mb-0">
            <thead><tr><th>Item</th><th>Quantity</th><th>Unit</th><th class="text-end">Unit Cost</th><th class="text-end">Line Total</th></tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td><?= htmlspecialchars($item['unit'] ?? '') ?></td>
                    <td class="text-end"><?= number_format((float) $item['unit_cost'], 2) ?></td>
                    <td class="text-end"><?= number_format((float) $item['quantity'] * (float) $item['unit_cost'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($order['notes']): ?><p class="text-muted small mt-2 mb-0">Notes: <?= htmlspecialchars($order['notes']) ?></p><?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Deliveries</h6>
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Received By</th><th>Condition</th></tr></thead>
                    <tbody>
                        <?php if (empty($deliveries)): ?><tr><td colspan="3" class="text-muted small">No deliveries recorded yet.</td></tr><?php endif; ?>
                        <?php foreach ($deliveries as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['delivery_date']) ?></td>
                            <td><?= htmlspecialchars($d['received_by'] ?? '—') ?></td>
                            <td class="small"><?= htmlspecialchars($d['condition_notes'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit): ?>
                <form method="post" action="/purchase-orders/<?= (int) $order['id'] ?>/deliveries" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-4"><input type="date" name="delivery_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-4"><input type="text" name="received_by" class="form-control form-control-sm" placeholder="Received by"></div>
                    <div class="col-md-4"><input type="text" name="condition_notes" class="form-control form-control-sm" placeholder="Condition notes"></div>
                    <div class="col-12"><button type="submit" class="btn btn-sm btn-outline-success mt-1">Record Delivery</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Payments</h6>
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Method</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        <?php if (empty($payments)): ?><tr><td colspan="3" class="text-muted small">No payments recorded yet.</td></tr><?php endif; ?>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['payment_date']) ?></td>
                            <td class="text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $p['method'])) ?></td>
                            <td class="text-end"><?= number_format((float) $p['amount'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit): ?>
                <form method="post" action="/purchase-orders/<?= (int) $order['id'] ?>/payments" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-3"><input type="text" name="amount" class="form-control form-control-sm" placeholder="Amount" required></div>
                    <div class="col-md-3"><input type="date" name="payment_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-3">
                        <select name="method" class="form-select form-select-sm">
                            <option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option>
                            <option value="mobile_money">Mobile money</option><option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-3"><button type="submit" class="btn btn-sm btn-outline-success w-100">Record Payment</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

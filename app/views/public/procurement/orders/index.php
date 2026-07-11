<?php use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Purchase Orders</h4>
    <div class="d-flex gap-2">
        <a href="/suppliers" class="btn btn-outline-secondary btn-sm">Suppliers</a>
        <?php if (Auth::hasPermission('procurement.create')): ?>
        <a href="/purchase-orders/create" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> New Purchase Order</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Supplier</th><th>Farm</th><th>Order Date</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Paid</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($orders)): ?><tr><td colspan="7" class="text-center text-muted py-4">No purchase orders yet.</td></tr><?php endif; ?>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><a href="/purchase-orders/<?= (int) $o['id'] ?>"><?= htmlspecialchars($o['supplier_name']) ?></a></td>
                    <td><?= htmlspecialchars($o['farm_name']) ?></td>
                    <td><?= htmlspecialchars($o['order_date']) ?></td>
                    <td>
                        <?php $statusBadge = ['draft' => 'secondary', 'ordered' => 'info', 'partially_received' => 'warning', 'received' => 'success', 'cancelled' => 'dark'][$o['status']] ?? 'secondary'; ?>
                        <span class="badge bg-<?= $statusBadge ?> text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $o['status'])) ?></span>
                    </td>
                    <td class="text-end"><?= number_format((float) $o['total_cost'], 2) ?></td>
                    <td class="text-end"><?= number_format((float) $o['total_paid'], 2) ?></td>
                    <td class="text-end"><a href="/purchase-orders/<?= (int) $o['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $baseUrl = '/purchase-orders'; require __DIR__ . '/../../../partials/pagination.php'; ?>

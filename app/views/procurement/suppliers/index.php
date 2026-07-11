<?php use App\Core\Auth; use App\Core\Csrf; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Suppliers</h4>
    <div class="d-flex gap-2">
        <a href="/purchase-orders" class="btn btn-outline-secondary btn-sm">Purchase Orders</a>
        <?php if (Auth::hasPermission('procurement.create')): ?>
        <a href="/suppliers/create" class="btn btn-success btn-sm"><i class="bi bi-plus-lg"></i> Add Supplier</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Category</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($suppliers)): ?><tr><td colspan="6" class="text-center text-muted py-4">No suppliers yet.</td></tr><?php endif; ?>
                <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['contact_person'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['email'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['category'] ?? '—') ?></td>
                    <td class="text-end">
                        <?php if (Auth::hasPermission('procurement.edit')): ?>
                        <a href="/suppliers/<?= (int) $s['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <?php endif; ?>
                        <?php if (Auth::hasPermission('procurement.delete')): ?>
                        <form method="post" action="/suppliers/<?= (int) $s['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this supplier?');">
                            <?= Csrf::field() ?><button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

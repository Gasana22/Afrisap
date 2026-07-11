<?php use App\Core\Auth; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Inventory</h4>
    <?php if (Auth::hasPermission('inventory.create')): ?>
    <a href="/inventory/create" class="btn btn-success"><i class="bi bi-plus-lg"></i> Add Item</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Name</th><th>Category</th><th>Unit</th><th>Reorder Level</th><th>Total Stock</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($items)): ?><tr><td colspan="6" class="text-center text-muted py-4">No inventory items yet.</td></tr><?php endif; ?>
                <?php foreach ($items as $item):
                    $lowStock = $item['reorder_level'] !== null && (float) $item['total_stock'] < (float) $item['reorder_level'];
                ?>
                <tr class="<?= $lowStock ? 'table-warning' : '' ?>">
                    <td><a href="/inventory/<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></a></td>
                    <td class="text-capitalize"><?= htmlspecialchars($item['category']) ?></td>
                    <td><?= htmlspecialchars($item['unit'] ?? '—') ?></td>
                    <td><?= $item['reorder_level'] !== null ? htmlspecialchars($item['reorder_level']) : '—' ?></td>
                    <td>
                        <?= htmlspecialchars($item['total_stock']) ?>
                        <?php if ($lowStock): ?><span class="badge bg-warning text-dark ms-1">Low Stock</span><?php endif; ?>
                    </td>
                    <td class="text-end"><a href="/inventory/<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

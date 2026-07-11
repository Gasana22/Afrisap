<?php use App\Core\Auth; use App\Core\Csrf; $canEdit = Auth::hasPermission('inventory.edit'); $lowStock = $item['reorder_level'] !== null && (float) $item['total_stock'] < (float) $item['reorder_level']; ?>
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($item['name']) ?></h4>
        <p class="text-muted mb-0">
            <span class="text-capitalize"><?= htmlspecialchars($item['category']) ?></span>
            <?= $item['unit'] ? ' &middot; unit: ' . htmlspecialchars($item['unit']) : '' ?>
            <?php if ($lowStock): ?><span class="badge bg-warning text-dark ms-1">Low Stock</span><?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($canEdit): ?>
        <a href="/inventory/<?= (int) $item['id'] ?>/edit" class="btn btn-outline-secondary btn-sm">Edit</a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('inventory.delete')): ?>
        <form method="post" action="/inventory/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Delete this item? Only possible with no stock movement history.');">
            <?= Csrf::field() ?><button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Total Stock</div><div class="fs-4 fw-bold"><?= htmlspecialchars($item['total_stock']) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Reorder Level</div><div class="fs-4 fw-bold"><?= $item['reorder_level'] !== null ? htmlspecialchars($item['reorder_level']) : '—' ?></div></div></div>
</div>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3">Stock by Farm</h6>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Farm</th><th class="text-end">Quantity</th></tr></thead>
                    <tbody>
                        <?php if (empty($stockByFarm)): ?><tr><td colspan="2" class="text-muted small">No stock recorded yet.</td></tr><?php endif; ?>
                        <?php foreach ($stockByFarm as $s): ?>
                        <tr><td><?= htmlspecialchars($s['farm_name']) ?></td><td class="text-end"><?= htmlspecialchars($s['quantity_on_hand']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($canEdit): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="small text-muted">Stock In</h6>
                <form method="post" action="/inventory/<?= (int) $item['id'] ?>/stock-in">
                    <?= Csrf::field() ?>
                    <div class="mb-2">
                        <select name="farm_id" class="form-select form-select-sm" required>
                            <option value="">Farm</option>
                            <?php foreach ($farms as $f): ?><option value="<?= (int) $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><input type="text" name="quantity" class="form-control form-control-sm" placeholder="Quantity" required></div>
                    <div class="mb-2"><input type="date" name="movement_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="mb-2"><input type="text" name="reference" class="form-control form-control-sm" placeholder="Reference (e.g. PO #, receipt)"></div>
                    <div class="mb-2"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <button type="submit" class="btn btn-sm btn-outline-success w-100">Record Stock In</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="small text-muted">Stock Out</h6>
                <form method="post" action="/inventory/<?= (int) $item['id'] ?>/stock-out">
                    <?= Csrf::field() ?>
                    <div class="mb-2">
                        <select name="farm_id" class="form-select form-select-sm" required>
                            <option value="">Farm</option>
                            <?php foreach ($farms as $f): ?><option value="<?= (int) $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><input type="text" name="quantity" class="form-control form-control-sm" placeholder="Quantity" required></div>
                    <div class="mb-2"><input type="date" name="movement_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="mb-2"><input type="text" name="reference" class="form-control form-control-sm" placeholder="Reference (e.g. crop cycle)"></div>
                    <div class="mb-2"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">Record Stock Out</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="small text-muted">Transfer Between Farms</h6>
                <form method="post" action="/inventory/<?= (int) $item['id'] ?>/transfer">
                    <?= Csrf::field() ?>
                    <div class="mb-2">
                        <select name="from_farm_id" class="form-select form-select-sm" required>
                            <option value="">From Farm</option>
                            <?php foreach ($farms as $f): ?><option value="<?= (int) $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <select name="to_farm_id" class="form-select form-select-sm" required>
                            <option value="">To Farm</option>
                            <?php foreach ($farms as $f): ?><option value="<?= (int) $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><input type="text" name="quantity" class="form-control form-control-sm" placeholder="Quantity" required></div>
                    <div class="mb-2"><input type="date" name="movement_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="mb-2"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Record Transfer</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Movement History</h6>
                <div class="table-responsive" style="max-height: 600px;">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Type</th><th>Farm</th><th class="text-end">Qty</th><th>Reference</th><th>By</th></tr></thead>
                        <tbody>
                            <?php if (empty($movements)): ?><tr><td colspan="6" class="text-muted small">No movements recorded yet.</td></tr><?php endif; ?>
                            <?php foreach ($movements as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['movement_date']) ?></td>
                                <td>
                                    <?php $typeBadge = ['in' => 'success', 'out' => 'danger', 'transfer_in' => 'info', 'transfer_out' => 'secondary'][$m['type']] ?? 'secondary'; ?>
                                    <span class="badge bg-<?= $typeBadge ?>"><?= htmlspecialchars(str_replace('_', ' ', $m['type'])) ?></span>
                                </td>
                                <td class="small">
                                    <?= htmlspecialchars($m['farm_name']) ?>
                                    <?= $m['related_farm_name'] ? ' ↔ ' . htmlspecialchars($m['related_farm_name']) : '' ?>
                                </td>
                                <td class="text-end"><?= htmlspecialchars($m['quantity']) ?></td>
                                <td class="small"><?= htmlspecialchars($m['reference'] ?? '—') ?></td>
                                <td class="small"><?= htmlspecialchars($m['performed_by_name']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

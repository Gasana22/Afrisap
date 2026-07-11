<h4 class="mb-4">Start Crop Cycle</h4>

<?php if (empty($plots) || empty($cropTypes)): ?>
<div class="alert alert-warning">
    You need at least one <a href="/farms">plot</a> and one <a href="/crops/setup">crop type</a> before starting a crop cycle.
</div>
<?php else: ?>
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="post" action="/crops">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Plot</label>
                <select name="plot_id" class="form-select" required>
                    <option value="">— Select plot —</option>
                    <?php foreach ($plots as $plot): ?>
                    <option value="<?= (int) $plot['id'] ?>">
                        <?= htmlspecialchars($plot['farm_name']) ?> / <?= htmlspecialchars($plot['block_name']) ?> / <?= htmlspecialchars($plot['plot_code']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Crop type</label>
                <select name="crop_type_id" class="form-select" required>
                    <?php foreach ($cropTypes as $type): ?>
                    <option value="<?= (int) $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Season</label>
                <select name="season_id" class="form-select">
                    <option value="">— None —</option>
                    <?php foreach ($seasons as $season): ?>
                    <option value="<?= (int) $season['id'] ?>"><?= htmlspecialchars($season['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Start date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Budget</label>
                    <input type="text" name="budget" class="form-control" placeholder="e.g. 500000">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Expected yield</label>
                    <input type="text" name="expected_yield" class="form-control" placeholder="e.g. 2000 (kg)">
                </div>
            </div>
            <p class="text-muted small mt-3">A unique batch code (e.g. <code>COCOA-2026-FARM01-BLOCKB-001</code>) will be generated automatically for full traceability.</p>
            <button type="submit" class="btn btn-success">Start Crop Cycle</button>
            <a href="/crops" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php endif; ?>

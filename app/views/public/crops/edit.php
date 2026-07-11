<h4 class="mb-4">Edit <?= htmlspecialchars($cycle['batch_code']) ?></h4>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="post" action="/crops/<?= (int) $cycle['id'] ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Season</label>
                <select name="season_id" class="form-select">
                    <option value="">— None —</option>
                    <?php foreach ($seasons as $season): ?>
                    <option value="<?= (int) $season['id'] ?>" <?= (int) $cycle['season_id'] === (int) $season['id'] ? 'selected' : '' ?>><?= htmlspecialchars($season['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Start date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($cycle['start_date'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['planning','procurement','nursery','field','monitoring','harvested','closed'] as $status): ?>
                        <option value="<?= $status ?>" <?= $cycle['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Budget</label>
                    <input type="text" name="budget" class="form-control" value="<?= htmlspecialchars($cycle['budget'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Expected yield</label>
                    <input type="text" name="expected_yield" class="form-control" value="<?= htmlspecialchars($cycle['expected_yield'] ?? '') ?>">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-success">Save Changes</button>
                <a href="/crops/<?= (int) $cycle['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

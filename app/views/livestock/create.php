<h4 class="mb-4">Register Animal</h4>

<?php if (empty($farms)): ?>
<div class="alert alert-warning">You need at least one <a href="/farms">farm</a> before registering an animal.</div>
<?php else: ?>
<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="post" action="/livestock">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3">
                <label class="form-label">Farm</label>
                <select name="farm_id" class="form-select" required>
                    <option value="">— Select farm —</option>
                    <?php foreach ($farms as $farm): ?>
                    <option value="<?= (int) $farm['id'] ?>"><?= htmlspecialchars($farm['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-muted small">(optional)</span></label>
                    <input type="text" name="name" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tag number</label>
                    <input type="text" name="tag_number" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Species</label>
                    <input type="text" name="species" list="speciesList" class="form-control" required placeholder="e.g. Cattle">
                    <datalist id="speciesList">
                        <option value="Cattle"><option value="Goat"><option value="Sheep">
                        <option value="Pig"><option value="Chicken"><option value="Duck"><option value="Rabbit">
                    </datalist>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Breed</label>
                    <input type="text" name="breed" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select" required>
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Birth date</label>
                    <input type="date" name="birth_date" class="form-control">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Parent <span class="text-muted small">(optional, for breeding lineage)</span></label>
                    <select name="parent_id" class="form-select">
                        <option value="">— None —</option>
                        <?php foreach ($existingAnimals as $parent): ?>
                        <option value="<?= (int) $parent['id'] ?>"><?= htmlspecialchars($parent['animal_code']) ?><?= $parent['name'] ? ' — ' . htmlspecialchars($parent['name']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <p class="text-muted small mt-3">A unique animal ID (e.g. <code>AN-CATTLE-2026-0048</code>) will be generated automatically.</p>
            <button type="submit" class="btn btn-success">Register Animal</button>
            <a href="/livestock" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php endif; ?>

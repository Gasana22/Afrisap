<h4 class="mb-4">Edit <?= htmlspecialchars($animal['animal_code']) ?></h4>

<div class="card" style="max-width: 640px;">
    <div class="card-body">
        <form method="post" action="/livestock/<?= (int) $animal['id'] ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($animal['name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tag number</label>
                    <input type="text" name="tag_number" class="form-control" value="<?= htmlspecialchars($animal['tag_number'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Species</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($animal['species']) ?>" disabled>
                    <small class="text-muted">Species can't change after the animal ID has been issued.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Breed</label>
                    <input type="text" name="breed" class="form-control" value="<?= htmlspecialchars($animal['breed'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select" required>
                        <option value="female" <?= $animal['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="male" <?= $animal['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Birth date</label>
                    <input type="date" name="birth_date" class="form-control" value="<?= htmlspecialchars($animal['birth_date'] ?? '') ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Parent</label>
                    <select name="parent_id" class="form-select">
                        <option value="">— None —</option>
                        <?php foreach ($existingAnimals as $parent): ?>
                        <option value="<?= (int) $parent['id'] ?>" <?= (int) $animal['parent_id'] === (int) $parent['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($parent['animal_code']) ?><?= $parent['name'] ? ' — ' . htmlspecialchars($parent['name']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-success">Save Changes</button>
                <a href="/livestock/<?= (int) $animal['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

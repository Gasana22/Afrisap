<h4 class="mb-4">Upload File</h4>

<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/media" enctype="multipart/form-data">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="photo">Photo</option><option value="receipt">Receipt</option>
                        <option value="contract">Contract</option><option value="certificate">Certificate</option>
                        <option value="other" selected>Other</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Farm <span class="text-muted small">(optional)</span></label>
                    <select name="farm_id" class="form-select">
                        <option value="">— None —</option>
                        <?php foreach ($farms as $farm): ?>
                        <option value="<?= (int) $farm['id'] ?>"><?= htmlspecialchars($farm['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-3"><label class="form-label">File</label><input type="file" name="file" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            <button type="submit" class="btn btn-success">Upload</button>
            <a href="/media" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

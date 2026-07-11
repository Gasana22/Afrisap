<?php use App\Core\Auth; use App\Core\Csrf; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Media &amp; Documents</h4>
    <?php if (Auth::hasPermission('media.create')): ?>
    <a href="/media/create" class="btn btn-success"><i class="bi bi-upload"></i> Upload File</a>
    <?php endif; ?>
</div>

<form method="get" action="/media" class="row g-2 mb-4">
    <div class="col-md-3">
        <select name="category" class="form-select form-select-sm">
            <option value="">All Categories</option>
            <?php foreach (['photo', 'receipt', 'contract', 'certificate', 'other'] as $cat): ?>
            <option value="<?= $cat ?>" <?= $selectedCategory === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select name="farm_id" class="form-select form-select-sm">
            <option value="">All Farms</option>
            <?php foreach ($farms as $farm): ?>
            <option value="<?= (int) $farm['id'] ?>" <?= $selectedFarmId === (int) $farm['id'] ? 'selected' : '' ?>><?= htmlspecialchars($farm['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button></div>
</form>

<div class="row g-3">
    <?php if (empty($files)): ?>
    <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-4">No files uploaded yet.</div></div></div>
    <?php endif; ?>
    <?php foreach ($files as $f): ?>
    <div class="col-md-4 col-lg-3">
        <div class="card h-100">
            <?php $ext = strtolower(pathinfo($f['file_path'], PATHINFO_EXTENSION)); ?>
            <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])): ?>
            <a href="/<?= htmlspecialchars($f['file_path']) ?>" target="_blank">
                <img src="/<?= htmlspecialchars($f['file_path']) ?>" class="card-img-top" style="height: 140px; object-fit: cover;" alt="<?= htmlspecialchars($f['title']) ?>">
            </a>
            <?php else: ?>
            <a href="/<?= htmlspecialchars($f['file_path']) ?>" target="_blank" class="d-flex align-items-center justify-content-center bg-light" style="height: 140px;">
                <i class="bi bi-file-earmark-pdf fs-1 text-danger"></i>
            </a>
            <?php endif; ?>
            <div class="card-body">
                <span class="badge bg-secondary text-capitalize mb-1"><?= htmlspecialchars($f['category']) ?></span>
                <h6 class="card-title mb-1"><?= htmlspecialchars($f['title']) ?></h6>
                <p class="text-muted small mb-1"><?= htmlspecialchars($f['farm_name'] ?? 'No farm') ?></p>
                <p class="text-muted small mb-2"><?= htmlspecialchars($f['uploaded_by_name']) ?>, <?= htmlspecialchars($f['uploaded_at']) ?></p>
                <?php if (Auth::hasPermission('media.delete')): ?>
                <form method="post" action="/media/<?= (int) $f['id'] ?>/delete" onsubmit="return confirm('Delete this file?');">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

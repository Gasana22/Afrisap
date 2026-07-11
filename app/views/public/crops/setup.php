<?php use App\Core\Csrf; ?>
<h4 class="mb-4">Crop Setup</h4>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Crop Types</h6>
                <ul class="list-group mb-3">
                    <?php if (empty($cropTypes)): ?>
                    <li class="list-group-item text-muted small">No crop types yet.</li>
                    <?php endif; ?>
                    <?php foreach ($cropTypes as $type): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($type['name']) ?>
                        <form method="post" action="/crop-types/<?= (int) $type['id'] ?>/delete" onsubmit="return confirm('Remove this crop type?');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <form method="post" action="/crops/setup/types" class="d-flex gap-2">
                    <?= Csrf::field() ?>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Cocoa" required>
                    <button type="submit" class="btn btn-sm btn-success text-nowrap">Add</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Seasons</h6>
                <ul class="list-group mb-3">
                    <?php if (empty($seasons)): ?>
                    <li class="list-group-item text-muted small">No seasons yet.</li>
                    <?php endif; ?>
                    <?php foreach ($seasons as $season): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= htmlspecialchars($season['name']) ?> <span class="text-muted small">(<?= htmlspecialchars($season['start_date']) ?><?= $season['end_date'] ? ' – ' . htmlspecialchars($season['end_date']) : '' ?>)</span></span>
                        <form method="post" action="/seasons/<?= (int) $season['id'] ?>/delete" onsubmit="return confirm('Remove this season?');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <form method="post" action="/crops/setup/seasons" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-4"><input type="text" name="name" class="form-control form-control-sm" placeholder="Season name" required></div>
                    <div class="col-3"><input type="date" name="start_date" class="form-control form-control-sm" required></div>
                    <div class="col-3"><input type="date" name="end_date" class="form-control form-control-sm"></div>
                    <div class="col-2"><button type="submit" class="btn btn-sm btn-success w-100">Add</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

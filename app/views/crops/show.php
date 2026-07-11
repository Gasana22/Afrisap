<?php use App\Core\Auth; use App\Core\Csrf; $canEdit = Auth::hasPermission('crops.edit'); ?>
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 font-monospace"><?= htmlspecialchars($cycle['batch_code']) ?></h4>
        <p class="text-muted mb-0">
            <?= htmlspecialchars($cycle['crop_type_name']) ?> &middot;
            <?= htmlspecialchars($cycle['farm_name']) ?> / <?= htmlspecialchars($cycle['block_name']) ?> / <?= htmlspecialchars($cycle['plot_code']) ?>
            &middot; Season: <?= htmlspecialchars($cycle['season_name'] ?? '—') ?>
            &middot; <span class="badge bg-info text-dark text-capitalize"><?= htmlspecialchars($cycle['status']) ?></span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($canEdit): ?>
        <a href="/crops/<?= (int) $cycle['id'] ?>/edit" class="btn btn-outline-secondary btn-sm">Edit</a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('crops.delete')): ?>
        <form method="post" action="/crops/<?= (int) $cycle['id'] ?>/delete" onsubmit="return confirm('Delete this crop cycle and all its records?');">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Budget</div><div class="fs-5 fw-bold"><?= $cycle['budget'] !== null ? htmlspecialchars($cycle['budget']) : '—' ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Expected Yield</div><div class="fs-5 fw-bold"><?= $cycle['expected_yield'] !== null ? htmlspecialchars($cycle['expected_yield']) : '—' ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Start Date</div><div class="fs-5 fw-bold"><?= htmlspecialchars($cycle['start_date'] ?? '—') ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Total Harvested</div><div class="fs-5 fw-bold"><?= array_sum(array_column($harvests, 'quantity')) ?></div></div></div>
</div>

<div class="accordion" id="cropAccordion">

    <!-- Procurement -->
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#secInputs">1. Procurement (Seeds / Fertilizer / Chemicals)</button>
        </h2>
        <div id="secInputs" class="accordion-collapse collapse show" data-bs-parent="#cropAccordion">
            <div class="accordion-body">
                <table class="table table-sm">
                    <thead><tr><th>Type</th><th>Supplier</th><th>Qty</th><th>Cost</th><th>Purchased</th><th>Expiry</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($inputs)): ?><tr><td colspan="7" class="text-muted small">No inputs recorded yet.</td></tr><?php endif; ?>
                        <?php foreach ($inputs as $in): ?>
                        <tr>
                            <td class="text-capitalize"><?= htmlspecialchars($in['input_type']) ?></td>
                            <td><?= htmlspecialchars($in['supplier_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars(($in['quantity'] ?? '—') . ' ' . ($in['unit'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($in['cost'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($in['purchase_date'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($in['expiry_date'] ?? '—') ?></td>
                            <td>
                                <?php if ($canEdit): ?>
                                <form method="post" action="/crop-inputs/<?= (int) $in['id'] ?>/delete" onsubmit="return confirm('Delete?');">
                                    <?= Csrf::field() ?><button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit): ?>
                <form method="post" action="/crops/<?= (int) $cycle['id'] ?>/inputs" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-2">
                        <select name="input_type" class="form-select form-select-sm" required>
                            <option value="seed">Seed</option><option value="fertilizer">Fertilizer</option>
                            <option value="chemical">Chemical</option><option value="tool">Tool</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="text" name="supplier_name" class="form-control form-control-sm" placeholder="Supplier"></div>
                    <div class="col-md-1"><input type="text" name="quantity" class="form-control form-control-sm" placeholder="Qty"></div>
                    <div class="col-md-1"><input type="text" name="unit" class="form-control form-control-sm" placeholder="Unit"></div>
                    <div class="col-md-2"><input type="text" name="cost" class="form-control form-control-sm" placeholder="Cost"></div>
                    <div class="col-md-2"><input type="date" name="purchase_date" class="form-control form-control-sm" title="Purchase date"></div>
                    <div class="col-md-1"><input type="date" name="expiry_date" class="form-control form-control-sm" title="Expiry date"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Nursery -->
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secNursery">2. Nursery</button>
        </h2>
        <div id="secNursery" class="accordion-collapse collapse" data-bs-parent="#cropAccordion">
            <div class="accordion-body">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Germination %</th><th>Treatment</th><th>Survival %</th><th>Notes</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($nurseryRecords)): ?><tr><td colspan="6" class="text-muted small">No nursery records yet.</td></tr><?php endif; ?>
                        <?php foreach ($nurseryRecords as $n): ?>
                        <tr>
                            <td><?= htmlspecialchars($n['record_date']) ?></td>
                            <td><?= htmlspecialchars($n['germination_rate'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($n['treatment'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($n['survival_rate'] ?? '—') ?></td>
                            <td class="small"><?= htmlspecialchars($n['notes'] ?? '') ?></td>
                            <td>
                                <?php if ($canEdit): ?>
                                <form method="post" action="/nursery/<?= (int) $n['id'] ?>/delete" onsubmit="return confirm('Delete?');">
                                    <?= Csrf::field() ?><button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit): ?>
                <form method="post" action="/crops/<?= (int) $cycle['id'] ?>/nursery" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-2"><input type="date" name="record_date" class="form-control form-control-sm" required></div>
                    <div class="col-md-2"><input type="text" name="germination_rate" class="form-control form-control-sm" placeholder="Germination %"></div>
                    <div class="col-md-2"><input type="text" name="treatment" class="form-control form-control-sm" placeholder="Treatment"></div>
                    <div class="col-md-2"><input type="text" name="survival_rate" class="form-control form-control-sm" placeholder="Survival %"></div>
                    <div class="col-md-3"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Field Activities -->
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secActivities">3. Field Operations</button>
        </h2>
        <div id="secActivities" class="accordion-collapse collapse" data-bs-parent="#cropAccordion">
            <div class="accordion-body">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Type</th><th>Date</th><th>Worker</th><th>GPS</th><th>Cost</th><th>Photo</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($activities)): ?><tr><td colspan="8" class="text-muted small">No field activities yet.</td></tr><?php endif; ?>
                        <?php foreach ($activities as $a): ?>
                        <tr>
                            <td class="text-capitalize"><?= htmlspecialchars($a['activity_type']) ?></td>
                            <td><?= htmlspecialchars($a['activity_date']) ?></td>
                            <td><?= htmlspecialchars($a['worker_name'] ?? '—') ?></td>
                            <td class="small"><?= $a['gps_lat'] ? htmlspecialchars($a['gps_lat'] . ', ' . $a['gps_lng']) : '—' ?></td>
                            <td><?= htmlspecialchars($a['cost'] ?? '—') ?></td>
                            <td>
                                <?php foreach ($a['photos'] as $photo): ?>
                                <a href="/<?= htmlspecialchars($photo['file_path']) ?>" target="_blank"><img src="/<?= htmlspecialchars($photo['file_path']) ?>" style="width:32px;height:32px;object-fit:cover;border-radius:4px;" alt="photo"></a>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php if ($canEdit): ?>
                                <form method="post" action="/activities/<?= (int) $a['id'] ?>/status" class="d-inline">
                                    <?= Csrf::field() ?>
                                    <select name="status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                        <?php foreach (['pending','ongoing','completed'] as $status): ?>
                                        <option value="<?= $status ?>" <?= $a['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <?php else: ?>
                                <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars($a['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($canEdit): ?>
                                <form method="post" action="/activities/<?= (int) $a['id'] ?>/delete" onsubmit="return confirm('Delete?');">
                                    <?= Csrf::field() ?><button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit): ?>
                <form method="post" action="/crops/<?= (int) $cycle['id'] ?>/activities" enctype="multipart/form-data" class="row g-2" id="activityForm">
                    <?= Csrf::field() ?>
                    <div class="col-md-2">
                        <select name="activity_type" class="form-select form-select-sm" required>
                            <option value="planting">Planting</option><option value="weeding">Weeding</option>
                            <option value="irrigation">Irrigation</option><option value="spraying">Spraying</option>
                            <option value="fertilizing">Fertilizing</option><option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="date" name="activity_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-2"><input type="text" name="worker_name" class="form-control form-control-sm" placeholder="Worker"></div>
                    <div class="col-md-2"><input type="text" name="cost" class="form-control form-control-sm" placeholder="Cost"></div>
                    <div class="col-md-2"><input type="file" name="photo" accept="image/*" capture="environment" class="form-control form-control-sm"></div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="captureGpsBtn"><i class="bi bi-geo-alt"></i> Capture GPS</button>
                    </div>
                    <input type="hidden" name="gps_lat" id="activityGpsLat">
                    <input type="hidden" name="gps_lng" id="activityGpsLng">
                    <div class="col-md-10"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add Activity</button></div>
                    <div class="col-12"><small class="text-muted" id="gpsStatus"></small></div>
                </form>
                <script>
                document.getElementById('captureGpsBtn')?.addEventListener('click', function () {
                    const status = document.getElementById('gpsStatus');
                    if (!navigator.geolocation) { status.textContent = 'Geolocation not supported by this browser.'; return; }
                    status.textContent = 'Capturing location...';
                    navigator.geolocation.getCurrentPosition(function (pos) {
                        document.getElementById('activityGpsLat').value = pos.coords.latitude.toFixed(7);
                        document.getElementById('activityGpsLng').value = pos.coords.longitude.toFixed(7);
                        status.textContent = 'GPS captured: ' + pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5);
                    }, function (err) {
                        status.textContent = 'Could not get location: ' + err.message;
                    });
                });
                </script>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Monitoring -->
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secMonitoring">4. Monitoring (Disease / Pest / Growth)</button>
        </h2>
        <div id="secMonitoring" class="accordion-collapse collapse" data-bs-parent="#cropAccordion">
            <div class="accordion-body">
                <table class="table table-sm">
                    <thead><tr><th>Type</th><th>Date</th><th>Severity</th><th>Description</th><th>Photo</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($monitoringRecords)): ?><tr><td colspan="6" class="text-muted small">No monitoring records yet.</td></tr><?php endif; ?>
                        <?php foreach ($monitoringRecords as $m): ?>
                        <tr>
                            <td class="text-capitalize"><?= htmlspecialchars($m['type']) ?></td>
                            <td><?= htmlspecialchars($m['record_date']) ?></td>
                            <td><?= $m['severity'] ? '<span class="badge bg-' . ($m['severity'] === 'high' ? 'danger' : ($m['severity'] === 'medium' ? 'warning' : 'secondary')) . '">' . htmlspecialchars($m['severity']) . '</span>' : '—' ?></td>
                            <td class="small"><?= htmlspecialchars($m['description'] ?? '') ?></td>
                            <td><?php if ($m['photo_path']): ?><a href="/<?= htmlspecialchars($m['photo_path']) ?>" target="_blank"><img src="/<?= htmlspecialchars($m['photo_path']) ?>" style="width:32px;height:32px;object-fit:cover;border-radius:4px;" alt="photo"></a><?php endif; ?></td>
                            <td>
                                <?php if ($canEdit): ?>
                                <form method="post" action="/monitoring/<?= (int) $m['id'] ?>/delete" onsubmit="return confirm('Delete?');">
                                    <?= Csrf::field() ?><button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit): ?>
                <form method="post" action="/crops/<?= (int) $cycle['id'] ?>/monitoring" enctype="multipart/form-data" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-2">
                        <select name="type" class="form-select form-select-sm" required>
                            <option value="disease">Disease</option><option value="pest">Pest</option><option value="growth">Growth</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="date" name="record_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-2">
                        <select name="severity" class="form-select form-select-sm">
                            <option value="">Severity</option><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option>
                        </select>
                    </div>
                    <div class="col-md-4"><input type="text" name="description" class="form-control form-control-sm" placeholder="Description"></div>
                    <div class="col-md-2"><input type="file" name="photo" accept="image/*" capture="environment" class="form-control form-control-sm"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Yield Forecast -->
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secForecast">5. Yield Forecast</button>
        </h2>
        <div id="secForecast" class="accordion-collapse collapse" data-bs-parent="#cropAccordion">
            <div class="accordion-body">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Estimated Yield</th><th>Notes</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($forecasts)): ?><tr><td colspan="4" class="text-muted small">No forecasts yet.</td></tr><?php endif; ?>
                        <?php foreach ($forecasts as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['forecast_date']) ?></td>
                            <td><?= htmlspecialchars($f['estimated_yield']) ?></td>
                            <td class="small"><?= htmlspecialchars($f['notes'] ?? '') ?></td>
                            <td>
                                <?php if ($canEdit): ?>
                                <form method="post" action="/yield-forecasts/<?= (int) $f['id'] ?>/delete" onsubmit="return confirm('Delete?');">
                                    <?= Csrf::field() ?><button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit): ?>
                <form method="post" action="/crops/<?= (int) $cycle['id'] ?>/yield-forecast" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-2"><input type="date" name="forecast_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-2"><input type="text" name="estimated_yield" class="form-control form-control-sm" placeholder="Estimated yield" required></div>
                    <div class="col-md-6"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Harvest & Sales -->
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secHarvest">6. Harvest &amp; Sales</button>
        </h2>
        <div id="secHarvest" class="accordion-collapse collapse" data-bs-parent="#cropAccordion">
            <div class="accordion-body">
                <?php if (empty($harvests)): ?><p class="text-muted small">No harvests recorded yet.</p><?php endif; ?>
                <?php foreach ($harvests as $h): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?= htmlspecialchars($h['harvest_date']) ?></strong> —
                                <?= htmlspecialchars($h['quantity']) ?> <?= htmlspecialchars($h['unit'] ?? '') ?>
                                <?= $h['quality_grade'] ? '· Grade: ' . htmlspecialchars($h['quality_grade']) : '' ?>
                            </div>
                            <?php if ($canEdit): ?>
                            <form method="post" action="/harvests/<?= (int) $h['id'] ?>/delete" onsubmit="return confirm('Delete this harvest and its sales?');">
                                <?= Csrf::field() ?><button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>

                        <table class="table table-sm mt-2 mb-2">
                            <thead><tr><th>Buyer</th><th>Qty</th><th>Unit Price</th><th>Revenue</th><th>Date</th><th></th></tr></thead>
                            <tbody>
                                <?php if (empty($h['sales'])): ?><tr><td colspan="6" class="text-muted small">No sales recorded yet.</td></tr><?php endif; ?>
                                <?php foreach ($h['sales'] as $sale): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sale['buyer_name']) ?></td>
                                    <td><?= htmlspecialchars($sale['quantity']) ?></td>
                                    <td><?= htmlspecialchars($sale['unit_price']) ?></td>
                                    <td><?= htmlspecialchars($sale['revenue']) ?></td>
                                    <td><?= htmlspecialchars($sale['sale_date']) ?></td>
                                    <td>
                                        <?php if ($canEdit): ?>
                                        <form method="post" action="/crop-sales/<?= (int) $sale['id'] ?>/delete" onsubmit="return confirm('Delete?');">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="cycle_id" value="<?= (int) $cycle['id'] ?>">
                                            <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if ($canEdit): ?>
                        <form method="post" action="/harvests/<?= (int) $h['id'] ?>/sales" class="row g-2">
                            <?= Csrf::field() ?>
                            <div class="col-md-3"><input type="text" name="buyer_name" class="form-control form-control-sm" placeholder="Buyer" required></div>
                            <div class="col-md-2"><input type="text" name="quantity" class="form-control form-control-sm" placeholder="Qty" required></div>
                            <div class="col-md-2"><input type="text" name="unit_price" class="form-control form-control-sm" placeholder="Unit price" required></div>
                            <div class="col-md-2"><input type="date" name="sale_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                            <div class="col-md-2"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                            <div class="col-md-1"><button type="submit" class="btn btn-sm btn-outline-success w-100">Sell</button></div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($canEdit): ?>
                <div class="card">
                    <div class="card-body">
                        <h6 class="small text-muted">Record New Harvest</h6>
                        <form method="post" action="/crops/<?= (int) $cycle['id'] ?>/harvest" class="row g-2">
                            <?= Csrf::field() ?>
                            <div class="col-md-2"><input type="date" name="harvest_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                            <div class="col-md-2"><input type="text" name="quantity" class="form-control form-control-sm" placeholder="Quantity" required></div>
                            <div class="col-md-2"><input type="text" name="unit" class="form-control form-control-sm" placeholder="Unit (kg)"></div>
                            <div class="col-md-2"><input type="text" name="quality_grade" class="form-control form-control-sm" placeholder="Quality grade"></div>
                            <div class="col-md-3"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                            <div class="col-md-1"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

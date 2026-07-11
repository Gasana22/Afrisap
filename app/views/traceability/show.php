<?php use App\Core\Auth; use App\Core\Csrf; $canEdit = Auth::hasPermission('traceability.edit');
$productName = $batch['batch_type'] === 'crop' ? ($batch['crop_cycle']['crop_type_name'] ?? '') : ($batch['animal']['species'] ?? '');
$farmName = $batch['batch_type'] === 'crop' ? ($batch['crop_cycle']['farm_name'] ?? '') : ($batch['animal']['farm_name'] ?? '');
?>
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 font-monospace"><?= htmlspecialchars($batch['batch_code']) ?></h4>
        <p class="text-muted mb-0">
            <span class="text-capitalize"><?= htmlspecialchars($batch['batch_type']) ?></span> &middot;
            <?= htmlspecialchars($productName) ?> &middot; <?= htmlspecialchars($farmName) ?>
            <?php $statusBadge = ['active' => 'success', 'completed' => 'secondary', 'recalled' => 'danger'][$batch['status']] ?? 'secondary'; ?>
            &middot; <span class="badge bg-<?= $statusBadge ?> text-capitalize"><?= htmlspecialchars($batch['status']) ?></span>
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if ($canEdit): ?>
        <form method="post" action="/traceability/<?= (int) $batch['id'] ?>/status" class="d-flex gap-2">
            <?= Csrf::field() ?>
            <select name="status" class="form-select form-select-sm w-auto">
                <?php foreach (['active', 'completed', 'recalled'] as $status): ?>
                <option value="<?= $status ?>" <?= $batch['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
        </form>
        <?php endif; ?>
        <?php if ($batch['batch_type'] === 'crop'): ?>
        <a href="/crops/<?= (int) $batch['crop_cycle_id'] ?>" class="btn btn-sm btn-outline-secondary">View Crop Cycle</a>
        <?php else: ?>
        <a href="/livestock/<?= (int) $batch['animal_id'] ?>" class="btn btn-sm btn-outline-secondary">View Animal</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Revenue</div><div class="fs-6 fw-bold text-success"><?= number_format($profit['revenue'], 2) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Cost</div><div class="fs-6 fw-bold text-danger"><?= number_format($profit['cost'], 2) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Profit by Batch</div><div class="fs-6 fw-bold <?= $profit['profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($profit['profit'], 2) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Timeline Events</div><div class="fs-6 fw-bold"><?= count($timeline) ?></div></div></div>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3">Batch Journey Timeline</h6>
                <?php if (empty($timeline)): ?>
                <p class="text-muted small">No events recorded yet.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($timeline as $event): ?>
                    <li class="list-group-item px-0">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-secondary"><?= htmlspecialchars($event['category']) ?></span>
                            <span class="text-muted small"><?= htmlspecialchars($event['date']) ?></span>
                        </div>
                        <div class="small mt-1"><?= htmlspecialchars($event['description']) ?></div>
                        <?php if ($event['gps_lat']): ?><div class="text-muted small">GPS: <?= htmlspecialchars($event['gps_lat'] . ', ' . $event['gps_lng']) ?></div><?php endif; ?>
                        <?php if ($event['photo_path']): ?><a href="/<?= htmlspecialchars($event['photo_path']) ?>" target="_blank"><img src="/<?= htmlspecialchars($event['photo_path']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px;" class="mt-1" alt="photo"></a><?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3">Chain of Custody (Post-Harvest / Post-Sale)</h6>
                <table class="table table-sm">
                    <thead><tr><th>Stage</th><th>Date</th><th>Location</th><th>By</th></tr></thead>
                    <tbody>
                        <?php if (empty($journeyStages)): ?><tr><td colspan="4" class="text-muted small">No stages recorded yet.</td></tr><?php endif; ?>
                        <?php foreach ($journeyStages as $j): ?>
                        <tr>
                            <td class="text-capitalize"><?= htmlspecialchars($j['stage']) ?></td>
                            <td><?= htmlspecialchars($j['stage_date']) ?></td>
                            <td><?= htmlspecialchars($j['location'] ?? '—') ?></td>
                            <td class="small"><?= htmlspecialchars($j['responsible_user_name']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit): ?>
                <form method="post" action="/traceability/<?= (int) $batch['id'] ?>/journey" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-3">
                        <select name="stage" class="form-select form-select-sm">
                            <option value="storage">Storage</option><option value="processing">Processing</option>
                            <option value="packaging">Packaging</option><option value="distribution">Distribution</option>
                            <option value="delivered">Delivered</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="date" name="stage_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-3"><input type="text" name="location" class="form-control form-control-sm" placeholder="Location"></div>
                    <div class="col-md-3"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-body text-center">
                <h6 class="mb-3">QR Code</h6>
                <?php if ($qrCode): ?>
                <img src="/<?= htmlspecialchars($qrCode['qr_image_path']) ?>" style="max-width: 200px;" alt="QR code for <?= htmlspecialchars($batch['batch_code']) ?>">
                <p class="small text-muted mt-2 mb-1">Public link:</p>
                <?php $config = require __DIR__ . '/../../config/config.php'; ?>
                <a href="/trace/<?= htmlspecialchars($qrCode['public_token']) ?>" target="_blank" class="small"><?= htmlspecialchars(rtrim($config['app']['url'], '/')) ?>/trace/<?= htmlspecialchars($qrCode['public_token']) ?></a>
                <div class="mt-2"><a href="/<?= htmlspecialchars($qrCode['qr_image_path']) ?>" download class="btn btn-sm btn-outline-secondary">Download QR</a></div>
                <?php elseif ($canEdit): ?>
                <p class="text-muted small">No QR code generated yet.</p>
                <form method="post" action="/traceability/<?= (int) $batch['id'] ?>/qr">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-sm btn-success">Generate QR Code</button>
                </form>
                <?php else: ?>
                <p class="text-muted small">No QR code generated yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3">GPS Origin &amp; Movement</h6>
                <?php if (empty($gpsPoints)): ?>
                <p class="text-muted small">No GPS points recorded.</p>
                <?php else: ?>
                <ul class="list-unstyled small mb-0">
                    <?php foreach ($gpsPoints as $p): ?>
                    <li class="mb-1"><i class="bi bi-geo-alt text-success"></i> <?= htmlspecialchars($p['label']) ?>: <?= htmlspecialchars($p['lat'] . ', ' . $p['lng']) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3">Approvals &amp; Certification</h6>
                <table class="table table-sm">
                    <thead><tr><th>Type</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($approvals)): ?><tr><td colspan="3" class="text-muted small">No approvals requested yet.</td></tr><?php endif; ?>
                        <?php foreach ($approvals as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['approval_type']) ?></td>
                            <td>
                                <?php $apBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$a['status']] ?? 'secondary'; ?>
                                <span class="badge bg-<?= $apBadge ?> text-capitalize"><?= htmlspecialchars($a['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($canEdit && $a['status'] === 'pending'): ?>
                                <form method="post" action="/approvals/<?= (int) $a['id'] ?>/status" class="d-inline"><?= Csrf::field() ?><input type="hidden" name="status" value="approved"><button class="btn btn-sm btn-link text-success p-0">Approve</button></form>
                                /
                                <form method="post" action="/approvals/<?= (int) $a['id'] ?>/status" class="d-inline"><?= Csrf::field() ?><input type="hidden" name="status" value="rejected"><button class="btn btn-sm btn-link text-danger p-0">Reject</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit): ?>
                <form method="post" action="/traceability/<?= (int) $batch['id'] ?>/approvals" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-6"><input type="text" name="approval_type" class="form-control form-control-sm" placeholder="e.g. Organic Certification" required></div>
                    <div class="col-md-4"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Request</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Documents</h6>
                <ul class="list-unstyled small">
                    <?php if (empty($documents)): ?><li class="text-muted">No documents uploaded yet.</li><?php endif; ?>
                    <?php foreach ($documents as $d): ?>
                    <li class="mb-1">
                        <a href="/<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="text-capitalize"><?= htmlspecialchars($d['document_type']) ?></a>
                        <span class="text-muted">— <?= htmlspecialchars($d['uploaded_by_name']) ?>, <?= htmlspecialchars($d['uploaded_at']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($canEdit): ?>
                <form method="post" action="/traceability/<?= (int) $batch['id'] ?>/documents" enctype="multipart/form-data" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-4">
                        <select name="document_type" class="form-select form-select-sm">
                            <option value="certificate">Certificate</option><option value="invoice">Invoice</option>
                            <option value="contract">Contract</option><option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-5"><input type="file" name="document" class="form-control form-control-sm" required></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-sm btn-outline-success w-100">Upload</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Compliance Reports</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="/traceability/<?= (int) $batch['id'] ?>/compliance/organic" class="btn btn-sm btn-outline-secondary">Organic Certification</a>
                    <a href="/traceability/<?= (int) $batch['id'] ?>/compliance/gap" class="btn btn-sm btn-outline-secondary">GAP Compliance</a>
                    <a href="/traceability/<?= (int) $batch['id'] ?>/compliance/export" class="btn btn-sm btn-outline-secondary">Export Documentation</a>
                    <a href="/traceability/<?= (int) $batch['id'] ?>/compliance/food_safety" class="btn btn-sm btn-outline-secondary">Food Safety Audit</a>
                    <a href="/traceability/<?= (int) $batch['id'] ?>/compliance/carbon" class="btn btn-sm btn-outline-secondary">Carbon Reporting</a>
                </div>
            </div>
        </div>
    </div>
</div>

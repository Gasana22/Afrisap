<?php use App\Core\Auth; use App\Core\Csrf; $canEdit = Auth::hasPermission('livestock.edit'); $isActive = $animal['status'] === 'active'; ?>
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 font-monospace"><?= htmlspecialchars($animal['animal_code']) ?></h4>
        <p class="text-muted mb-0">
            <?= htmlspecialchars($animal['name'] ?? 'Unnamed') ?> &middot;
            <?= htmlspecialchars($animal['species']) ?><?= $animal['breed'] ? ' / ' . htmlspecialchars($animal['breed']) : '' ?> &middot;
            <span class="text-capitalize"><?= htmlspecialchars($animal['gender']) ?></span> &middot;
            <?= htmlspecialchars($animal['farm_name']) ?>
            <?php $badge = ['active' => 'success', 'sold' => 'secondary', 'deceased' => 'dark'][$animal['status']] ?? 'secondary'; ?>
            &middot; <span class="badge bg-<?= $badge ?> text-capitalize"><?= htmlspecialchars($animal['status']) ?></span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if (Auth::hasPermission('traceability.view')): ?>
        <a href="/livestock/<?= (int) $animal['id'] ?>/traceability" class="btn btn-outline-success btn-sm"><i class="bi bi-qr-code"></i> Traceability</a>
        <?php endif; ?>
        <?php if ($canEdit): ?>
        <a href="/livestock/<?= (int) $animal['id'] ?>/edit" class="btn btn-outline-secondary btn-sm">Edit</a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('livestock.delete')): ?>
        <form method="post" action="/livestock/<?= (int) $animal['id'] ?>/delete" onsubmit="return confirm('Delete this animal profile? Only possible if it has no recorded history.');">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Birth Date</div><div class="fs-6 fw-bold"><?= htmlspecialchars($animal['birth_date'] ?? '—') ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Parent</div><div class="fs-6 fw-bold"><?= htmlspecialchars($animal['parent_name'] ?? '—') ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Latest Weight</div><div class="fs-6 fw-bold"><?= !empty($weights) ? htmlspecialchars($weights[0]['weight_kg']) . ' kg' : '—' ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Tag Number</div><div class="fs-6 fw-bold"><?= htmlspecialchars($animal['tag_number'] ?? '—') ?></div></div></div>
</div>

<?php if ($mortality): ?>
<div class="alert alert-dark">
    <strong>Deceased:</strong> <?= htmlspecialchars($mortality['death_date']) ?>
    <?= $mortality['cause'] ? '— Cause: ' . htmlspecialchars($mortality['cause']) : '' ?>
    <?= $mortality['notes'] ? '— ' . htmlspecialchars($mortality['notes']) : '' ?>
</div>
<?php endif; ?>
<?php if ($sale): ?>
<div class="alert alert-secondary">
    <strong>Sold:</strong> to <?= htmlspecialchars($sale['buyer_name']) ?> on <?= htmlspecialchars($sale['sale_date']) ?>
    for <?= htmlspecialchars($sale['sale_price']) ?>
    <?= $sale['notes'] ? '— ' . htmlspecialchars($sale['notes']) : '' ?>
</div>
<?php endif; ?>

<p class="text-muted small">All history below is permanent and cannot be edited or deleted, per traceability requirements.</p>

<div class="accordion" id="animalAccordion">

    <!-- Vaccinations -->
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#secVax">Vaccinations</button></h2>
        <div id="secVax" class="accordion-collapse collapse show" data-bs-parent="#animalAccordion">
            <div class="accordion-body">
                <table class="table table-sm">
                    <thead><tr><th>Vaccine</th><th>Date</th><th>Next Due</th><th>Administered By</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php if (empty($vaccinations)): ?><tr><td colspan="5" class="text-muted small">No vaccinations recorded yet.</td></tr><?php endif; ?>
                        <?php foreach ($vaccinations as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['vaccine_name']) ?></td>
                            <td><?= htmlspecialchars($v['date_administered']) ?></td>
                            <td><?= htmlspecialchars($v['next_due_date'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($v['administered_by'] ?? '—') ?></td>
                            <td class="small"><?= htmlspecialchars($v['notes'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit && $isActive): ?>
                <form method="post" action="/livestock/<?= (int) $animal['id'] ?>/vaccinations" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-3"><input type="text" name="vaccine_name" class="form-control form-control-sm" placeholder="Vaccine name" required></div>
                    <div class="col-md-2"><input type="date" name="date_administered" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-2"><input type="date" name="next_due_date" class="form-control form-control-sm" title="Next due"></div>
                    <div class="col-md-2"><input type="text" name="administered_by" class="form-control form-control-sm" placeholder="Administered by"></div>
                    <div class="col-md-2"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Feeding -->
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secFeed">Feeding</button></h2>
        <div id="secFeed" class="accordion-collapse collapse" data-bs-parent="#animalAccordion">
            <div class="accordion-body">
                <table class="table table-sm">
                    <thead><tr><th>Feed Type</th><th>Qty</th><th>Date</th><th>Cost</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php if (empty($feedings)): ?><tr><td colspan="5" class="text-muted small">No feeding records yet.</td></tr><?php endif; ?>
                        <?php foreach ($feedings as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['feed_type']) ?></td>
                            <td><?= htmlspecialchars(($f['quantity'] ?? '—') . ' ' . ($f['unit'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($f['feeding_date']) ?></td>
                            <td><?= htmlspecialchars($f['cost'] ?? '—') ?></td>
                            <td class="small"><?= htmlspecialchars($f['notes'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit && $isActive): ?>
                <form method="post" action="/livestock/<?= (int) $animal['id'] ?>/feedings" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-2"><input type="text" name="feed_type" class="form-control form-control-sm" placeholder="Feed type" required></div>
                    <div class="col-md-2"><input type="text" name="quantity" class="form-control form-control-sm" placeholder="Qty"></div>
                    <div class="col-md-2"><input type="text" name="unit" class="form-control form-control-sm" placeholder="Unit"></div>
                    <div class="col-md-2"><input type="date" name="feeding_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-2"><input type="text" name="cost" class="form-control form-control-sm" placeholder="Cost"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Weight -->
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secWeight">Weight History</button></h2>
        <div id="secWeight" class="accordion-collapse collapse" data-bs-parent="#animalAccordion">
            <div class="accordion-body">
                <table class="table table-sm">
                    <thead><tr><th>Weight (kg)</th><th>Date</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php if (empty($weights)): ?><tr><td colspan="3" class="text-muted small">No weight records yet.</td></tr><?php endif; ?>
                        <?php foreach ($weights as $w): ?>
                        <tr><td><?= htmlspecialchars($w['weight_kg']) ?></td><td><?= htmlspecialchars($w['recorded_date']) ?></td><td class="small"><?= htmlspecialchars($w['notes'] ?? '') ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit && $isActive): ?>
                <form method="post" action="/livestock/<?= (int) $animal['id'] ?>/weights" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-3"><input type="text" name="weight_kg" class="form-control form-control-sm" placeholder="Weight (kg)" required></div>
                    <div class="col-md-3"><input type="date" name="recorded_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-4"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Treatments -->
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secTreat">Treatments</button></h2>
        <div id="secTreat" class="accordion-collapse collapse" data-bs-parent="#animalAccordion">
            <div class="accordion-body">
                <table class="table table-sm">
                    <thead><tr><th>Condition</th><th>Treatment</th><th>Date</th><th>By</th><th>Cost</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php if (empty($treatments)): ?><tr><td colspan="6" class="text-muted small">No treatments recorded yet.</td></tr><?php endif; ?>
                        <?php foreach ($treatments as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['condition_name']) ?></td>
                            <td><?= htmlspecialchars($t['treatment']) ?></td>
                            <td><?= htmlspecialchars($t['treatment_date']) ?></td>
                            <td><?= htmlspecialchars($t['administered_by'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($t['cost'] ?? '—') ?></td>
                            <td class="small"><?= htmlspecialchars($t['notes'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit && $isActive): ?>
                <form method="post" action="/livestock/<?= (int) $animal['id'] ?>/treatments" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-2"><input type="text" name="condition_name" class="form-control form-control-sm" placeholder="Condition" required></div>
                    <div class="col-md-2"><input type="text" name="treatment" class="form-control form-control-sm" placeholder="Treatment" required></div>
                    <div class="col-md-2"><input type="date" name="treatment_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-2"><input type="text" name="administered_by" class="form-control form-control-sm" placeholder="By"></div>
                    <div class="col-md-2"><input type="text" name="cost" class="form-control form-control-sm" placeholder="Cost"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Breeding -->
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secBreed">Breeding</button></h2>
        <div id="secBreed" class="accordion-collapse collapse" data-bs-parent="#animalAccordion">
            <div class="accordion-body">
                <table class="table table-sm">
                    <thead><tr><th>Mate</th><th>Date</th><th>Expected Due</th><th>Outcome</th><th>Offspring</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php if (empty($breedingRecords)): ?><tr><td colspan="6" class="text-muted small">No breeding records yet.</td></tr><?php endif; ?>
                        <?php foreach ($breedingRecords as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['mate_description'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($b['breeding_date']) ?></td>
                            <td><?= htmlspecialchars($b['expected_due_date'] ?? '—') ?></td>
                            <td><span class="badge bg-secondary text-capitalize"><?= htmlspecialchars($b['outcome']) ?></span></td>
                            <td><?= htmlspecialchars($b['offspring_count'] ?? '—') ?></td>
                            <td class="small"><?= htmlspecialchars($b['notes'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit && $isActive): ?>
                <form method="post" action="/livestock/<?= (int) $animal['id'] ?>/breeding" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-2"><input type="text" name="mate_description" class="form-control form-control-sm" placeholder="Mate"></div>
                    <div class="col-md-2"><input type="date" name="breeding_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-2"><input type="date" name="expected_due_date" class="form-control form-control-sm" title="Expected due"></div>
                    <div class="col-md-2">
                        <select name="outcome" class="form-select form-select-sm">
                            <option value="pending">Pending</option><option value="successful">Successful</option><option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="text" name="offspring_count" class="form-control form-control-sm" placeholder="Offspring #"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Production -->
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secProd">Production</button></h2>
        <div id="secProd" class="accordion-collapse collapse" data-bs-parent="#animalAccordion">
            <div class="accordion-body">
                <table class="table table-sm">
                    <thead><tr><th>Type</th><th>Qty</th><th>Date</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php if (empty($productionRecords)): ?><tr><td colspan="4" class="text-muted small">No production records yet.</td></tr><?php endif; ?>
                        <?php foreach ($productionRecords as $p): ?>
                        <tr>
                            <td class="text-capitalize"><?= htmlspecialchars($p['production_type']) ?></td>
                            <td><?= htmlspecialchars($p['quantity'] . ' ' . ($p['unit'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($p['production_date']) ?></td>
                            <td class="small"><?= htmlspecialchars($p['notes'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit && $isActive): ?>
                <form method="post" action="/livestock/<?= (int) $animal['id'] ?>/production" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-3"><input type="text" name="production_type" class="form-control form-control-sm" placeholder="e.g. Milk, Eggs" required></div>
                    <div class="col-md-2"><input type="text" name="quantity" class="form-control form-control-sm" placeholder="Qty" required></div>
                    <div class="col-md-2"><input type="text" name="unit" class="form-control form-control-sm" placeholder="Unit"></div>
                    <div class="col-md-2"><input type="date" name="production_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-2"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-sm btn-outline-success w-100">Add</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mortality & Sale -->
    <?php if ($canEdit && $isActive): ?>
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secTerminal">Record Sale / Mortality</button></h2>
        <div id="secTerminal" class="accordion-collapse collapse" data-bs-parent="#animalAccordion">
            <div class="accordion-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="small text-muted">Record Sale</h6>
                                <form method="post" action="/livestock/<?= (int) $animal['id'] ?>/sale" onsubmit="return confirm('This marks the animal as sold and cannot be undone. Continue?');">
                                    <?= Csrf::field() ?>
                                    <div class="mb-2"><input type="text" name="buyer_name" class="form-control form-control-sm" placeholder="Buyer name" required></div>
                                    <div class="mb-2"><input type="text" name="sale_price" class="form-control form-control-sm" placeholder="Sale price" required></div>
                                    <div class="mb-2"><input type="date" name="sale_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                                    <div class="mb-2"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Mark as Sold</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="small text-muted">Record Mortality</h6>
                                <form method="post" action="/livestock/<?= (int) $animal['id'] ?>/mortality" onsubmit="return confirm('This marks the animal as deceased and cannot be undone. Continue?');">
                                    <?= Csrf::field() ?>
                                    <div class="mb-2"><input type="date" name="death_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                                    <div class="mb-2"><input type="text" name="cause" class="form-control form-control-sm" placeholder="Cause"></div>
                                    <div class="mb-2"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                                    <button type="submit" class="btn btn-sm btn-outline-dark w-100">Mark as Deceased</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

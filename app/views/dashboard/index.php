<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">Welcome, <?= htmlspecialchars($currentUser['name']) ?></h4>
        <p class="text-muted mb-0"><?= htmlspecialchars($currentUser['role_name']) ?></p>
    </div>
</div>

<div class="row g-3">
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Farms</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['farms'] ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Blocks</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['blocks'] ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Plots</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['plots'] ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card p-3">
            <div class="text-muted small">Users</div>
            <div class="fs-3 fw-bold"><?= (int) $stats['users'] ?></div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        <h6 class="card-title">Platform roadmap</h6>
        <p class="text-muted small mb-0">
            Phase 1 (this release) covers user management, the admin panel, and farm structure
            (farms / blocks / plots). Crop management, livestock, workers, finance, procurement,
            inventory, assets, traceability &amp; QR codes, reports, and maps follow in later phases.
        </p>
    </div>
</div>

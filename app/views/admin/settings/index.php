<h4 class="mb-4">Settings</h4>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Company / Farm Settings</h6>
                <form method="post" action="/admin/settings">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Company name</label>
                        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Default currency</label>
                        <input type="text" name="default_currency" class="form-control" value="<?= htmlspecialchars($settings['default_currency'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Default units</label>
                        <select name="default_units" class="form-select">
                            <option value="metric" <?= ($settings['default_units'] ?? '') === 'metric' ? 'selected' : '' ?>>Metric</option>
                            <option value="imperial" <?= ($settings['default_units'] ?? '') === 'imperial' ? 'selected' : '' ?>>Imperial</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">System Health</h6>
                <table class="table table-sm mb-0">
                    <tr><td>Database size</td><td class="text-end"><?= htmlspecialchars((string) $dbSizeMb) ?> MB</td></tr>
                    <tr><td>Uploads storage used</td><td class="text-end"><?= htmlspecialchars((string) $uploadsSizeMb) ?> MB</td></tr>
                    <tr><td>Last backup</td><td class="text-end text-muted">Not configured yet</td></tr>
                </table>
                <p class="text-muted small mt-3 mb-0">Automated backups and SMTP mail settings will be wired in during hardening (Phase 10).</p>
            </div>
        </div>
    </div>
</div>

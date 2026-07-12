<?php
/**
 * Escape output for safe HTML display.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect relative to BASE_URL and stop execution.
 * Usage: redirect('/admin/dashboard.php');
 */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/**
 * Set a one-time flash message, or read (and clear) one if $message is omitted.
 * Usage: flash('error', 'Something went wrong.');   // set
 *        $msg = flash('error');                      // read + clear
 */
function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

/**
 * Stop the request with a 403 unless the current user holds $code.
 * Use for mutating actions (create/update/delete) inside a module; leave
 * read access open to any logged-in user, same as the rest of the admin panel.
 */
function require_permission(string $code): void
{
    if (!has_permission($code)) {
        http_response_code(403);
        exit('403 Forbidden — missing permission: ' . e($code));
    }
}

/**
 * Stop the request with a 403 if the current user is platform staff (Super
 * Admin, Manager, Accountant). Admin Portal staff keep the platform running
 * -- they manage organizations' accounts and the shared role/permission
 * catalog -- but they don't take part in any single organization's day-to-day
 * farm operations. Require this at the top of every farm-operational module
 * (farms, crops, livestock, workers, finance, procurement, inventory,
 * assets, traceability, media, reports, compliance).
 */
function require_tenant_user(): void
{
    if (is_platform_user()) {
        http_response_code(403);
        exit('403 Forbidden — Admin Portal staff manage the platform, not farm operations. See Organizations for a read-only overview.');
    }
}

/**
 * Insert a notification for a user. Used for things like task assignment,
 * low stock, or a newly generated trace batch.
 */
function notify(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
{
    db()->prepare(
        'INSERT INTO notifications (user_id, title, message, type, link) VALUES (:user_id, :title, :message, :type, :link)'
    )->execute([
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'link' => $link,
    ]);
}

/**
 * Notify every user in an organization (or every platform user, if
 * $organizationId is null) -- e.g. "low stock", "new purchase order".
 */
function notify_organization(?int $organizationId, string $title, string $message, string $type = 'info', ?string $link = null): void
{
    if ($organizationId === null) {
        $stmt = db()->prepare('SELECT u.id FROM users u JOIN roles r ON r.id = u.role_id WHERE r.scope = "platform" AND u.status = "active"');
        $stmt->execute();
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE organization_id = :org_id AND status = "active"');
        $stmt->execute(['org_id' => $organizationId]);
    }
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) {
        notify((int) $userId, $title, $message, $type, $link);
    }
}

/**
 * Create the matching trace_batches row for a new crop cycle or animal.
 * There's no DB trigger for this (flat-file app, no migration runner
 * feature for triggers) so every insert path that creates a crop_cycle or
 * animal must call this right after.
 */
function create_trace_batch(string $batchType, string $batchCode, ?int $cropCycleId = null, ?int $animalId = null): void
{
    db()->prepare(
        'INSERT INTO trace_batches (batch_type, crop_cycle_id, animal_id, batch_code, status)
         VALUES (:type, :crop_cycle_id, :animal_id, :code, "active")'
    )->execute([
        'type' => $batchType,
        'crop_cycle_id' => $cropCycleId,
        'animal_id' => $animalId,
        'code' => $batchCode,
    ]);
}

/**
 * Name of the current tenant user's organization, for display (e.g. the
 * admin topbar) -- helps make it visible that this is one of potentially
 * many organizations on the platform, not the only one. Null for platform
 * users (organization_id is null for them) or a logged-out request.
 */
function current_organization_name(): ?string
{
    $organizationId = current_organization_id();
    if ($organizationId === null) {
        return null;
    }
    static $cache = [];
    if (array_key_exists($organizationId, $cache)) {
        return $cache[$organizationId];
    }
    $stmt = db()->prepare('SELECT name FROM organizations WHERE id = :id');
    $stmt->execute(['id' => $organizationId]);
    return $cache[$organizationId] = $stmt->fetchColumn() ?: null;
}

/**
 * Records one row in audit_logs. Called after a mutating action succeeds --
 * $old/$new are the record's state before/after (omit either for a pure
 * create or delete). Wired into the major create/update/delete/status-change
 * actions across every module; not every single nested sub-action has a
 * call (e.g. individual crop-cycle input/nursery/monitoring rows don't) --
 * those are lower-value entries and can be added the same way if needed.
 */
function audit_log(string $action, string $table, ?string $recordId = null, ?array $old = null, ?array $new = null): void
{
    db()->prepare(
        'INSERT INTO audit_logs (user_id, organization_id, action, table_name, record_id, old_value, new_value, ip_address, device)
         VALUES (:user_id, :organization_id, :action, :table_name, :record_id, :old_value, :new_value, :ip_address, :device)'
    )->execute([
        'user_id' => is_logged_in() ? current_user()['id'] : null,
        'organization_id' => current_organization_id(),
        'action' => $action,
        'table_name' => $table,
        'record_id' => $recordId,
        'old_value' => $old !== null ? json_encode($old) : null,
        'new_value' => $new !== null ? json_encode($new) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'device' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

/**
 * Fetch a farm in the current tenant's organization, or exit with 404.
 * Only ever called from farm-operational pages, which call
 * require_tenant_user() first -- platform staff never reach this.
 */
function farm_or_404(int $farmId): array
{
    $stmt = db()->prepare('SELECT * FROM farms WHERE id = :id AND organization_id = :org_id');
    $stmt->execute(['id' => $farmId, 'org_id' => current_organization_id()]);
    $farm = $stmt->fetch();
    if (!$farm) {
        http_response_code(404);
        exit('404 — farm not found.');
    }
    return $farm;
}

/**
 * Farm ids belonging to the current tenant, for IN (...) scoping on pages
 * that span all of a tenant's farms at once (livestock, workers, inventory,
 * ...). Returns [] for a tenant user with no farms yet -- callers should
 * treat an empty array as "show nothing", not "show everything". Only ever
 * called from farm-operational pages, which call require_tenant_user()
 * first -- platform staff never reach this.
 */
function visible_farm_ids(): array
{
    $stmt = db()->prepare('SELECT id FROM farms WHERE organization_id = :org_id');
    $stmt->execute(['org_id' => current_organization_id()]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Build a "(?, ?, ?)" placeholder list sized to $items for a prepared IN () clause.
 */
function in_placeholders(array $items): string
{
    return implode(',', array_fill(0, count($items), '?'));
}

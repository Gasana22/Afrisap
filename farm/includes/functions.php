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
 * Fetch a farm the current user is allowed to see, or exit with 404.
 * Platform users can see any farm; tenant users only their own organization's.
 */
function farm_or_404(int $farmId): array
{
    if (is_platform_user()) {
        $stmt = db()->prepare('SELECT * FROM farms WHERE id = :id');
        $stmt->execute(['id' => $farmId]);
    } else {
        $stmt = db()->prepare('SELECT * FROM farms WHERE id = :id AND organization_id = :org_id');
        $stmt->execute(['id' => $farmId, 'org_id' => current_organization_id()]);
    }
    $farm = $stmt->fetch();
    if (!$farm) {
        http_response_code(404);
        exit('404 — farm not found.');
    }
    return $farm;
}

/**
 * Farm ids visible to the current user, for IN (...) scoping on pages that
 * span all of a tenant's farms at once (livestock, workers, inventory, ...).
 * Returns [] for a tenant user with no farms yet -- callers should treat an
 * empty array as "show nothing", not "show everything".
 */
function visible_farm_ids(): array
{
    if (is_platform_user()) {
        return array_map('intval', db()->query('SELECT id FROM farms')->fetchAll(PDO::FETCH_COLUMN));
    }
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

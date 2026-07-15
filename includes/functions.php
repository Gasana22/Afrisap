<?php
/**
 * General helper functions used across the app.
 */

function app_config(): array
{
    static $config = null;
    $config ??= require __DIR__ . '/../config/config.php';

    return $config;
}

function base_url(string $path = ''): string
{
    $url = app_config()['app']['url'];

    return $path === '' ? $url : $url . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : base_url($path)));
    exit;
}

function format_money(?float $amount, string $currency = 'USD'): string
{
    return $currency . ' ' . number_format((float) $amount, 2);
}

function format_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '-';
    }

    $ts = strtotime($date);

    return $ts ? date($format, $ts) : '-';
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);

    return trim($text, '-');
}

function generate_unique_slug(string $table, string $base): string
{
    $slug = slugify($base) ?: 'org';
    $original = $slug;
    $i = 1;

    while (db_value("SELECT COUNT(*) FROM $table WHERE slug = :slug", ['slug' => $slug]) > 0) {
        $slug = $original . '-' . $i;
        $i++;
    }

    return $slug;
}

function generate_batch_id(string $prefix = 'BATCH'): string
{
    return strtoupper($prefix) . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

function generate_employee_id(): string
{
    return 'EMP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

function generate_order_number(): string
{
    return 'PO-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old_input'][$key] ?? $default;
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'active', 'completed', 'verified', 'paid', 'present' => 'badge-success',
        'pending', 'planning', 'draft', 'trial' => 'badge-warning',
        'suspended', 'canceled', 'terminated', 'expired', 'absent', 'failed' => 'badge-danger',
        'in_progress', 'sent', 'partial' => 'badge-info',
        default => 'badge-secondary',
    };
}

function humanize(string $value): string
{
    return ucwords(str_replace(['_', '-'], ' ', $value));
}

function app_log_error(string $message): void
{
    $line = sprintf('[%s] %s%s', date('c'), $message, PHP_EOL);
    @file_put_contents(LOGS_PATH . '/errors.log', $line, FILE_APPEND);
}

function app_log_access(string $message): void
{
    $line = sprintf('[%s] %s%s', date('c'), $message, PHP_EOL);
    @file_put_contents(LOGS_PATH . '/access.log', $line, FILE_APPEND);
}

function paginate_params(int $defaultPerPage = 20): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? $defaultPerPage)));

    return ['page' => $page, 'per_page' => $perPage, 'offset' => ($page - 1) * $perPage];
}

function generate_animal_id(): string
{
    return 'AN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

/**
 * Human-readable age string ("2y 3m", "5m 2d", "3d") computed from a DATE
 * column. Returns '-' when the birth date is missing or in the future.
 */
function calculate_age(?string $birthDate): string
{
    if (!$birthDate) {
        return '-';
    }

    try {
        $birth = new DateTime($birthDate);
    } catch (Exception) {
        return '-';
    }

    $now = new DateTime();
    if ($birth > $now) {
        return '-';
    }

    $diff = $birth->diff($now);

    if ($diff->y > 0) {
        return $diff->y . 'y ' . $diff->m . 'm';
    }
    if ($diff->m > 0) {
        return $diff->m . 'm ' . $diff->d . 'd';
    }

    return $diff->d . 'd';
}

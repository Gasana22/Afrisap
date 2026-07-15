<?php
/**
 * Platform-admin-only helpers for queries that span ALL organizations
 * (no organization_id filter). This is the one place in the app where
 * that is correct - every admin/*.php page requires require_platform_admin().
 *
 * Mirrors includes/charts.php (chart_monthly_series / chart_group_totals)
 * but without tenant scoping.
 */

function platform_monthly_series(string $table, string $dateColumn, string $valueExpr = 'COUNT(*)', string $extraWhere = '', array $params = [], int $months = 6): array
{
    $params['months'] = $months;

    $rows = db_all(
        "SELECT DATE_FORMAT($dateColumn, '%Y-%m') AS ym, $valueExpr AS total
         FROM $table
         WHERE $dateColumn >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
           $extraWhere
         GROUP BY ym
         ORDER BY ym",
        $params
    );

    $byMonth = [];
    foreach ($rows as $row) {
        $byMonth[$row['ym']] = (float) $row['total'];
    }

    $labels = [];
    $data = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime("-$i months"));
        $labels[] = date('M Y', strtotime("-$i months"));
        $data[] = $byMonth[$ym] ?? 0;
    }

    return ['labels' => $labels, 'data' => $data];
}

function platform_group_totals(string $table, string $groupColumn, string $valueExpr = 'COUNT(*)', string $extraWhere = '', array $params = []): array
{
    $rows = db_all(
        "SELECT $groupColumn AS label, $valueExpr AS total
         FROM $table
         WHERE 1 = 1 $extraWhere
         GROUP BY $groupColumn
         ORDER BY total DESC",
        $params
    );

    return [
        'labels' => array_map(fn ($r) => humanize((string) $r['label']), $rows),
        'data' => array_map(fn ($r) => (float) $r['total'], $rows),
    ];
}

/**
 * Upsert one row into system_settings (setting_key is UNIQUE).
 */
function upsert_setting(string $key, ?string $value, string $group): void
{
    db_query(
        'INSERT INTO system_settings (setting_key, setting_value, setting_group)
         VALUES (:key, :value, :group)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)',
        ['key' => $key, 'value' => $value, 'group' => $group]
    );
}

/**
 * Fetch all system_settings for a group as a flat [setting_key => setting_value] map.
 */
function settings_group(string $group): array
{
    $rows = db_all('SELECT setting_key, setting_value FROM system_settings WHERE setting_group = :group', ['group' => $group]);
    $out = [];
    foreach ($rows as $row) {
        $out[$row['setting_key']] = $row['setting_value'];
    }

    return $out;
}

/**
 * Render simple Bootstrap pagination controls for admin list pages.
 */
function render_pagination(int $page, int $perPage, int $totalRows, string $baseUrl, array $queryParams = []): void
{
    $totalPages = (int) ceil($totalRows / max(1, $perPage));
    if ($totalPages <= 1) {
        return;
    }

    $buildUrl = function (int $p) use ($baseUrl, $queryParams) {
        $params = array_merge($queryParams, ['page' => $p]);

        return $baseUrl . '?' . http_build_query($params);
    };

    echo '<nav aria-label="Page navigation"><ul class="pagination justify-content-end mt-3">';
    echo '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . e($buildUrl(max(1, $page - 1))) . '">Previous</a></li>';
    for ($p = 1; $p <= $totalPages; $p++) {
        echo '<li class="page-item' . ($p === $page ? ' active' : '') . '"><a class="page-link" href="' . e($buildUrl($p)) . '">' . $p . '</a></li>';
    }
    echo '<li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '"><a class="page-link" href="' . e($buildUrl(min($totalPages, $page + 1))) . '">Next</a></li>';
    echo '</ul></nav>';
}

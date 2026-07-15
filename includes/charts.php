<?php
/**
 * Chart data preparation - returns plain arrays ready to be json_encode()'d
 * for Chart.js on the front end.
 */

function chart_monthly_series(int $organizationId, string $table, string $dateColumn, string $valueColumn, string $extraWhere = '', array $params = [], int $months = 6): array
{
    $params['organization_id'] = $organizationId;
    $params['months'] = $months;

    $rows = db_all(
        "SELECT DATE_FORMAT($dateColumn, '%Y-%m') AS ym, SUM($valueColumn) AS total
         FROM $table
         WHERE organization_id = :organization_id
           AND $dateColumn >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
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

function chart_group_totals(int $organizationId, string $table, string $groupColumn, string $valueColumn, string $extraWhere = '', array $params = []): array
{
    $params['organization_id'] = $organizationId;

    $rows = db_all(
        "SELECT $groupColumn AS label, SUM($valueColumn) AS total
         FROM $table
         WHERE organization_id = :organization_id $extraWhere
         GROUP BY $groupColumn
         ORDER BY total DESC",
        $params
    );

    return [
        'labels' => array_map(fn ($r) => humanize((string) $r['label']), $rows),
        'data' => array_map(fn ($r) => (float) $r['total'], $rows),
    ];
}

<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/export.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$report = $_GET['report'] ?? '';
$format = $_GET['format'] ?? 'csv';

if (!in_array($format, ['csv', 'pdf', 'excel'], true)) {
    $format = 'csv';
}

$headers = [];
$rows = [];
$title = 'Report';

switch ($report) {
    case 'operational':
        $date = $_GET['date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $title = 'Operational Report - ' . $date;
        $headers = ['Time', 'Type', 'Summary', 'Detail'];

        $cropOperations = db_all(
            "SELECT co.*, cc.crop_type, cc.crop_batch_id, f.name AS farm_name
             FROM crop_operations co
             JOIN crop_cycles cc ON cc.id = co.crop_cycle_id
             JOIN farms f ON f.id = cc.farm_id
             WHERE cc.organization_id = :id AND co.activity_date = :date
             ORDER BY co.created_at DESC",
            ['id' => $orgId, 'date' => $date]
        );
        $attendanceRecords = db_all(
            "SELECT a.*, w.name AS worker_name
             FROM attendance a
             JOIN workers w ON w.id = a.worker_id
             WHERE w.organization_id = :id AND a.date = :date
             ORDER BY a.clock_in ASC",
            ['id' => $orgId, 'date' => $date]
        );
        $completedTasks = db_all(
            "SELECT t.*, w.name AS worker_name
             FROM tasks t
             JOIN workers w ON w.id = t.assigned_to
             WHERE t.organization_id = :id AND t.status IN ('completed', 'verified') AND DATE(t.completed_at) = :date
             ORDER BY t.completed_at DESC",
            ['id' => $orgId, 'date' => $date]
        );

        $timeline = [];
        foreach ($cropOperations as $r) {
            $timeline[] = [$r['created_at'], 'Crop Operation', humanize($r['activity_type']) . ' - ' . humanize($r['crop_type']), ($r['description'] ?: 'No notes') . ' (Farm: ' . $r['farm_name'] . ')'];
        }
        foreach ($attendanceRecords as $r) {
            $timeline[] = [$date . ' ' . ($r['clock_in'] ?: '00:00:00'), 'Attendance', $r['worker_name'] . ' - ' . humanize($r['status']), 'In: ' . ($r['clock_in'] ?: '-') . ', Out: ' . ($r['clock_out'] ?: '-')];
        }
        foreach ($completedTasks as $r) {
            $timeline[] = [$r['completed_at'], 'Task Completed', $r['title'], 'Assigned to: ' . $r['worker_name']];
        }
        usort($timeline, fn ($a, $b) => strcmp((string) $b[0], (string) $a[0]));
        $rows = $timeline;
        break;

    case 'agricultural':
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 year'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $season = $_GET['season'] ?? '';
        $title = 'Agricultural Report';
        $headers = ['Batch', 'Crop', 'Variety', 'Season', 'Status', 'Expected Yield', 'Actual Yield'];

        $seasonWhere = '';
        $params = ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'date_from2' => $dateFrom, 'date_to2' => $dateTo];
        if ($season !== '') {
            $seasonWhere = ' AND cc.season = :season';
            $params['season'] = $season;
        }
        $cycles = db_all(
            "SELECT cc.crop_batch_id, cc.crop_type, cc.variety, cc.season, cc.status, cc.expected_yield, cc.actual_yield
             FROM crop_cycles cc
             WHERE cc.organization_id = :id AND (cc.start_date BETWEEN :date_from AND :date_to OR cc.end_date BETWEEN :date_from2 AND :date_to2) $seasonWhere
             ORDER BY cc.start_date DESC",
            $params
        );
        foreach ($cycles as $c) {
            $rows[] = [$c['crop_batch_id'], humanize($c['crop_type']), $c['variety'] ?: '-', $c['season'] ?: '-', humanize($c['status']), $c['expected_yield'], $c['actual_yield']];
        }
        break;

    case 'livestock':
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-90 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $title = 'Livestock Report';
        $headers = ['Event Date', 'Event Type', 'Animal', 'Species', 'Details', 'Cost'];

        $events = db_all(
            "SELECT ae.*, a.name AS animal_name, a.animal_id AS animal_tag, a.species
             FROM animal_events ae
             JOIN animals a ON a.id = ae.animal_id
             WHERE a.organization_id = :id AND ae.event_date BETWEEN :date_from AND :date_to
             ORDER BY ae.event_date DESC",
            ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo]
        );
        foreach ($events as $e) {
            $rows[] = [$e['event_date'], humanize($e['event_type']), $e['animal_name'] ?: $e['animal_tag'], humanize($e['species']), $e['details'] ?: '-', $e['cost']];
        }
        break;

    case 'financial':
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $title = 'Financial Report';
        $headers = ['Date', 'Type', 'Category', 'Sub-category', 'Description', 'Amount'];

        $transactions = db_all(
            "SELECT * FROM financial_transactions WHERE organization_id = :id AND transaction_date BETWEEN :date_from AND :date_to ORDER BY transaction_date DESC",
            ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo]
        );
        foreach ($transactions as $t) {
            $rows[] = [$t['transaction_date'], humanize($t['type']), $t['category'], $t['sub_category'] ?: '-', $t['description'] ?: '-', $t['amount']];
        }
        break;

    case 'productivity':
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $title = 'Worker Productivity Report';
        $headers = ['Worker', 'Department', 'Tasks Completed', 'Avg Completion Time (hrs)'];

        $workerRows = db_all(
            "SELECT w.name AS worker_name, w.department,
                    COUNT(t.id) AS tasks_completed,
                    AVG(CASE WHEN t.completed_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, t.created_at, t.completed_at) END) AS avg_hours
             FROM tasks t
             JOIN workers w ON w.id = t.assigned_to
             WHERE t.organization_id = :id AND t.status IN ('completed', 'verified')
               AND ((t.completed_at IS NOT NULL AND DATE(t.completed_at) BETWEEN :date_from AND :date_to)
                    OR (t.completed_at IS NULL AND t.updated_at BETWEEN :date_from2 AND :date_to2))
             GROUP BY w.id, w.name, w.department
             ORDER BY tasks_completed DESC",
            ['id' => $orgId, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'date_from2' => $dateFrom . ' 00:00:00', 'date_to2' => $dateTo . ' 23:59:59']
        );
        foreach ($workerRows as $w) {
            $rows[] = [$w['worker_name'], $w['department'] ?: '-', $w['tasks_completed'], $w['avg_hours'] !== null ? round((float) $w['avg_hours'], 1) : '-'];
        }
        break;

    default:
        session_flash('error', 'Unknown report requested.');
        redirect('org-admin/reporting/dashboard.php');
}

$filename = strtolower(str_replace(' ', '-', $title)) . '-' . date('Ymd');

if ($format === 'csv') {
    export_csv($headers, $rows, $filename);
}

if ($format === 'excel') {
    export_excel($headers, $rows, $filename, substr($title, 0, 31));
}

// PDF: build a simple HTML table.
$html = '<html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:11px;}
h2{margin-bottom:4px;}
p{color:#666;margin-top:0;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #ccc;padding:4px 6px;text-align:left;}
th{background:#f0f0f0;}
</style></head><body>';
$html .= '<h2>' . htmlspecialchars($title) . '</h2>';
$html .= '<p>' . htmlspecialchars($organization['name']) . ' &middot; Generated ' . date('d M Y H:i') . '</p>';
$html .= '<table><thead><tr>';
foreach ($headers as $h) {
    $html .= '<th>' . htmlspecialchars((string) $h) . '</th>';
}
$html .= '</tr></thead><tbody>';
foreach ($rows as $row) {
    $html .= '<tr>';
    foreach ($row as $cell) {
        $html .= '<td>' . htmlspecialchars((string) $cell) . '</td>';
    }
    $html .= '</tr>';
}
if (!$rows) {
    $html .= '<tr><td colspan="' . count($headers) . '">No data for the selected filters.</td></tr>';
}
$html .= '</tbody></table></body></html>';

export_pdf($html, $filename);

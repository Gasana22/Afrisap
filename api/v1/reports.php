<?php
/**
 * Read-only analytics endpoint: GET ?report=dashboard returns the same key
 * metrics used on org-admin/index.php (useful for a future mobile app or
 * widget refresh via JS polling). Distinct in purpose from dashboard.php,
 * which is a "what's new right now" activity feed rather than analytics.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = current_org_user();
if (!$currentUser) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}
$organization = current_organization();
$orgId = (int) $organization['id'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    $report = $_GET['report'] ?? null;

    if ($report !== 'dashboard') {
        json_response(['success' => false, 'message' => 'Unknown or missing report. Supported reports: dashboard'], 400);
    }

    $totalLandArea = (float) db_value(
        'SELECT COALESCE(SUM(size), 0) FROM farms WHERE organization_id = :id',
        ['id' => $orgId]
    );
    $activeCrops = tenant_count('crop_cycles', $orgId, "AND status != 'completed'");
    $totalWorkers = tenant_count('workers', $orgId, "AND status = 'active'");
    $revenueThisMonth = (float) db_value(
        "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions
         WHERE organization_id = :id AND type = 'income' AND transaction_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
        ['id' => $orgId]
    );
    $expenseThisMonth = (float) db_value(
        "SELECT COALESCE(SUM(amount), 0) FROM financial_transactions
         WHERE organization_id = :id AND type = 'expense' AND transaction_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
        ['id' => $orgId]
    );

    json_response([
        'success' => true,
        'data' => [
            'total_land_area' => $totalLandArea,
            'active_crops' => $activeCrops,
            'total_workers' => $totalWorkers,
            'revenue_this_month' => $revenueThisMonth,
            'expense_this_month' => $expenseThisMonth,
            'generated_at' => date('c'),
        ],
    ]);
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

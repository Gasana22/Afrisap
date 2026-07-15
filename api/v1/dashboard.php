<?php
/**
 * Live-refreshing dashboard widget feed: recent tasks, recent notifications,
 * unread notification count, and low stock item count. Distinct in purpose
 * from reports.php (which is analytics/report numbers) - this is "what's
 * new right now".
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

    $recentTasks = db_all(
        'SELECT t.*, w.name AS worker_name FROM tasks t
         JOIN workers w ON w.id = t.assigned_to
         WHERE t.organization_id = :id ORDER BY t.updated_at DESC LIMIT 5',
        ['id' => $orgId]
    );

    $lowStockCount = (int) db_value(
        "SELECT COUNT(*) FROM inventory_items
         WHERE organization_id = :id AND status = 'active' AND reorder_level IS NOT NULL AND quantity <= reorder_level",
        ['id' => $orgId]
    );

    json_response([
        'success' => true,
        'data' => [
            'recent_tasks' => $recentTasks,
            'unread_notification_count' => unread_notification_count($orgId, (int) $currentUser['id']),
            'recent_notifications' => recent_notifications($orgId, (int) $currentUser['id'], 5),
            'low_stock_count' => $lowStockCount,
            'generated_at' => date('c'),
        ],
    ]);
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

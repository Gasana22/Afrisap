<?php
/**
 * Offline action sync endpoint - consumed by assets/js/offline.js.
 *
 * Accepts a queued action recorded while the worker device was offline
 * (clock in/out, activity log, ...), applies the underlying change where
 * we can do so unambiguously, and always records the action in
 * offline_actions for an auditable history.
 *
 * Request body (JSON): { action_type, action_data, queued_at, csrf_token }
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

$worker = require_worker();
$orgId = (int) $worker['organization_id'];
$workerId = (int) $worker['id'];

if (!is_post()) {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    json_response(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
}

// This endpoint receives a JSON body (see api.js), not a form post, so the
// token lives in the decoded payload rather than $_POST - csrf_verify()
// can't be used directly here.
$token = $payload['csrf_token'] ?? '';
if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    json_response(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
}

$actionType = (string) ($payload['action_type'] ?? '');
$actionData = is_array($payload['action_data'] ?? null) ? $payload['action_data'] : [];

if ($actionType === '') {
    json_response(['success' => false, 'message' => 'Missing action_type.'], 400);
}

try {
    switch ($actionType) {
        case 'clock_in':
            $date = $actionData['date'] ?? date('Y-m-d');
            $existing = db_one('SELECT * FROM attendance WHERE worker_id = :wid AND date = :d', ['wid' => $workerId, 'd' => $date]);
            if (!$existing) {
                db_insert('attendance', [
                    'worker_id' => $workerId,
                    'date' => $date,
                    'clock_in' => date('H:i:s'),
                    'status' => 'present',
                    'gps_latitude_in' => $actionData['gps_latitude_in'] ?? null,
                    'gps_longitude_in' => $actionData['gps_longitude_in'] ?? null,
                ]);
            }
            break;

        case 'clock_out':
            $date = $actionData['date'] ?? date('Y-m-d');
            $existing = db_one('SELECT * FROM attendance WHERE worker_id = :wid AND date = :d', ['wid' => $workerId, 'd' => $date]);
            if ($existing && !$existing['clock_out']) {
                db_update('attendance', [
                    'clock_out' => date('H:i:s'),
                    'gps_latitude_out' => $actionData['gps_latitude_out'] ?? null,
                    'gps_longitude_out' => $actionData['gps_longitude_out'] ?? null,
                ], 'id = :id', ['id' => $existing['id']]);
            }
            break;

        case 'activity_log':
        default:
            // No dedicated worker activity-log table exists (crop_operations
            // requires a NOT NULL crop_cycle_id that a quick field note may
            // not have) - the offline_actions insert below is the record.
            break;
    }
} catch (Throwable $e) {
    app_log_error('api/v1/sync.php failed to apply ' . $actionType . ': ' . $e->getMessage());
}

db_insert('offline_actions', [
    'organization_id' => $orgId,
    'worker_id' => $workerId,
    'action_type' => $actionType,
    'action_data' => json_encode($actionData),
    'status' => 'synced',
    'synced_at' => date('Y-m-d H:i:s'),
]);

json_response(['success' => true]);

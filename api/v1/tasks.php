<?php
/**
 * API for tasks.
 * GET (list, filterable by ?status= and ?assigned_to=, or ?id= for one),
 * POST (create), PUT ?id= (status-only or full-field update), DELETE ?id=.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = current_org_user();
if (!$currentUser) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}
$organization = current_organization();
$orgId = (int) $organization['id'];

function api_body(): array
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function api_verify_csrf(array $input): void
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) ($input['csrf_token'] ?? ''))) {
        json_response(['success' => false, 'message' => 'Invalid or missing CSRF token'], 403);
    }
}

function api_block_belongs_to_org(int $blockId, int $orgId): bool
{
    $row = db_one(
        'SELECT b.id FROM blocks b JOIN farms f ON f.id = b.farm_id WHERE b.id = :block_id AND f.organization_id = :org_id',
        ['block_id' => $blockId, 'org_id' => $orgId]
    );

    return (bool) $row;
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $id = clean_int($_GET['id'] ?? null);
            if ($id) {
                $task = tenant_find('tasks', $orgId, $id);
                if (!$task) {
                    json_response(['success' => false, 'message' => 'Task not found'], 404);
                }
                json_response(['success' => true, 'data' => $task]);
            }

            $extraSql = '';
            $params = [];
            if (!empty($_GET['status'])) {
                $extraSql .= ' AND status = :status';
                $params['status'] = clean_string($_GET['status']);
            }
            if (!empty($_GET['assigned_to'])) {
                $extraSql .= ' AND assigned_to = :assigned_to';
                $params['assigned_to'] = (int) $_GET['assigned_to'];
            }
            $extraSql .= ' ORDER BY deadline ASC, created_at DESC';

            $tasks = tenant_all('tasks', $orgId, $extraSql, $params);
            json_response(['success' => true, 'data' => $tasks]);
            break;

        case 'POST':
            $input = api_body();
            api_verify_csrf($input);

            $errors = validate($input, [
                'assigned_to' => 'required|numeric',
                'task_type' => 'required|max:100',
                'title' => 'required|max:255',
            ]);
            if ($errors) {
                json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
            }

            $assignedTo = (int) $input['assigned_to'];
            if (!tenant_find('workers', $orgId, $assignedTo)) {
                json_response(['success' => false, 'message' => 'Invalid assigned_to worker for this organization'], 422);
            }

            $farmId = isset($input['farm_id']) ? (int) $input['farm_id'] : null;
            if ($farmId && !tenant_find('farms', $orgId, $farmId)) {
                json_response(['success' => false, 'message' => 'Invalid farm_id for this organization'], 422);
            }

            $blockId = isset($input['block_id']) ? (int) $input['block_id'] : null;
            if ($blockId && !api_block_belongs_to_org($blockId, $orgId)) {
                json_response(['success' => false, 'message' => 'Invalid block_id for this organization'], 422);
            }

            // tasks.assigned_by is NOT NULL and references workers.id - use the
            // workers row tied to the current org user if one exists, otherwise
            // pragmatically fall back to the assignee's own worker id.
            $assignerRow = db_one(
                'SELECT id FROM workers WHERE organization_id = :org_id AND user_id = :user_id',
                ['org_id' => $orgId, 'user_id' => $currentUser['id']]
            );
            $assignedBy = $assignerRow ? (int) $assignerRow['id'] : $assignedTo;

            $data = [
                'assigned_to' => $assignedTo,
                'assigned_by' => $assignedBy,
                'farm_id' => $farmId,
                'block_id' => $blockId,
                'task_type' => clean_string($input['task_type']),
                'title' => clean_string($input['title']),
                'description' => clean_string($input['description'] ?? ''),
                'priority' => $input['priority'] ?? 'medium',
                'deadline' => $input['deadline'] ?? null,
            ];

            $newId = tenant_insert('tasks', $orgId, $data);
            audit_log($orgId, $currentUser['id'], 'create', 'tasks', $newId, null, $data);

            $newTask = tenant_find('tasks', $orgId, $newId);
            $assigneeWorker = db_one('SELECT user_id FROM workers WHERE id = :id', ['id' => $assignedTo]);
            if ($assigneeWorker && $assigneeWorker['user_id']) {
                notify($orgId, (int) $assigneeWorker['user_id'], 'task_assigned', 'New Task Assigned', $data['title'], null);
            }

            json_response(['success' => true, 'data' => $newTask], 201);
            break;

        case 'PUT':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('tasks', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Task not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            if (isset($input['assigned_to']) && !tenant_find('workers', $orgId, (int) $input['assigned_to'])) {
                json_response(['success' => false, 'message' => 'Invalid assigned_to worker for this organization'], 422);
            }
            if (isset($input['farm_id']) && $input['farm_id'] && !tenant_find('farms', $orgId, (int) $input['farm_id'])) {
                json_response(['success' => false, 'message' => 'Invalid farm_id for this organization'], 422);
            }
            if (isset($input['block_id']) && $input['block_id'] && !api_block_belongs_to_org((int) $input['block_id'], $orgId)) {
                json_response(['success' => false, 'message' => 'Invalid block_id for this organization'], 422);
            }

            $allowed = [
                'assigned_to', 'farm_id', 'block_id', 'task_type', 'title', 'description',
                'priority', 'deadline', 'status', 'gps_latitude', 'gps_longitude',
            ];
            $data = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $input)) {
                    $data[$field] = $input[$field];
                }
            }
            if (!$data) {
                json_response(['success' => false, 'message' => 'No updatable fields provided'], 400);
            }

            // Lightweight status transitions also stamp the relevant timestamp.
            if (isset($data['status'])) {
                if ($data['status'] === 'completed' && $existing['status'] !== 'completed') {
                    $data['completed_at'] = date('Y-m-d H:i:s');
                }
                if ($data['status'] === 'verified' && !$existing['verified_at']) {
                    $data['verified_at'] = date('Y-m-d H:i:s');
                }
            }

            tenant_update('tasks', $orgId, $id, $data);
            audit_log($orgId, $currentUser['id'], 'update', 'tasks', $id, $existing, $data);
            json_response(['success' => true, 'data' => tenant_find('tasks', $orgId, $id)]);
            break;

        case 'DELETE':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('tasks', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Task not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            tenant_delete('tasks', $orgId, $id);
            audit_log($orgId, $currentUser['id'], 'delete', 'tasks', $id, $existing, null);
            json_response(['success' => true, 'data' => ['id' => $id]]);
            break;

        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

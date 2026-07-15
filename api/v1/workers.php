<?php
/**
 * CRUD API for workers.
 * GET (list / ?id=), POST (create), PUT ?id= (update), DELETE ?id=.
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

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $id = clean_int($_GET['id'] ?? null);
            if ($id) {
                $worker = tenant_find('workers', $orgId, $id);
                if (!$worker) {
                    json_response(['success' => false, 'message' => 'Worker not found'], 404);
                }
                json_response(['success' => true, 'data' => $worker]);
            }
            $workers = tenant_all('workers', $orgId, 'ORDER BY created_at DESC');
            json_response(['success' => true, 'data' => $workers]);
            break;

        case 'POST':
            $input = api_body();
            api_verify_csrf($input);

            $errors = validate($input, [
                'name' => 'required|max:255',
                'email' => 'email',
            ]);
            if ($errors) {
                json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
            }

            $data = [
                'employee_id' => generate_employee_id(),
                'name' => clean_string($input['name']),
                'phone' => clean_string($input['phone'] ?? ''),
                'email' => clean_string($input['email'] ?? ''),
                'role' => clean_string($input['role'] ?? ''),
                'department' => clean_string($input['department'] ?? ''),
                'hire_date' => $input['hire_date'] ?? null,
                'hourly_rate' => clean_float($input['hourly_rate'] ?? null),
                'created_by' => $currentUser['id'],
            ];

            $newId = tenant_insert('workers', $orgId, $data);
            audit_log($orgId, $currentUser['id'], 'create', 'workers', $newId, null, $data);
            json_response(['success' => true, 'data' => tenant_find('workers', $orgId, $newId)], 201);
            break;

        case 'PUT':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('workers', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Worker not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            $allowed = [
                'name', 'phone', 'email', 'role', 'department', 'hire_date', 'hourly_rate',
                'emergency_contact', 'status',
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

            tenant_update('workers', $orgId, $id, $data);
            audit_log($orgId, $currentUser['id'], 'update', 'workers', $id, $existing, $data);
            json_response(['success' => true, 'data' => tenant_find('workers', $orgId, $id)]);
            break;

        case 'DELETE':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('workers', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Worker not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            tenant_delete('workers', $orgId, $id);
            audit_log($orgId, $currentUser['id'], 'delete', 'workers', $id, $existing, null);
            json_response(['success' => true, 'data' => ['id' => $id]]);
            break;

        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

<?php
/**
 * CRUD API for farms.
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
                $farm = tenant_find('farms', $orgId, $id);
                if (!$farm) {
                    json_response(['success' => false, 'message' => 'Farm not found'], 404);
                }
                json_response(['success' => true, 'data' => $farm]);
            }
            $farms = tenant_all('farms', $orgId, 'ORDER BY created_at DESC');
            json_response(['success' => true, 'data' => $farms]);
            break;

        case 'POST':
            $input = api_body();
            api_verify_csrf($input);

            $errors = validate($input, ['name' => 'required|max:255']);
            if ($errors) {
                json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
            }

            $data = [
                'name' => clean_string($input['name']),
                'size' => clean_float($input['size'] ?? null),
                'gps_latitude' => clean_float($input['gps_latitude'] ?? null),
                'gps_longitude' => clean_float($input['gps_longitude'] ?? null),
                'district' => clean_string($input['district'] ?? ''),
                'village' => clean_string($input['village'] ?? ''),
                'description' => clean_string($input['description'] ?? ''),
                'created_by' => $currentUser['id'],
            ];

            $newId = tenant_insert('farms', $orgId, $data);
            audit_log($orgId, $currentUser['id'], 'create', 'farms', $newId, null, $data);
            json_response(['success' => true, 'data' => tenant_find('farms', $orgId, $newId)], 201);
            break;

        case 'PUT':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('farms', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Farm not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            $allowed = ['name', 'size', 'gps_latitude', 'gps_longitude', 'district', 'village', 'description', 'status'];
            $data = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $input)) {
                    $data[$field] = $input[$field];
                }
            }
            if (!$data) {
                json_response(['success' => false, 'message' => 'No updatable fields provided'], 400);
            }

            tenant_update('farms', $orgId, $id, $data);
            audit_log($orgId, $currentUser['id'], 'update', 'farms', $id, $existing, $data);
            json_response(['success' => true, 'data' => tenant_find('farms', $orgId, $id)]);
            break;

        case 'DELETE':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('farms', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Farm not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            tenant_delete('farms', $orgId, $id);
            audit_log($orgId, $currentUser['id'], 'delete', 'farms', $id, $existing, null);
            json_response(['success' => true, 'data' => ['id' => $id]]);
            break;

        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

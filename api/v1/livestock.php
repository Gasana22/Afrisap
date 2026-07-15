<?php
/**
 * CRUD API for animals (livestock).
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
                $animal = tenant_find('animals', $orgId, $id);
                if (!$animal) {
                    json_response(['success' => false, 'message' => 'Animal not found'], 404);
                }
                json_response(['success' => true, 'data' => $animal]);
            }
            $animals = tenant_all('animals', $orgId, 'ORDER BY created_at DESC');
            json_response(['success' => true, 'data' => $animals]);
            break;

        case 'POST':
            $input = api_body();
            api_verify_csrf($input);

            $errors = validate($input, [
                'farm_id' => 'required|numeric',
                'species' => 'required|max:100',
                'gender' => 'required|in:male,female',
            ]);
            if ($errors) {
                json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
            }

            $farmId = (int) $input['farm_id'];
            if (!tenant_find('farms', $orgId, $farmId)) {
                json_response(['success' => false, 'message' => 'Invalid farm_id for this organization'], 422);
            }

            $animalId = function_exists('generate_animal_id')
                ? generate_animal_id()
                : 'AN-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

            $data = [
                'farm_id' => $farmId,
                'animal_id' => $animalId,
                'tag_number' => clean_string($input['tag_number'] ?? ''),
                'name' => clean_string($input['name'] ?? ''),
                'species' => clean_string($input['species']),
                'breed' => clean_string($input['breed'] ?? ''),
                'gender' => $input['gender'],
                'birth_date' => $input['birth_date'] ?? null,
                'status' => $input['status'] ?? 'active',
                'purchase_price' => clean_float($input['purchase_price'] ?? null),
                'created_by' => $currentUser['id'],
            ];

            $newId = tenant_insert('animals', $orgId, $data);
            audit_log($orgId, $currentUser['id'], 'create', 'animals', $newId, null, $data);
            json_response(['success' => true, 'data' => tenant_find('animals', $orgId, $newId)], 201);
            break;

        case 'PUT':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('animals', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Animal not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            if (isset($input['farm_id']) && !tenant_find('farms', $orgId, (int) $input['farm_id'])) {
                json_response(['success' => false, 'message' => 'Invalid farm_id for this organization'], 422);
            }

            $allowed = [
                'farm_id', 'tag_number', 'name', 'species', 'breed', 'gender', 'birth_date',
                'status', 'purchase_price', 'sale_price',
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

            tenant_update('animals', $orgId, $id, $data);
            audit_log($orgId, $currentUser['id'], 'update', 'animals', $id, $existing, $data);
            json_response(['success' => true, 'data' => tenant_find('animals', $orgId, $id)]);
            break;

        case 'DELETE':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('animals', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Animal not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            tenant_delete('animals', $orgId, $id);
            audit_log($orgId, $currentUser['id'], 'delete', 'animals', $id, $existing, null);
            json_response(['success' => true, 'data' => ['id' => $id]]);
            break;

        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

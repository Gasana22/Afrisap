<?php
/**
 * CRUD API for crop_cycles.
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

function api_plot_belongs_to_org(int $plotId, int $orgId): bool
{
    $row = db_one(
        'SELECT p.id FROM plots p JOIN blocks b ON b.id = p.block_id JOIN farms f ON f.id = b.farm_id
         WHERE p.id = :plot_id AND f.organization_id = :org_id',
        ['plot_id' => $plotId, 'org_id' => $orgId]
    );

    return (bool) $row;
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $id = clean_int($_GET['id'] ?? null);
            if ($id) {
                $crop = tenant_find('crop_cycles', $orgId, $id);
                if (!$crop) {
                    json_response(['success' => false, 'message' => 'Crop cycle not found'], 404);
                }
                json_response(['success' => true, 'data' => $crop]);
            }
            $crops = tenant_all('crop_cycles', $orgId, 'ORDER BY created_at DESC');
            json_response(['success' => true, 'data' => $crops]);
            break;

        case 'POST':
            $input = api_body();
            api_verify_csrf($input);

            $errors = validate($input, [
                'crop_type' => 'required|max:100',
                'farm_id' => 'required|numeric',
                'plot_id' => 'required|numeric',
            ]);
            if ($errors) {
                json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
            }

            $farmId = (int) $input['farm_id'];
            $plotId = (int) $input['plot_id'];

            if (!tenant_find('farms', $orgId, $farmId)) {
                json_response(['success' => false, 'message' => 'Invalid farm_id for this organization'], 422);
            }
            if (!api_plot_belongs_to_org($plotId, $orgId)) {
                json_response(['success' => false, 'message' => 'Invalid plot_id for this organization'], 422);
            }

            $data = [
                'farm_id' => $farmId,
                'plot_id' => $plotId,
                'crop_type' => clean_string($input['crop_type']),
                'variety' => clean_string($input['variety'] ?? ''),
                'season' => clean_string($input['season'] ?? ''),
                'crop_batch_id' => generate_batch_id('CROP'),
                'start_date' => $input['start_date'] ?? null,
                'budget' => clean_float($input['budget'] ?? null),
                'expected_yield' => clean_float($input['expected_yield'] ?? null),
                'created_by' => $currentUser['id'],
            ];

            $newId = tenant_insert('crop_cycles', $orgId, $data);
            audit_log($orgId, $currentUser['id'], 'create', 'crop_cycles', $newId, null, $data);
            json_response(['success' => true, 'data' => tenant_find('crop_cycles', $orgId, $newId)], 201);
            break;

        case 'PUT':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('crop_cycles', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Crop cycle not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            if (isset($input['farm_id']) && !tenant_find('farms', $orgId, (int) $input['farm_id'])) {
                json_response(['success' => false, 'message' => 'Invalid farm_id for this organization'], 422);
            }
            if (isset($input['plot_id']) && !api_plot_belongs_to_org((int) $input['plot_id'], $orgId)) {
                json_response(['success' => false, 'message' => 'Invalid plot_id for this organization'], 422);
            }

            $allowed = [
                'farm_id', 'plot_id', 'crop_type', 'variety', 'season', 'start_date', 'end_date',
                'status', 'budget', 'expected_yield', 'actual_yield',
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

            tenant_update('crop_cycles', $orgId, $id, $data);
            audit_log($orgId, $currentUser['id'], 'update', 'crop_cycles', $id, $existing, $data);
            json_response(['success' => true, 'data' => tenant_find('crop_cycles', $orgId, $id)]);
            break;

        case 'DELETE':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('crop_cycles', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Crop cycle not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            tenant_delete('crop_cycles', $orgId, $id);
            audit_log($orgId, $currentUser['id'], 'delete', 'crop_cycles', $id, $existing, null);
            json_response(['success' => true, 'data' => ['id' => $id]]);
            break;

        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

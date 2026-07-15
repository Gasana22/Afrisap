<?php
/**
 * Read/create API for trace_batches.
 * GET (list, or ?batch_id=TRC-... for one full batch with nested
 * trace_events + product_journey), POST (create a trace_batch).
 * QR generation is intentionally kept OUT of this file - that is the
 * traceability UI flow using includes/qrcode.php. This endpoint only
 * reports whether a QR already exists for a batch.
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

function api_batch_with_details(array $batch): array
{
    $batch['trace_events'] = db_all(
        'SELECT * FROM trace_events WHERE batch_id = :batch_id ORDER BY event_date ASC',
        ['batch_id' => $batch['id']]
    );
    $batch['product_journey'] = db_all(
        'SELECT * FROM product_journey WHERE batch_id = :batch_id ORDER BY stage_order ASC',
        ['batch_id' => $batch['id']]
    );
    $batch['qr_exists'] = (bool) db_value(
        'SELECT COUNT(*) FROM trace_qr_codes WHERE batch_id = :batch_id',
        ['batch_id' => $batch['id']]
    );

    return $batch;
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $batchCode = $_GET['batch_id'] ?? null;
            $id = clean_int($_GET['id'] ?? null);

            if ($batchCode) {
                $batch = db_one(
                    'SELECT * FROM trace_batches WHERE organization_id = :org_id AND batch_id = :batch_id',
                    ['org_id' => $orgId, 'batch_id' => $batchCode]
                );
                if (!$batch) {
                    json_response(['success' => false, 'message' => 'Trace batch not found'], 404);
                }
                json_response(['success' => true, 'data' => api_batch_with_details($batch)]);
            }

            if ($id) {
                $batch = tenant_find('trace_batches', $orgId, $id);
                if (!$batch) {
                    json_response(['success' => false, 'message' => 'Trace batch not found'], 404);
                }
                json_response(['success' => true, 'data' => api_batch_with_details($batch)]);
            }

            $batches = tenant_all('trace_batches', $orgId, 'ORDER BY created_at DESC');
            json_response(['success' => true, 'data' => $batches]);
            break;

        case 'POST':
            $input = api_body();
            api_verify_csrf($input);

            $errors = validate($input, ['product_type' => 'required|max:100']);
            if ($errors) {
                json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
            }

            if (!empty($input['crop_cycle_id']) && !tenant_find('crop_cycles', $orgId, (int) $input['crop_cycle_id'])) {
                json_response(['success' => false, 'message' => 'Invalid crop_cycle_id for this organization'], 422);
            }
            if (!empty($input['farm_id']) && !tenant_find('farms', $orgId, (int) $input['farm_id'])) {
                json_response(['success' => false, 'message' => 'Invalid farm_id for this organization'], 422);
            }

            $data = [
                'batch_id' => generate_batch_id('TRC'),
                'product_type' => clean_string($input['product_type']),
                'crop_cycle_id' => clean_int($input['crop_cycle_id'] ?? null),
                'farm_id' => clean_int($input['farm_id'] ?? null),
                'block_id' => clean_int($input['block_id'] ?? null),
                'plot_id' => clean_int($input['plot_id'] ?? null),
                'production_date' => $input['production_date'] ?? null,
                'quantity' => clean_float($input['quantity'] ?? null),
                'unit' => clean_string($input['unit'] ?? ''),
                'current_location' => clean_string($input['current_location'] ?? ''),
            ];

            $newId = tenant_insert('trace_batches', $orgId, $data);
            audit_log($orgId, $currentUser['id'], 'create', 'trace_batches', $newId, null, $data);
            json_response(['success' => true, 'data' => api_batch_with_details(tenant_find('trace_batches', $orgId, $newId))], 201);
            break;

        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

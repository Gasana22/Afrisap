<?php
/**
 * CRUD API for inventory_items, plus stock movement actions.
 * GET (list / ?id=), POST (create, or ?action=stock_in|stock_out),
 * PUT ?id= (update), DELETE ?id=.
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
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? null;

    if ($method === 'POST' && in_array($action, ['stock_in', 'stock_out'], true)) {
        $input = api_body();
        api_verify_csrf($input);

        $errors = validate($input, [
            'item_id' => 'required|numeric',
            'quantity' => 'required|numeric',
        ]);
        if ($errors) {
            json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
        }

        $item = tenant_find('inventory_items', $orgId, (int) $input['item_id']);
        if (!$item) {
            json_response(['success' => false, 'message' => 'Inventory item not found'], 404);
        }

        $qty = (float) $input['quantity'];
        if ($qty <= 0) {
            json_response(['success' => false, 'message' => 'Quantity must be greater than zero'], 422);
        }

        if ($action === 'stock_out' && $qty > (float) $item['quantity']) {
            json_response(['success' => false, 'message' => 'Not enough stock available. Current stock is ' . number_format((float) $item['quantity'], 2) . ' ' . ($item['unit'] ?: '')], 422);
        }

        $unitCost = isset($input['unit_cost']) && $input['unit_cost'] !== '' ? (float) $input['unit_cost'] : null;
        $totalCost = ($action === 'stock_in' && $unitCost !== null) ? $qty * $unitCost : null;
        $notes = clean_string($input['notes'] ?? '');

        $txId = db_insert('inventory_transactions', [
            'item_id' => $item['id'],
            'type' => $action,
            'quantity' => $qty,
            'unit_cost' => $action === 'stock_in' ? $unitCost : null,
            'total_cost' => $totalCost,
            'reference_type' => 'api',
            'reference_id' => null,
            'notes' => $notes ?: null,
            'created_by' => $currentUser['id'],
        ]);

        $newQuantity = $action === 'stock_in'
            ? (float) $item['quantity'] + $qty
            : (float) $item['quantity'] - $qty;

        tenant_update('inventory_items', $orgId, $item['id'], ['quantity' => $newQuantity]);
        audit_log($orgId, $currentUser['id'], 'update', 'inventory_items', $item['id'], ['quantity' => $item['quantity']], ['quantity' => $newQuantity, $action => $qty]);

        json_response([
            'success' => true,
            'data' => [
                'transaction_id' => $txId,
                'item' => tenant_find('inventory_items', $orgId, $item['id']),
            ],
        ], 201);
    }

    switch ($method) {
        case 'GET':
            $id = clean_int($_GET['id'] ?? null);
            if ($id) {
                $item = tenant_find('inventory_items', $orgId, $id);
                if (!$item) {
                    json_response(['success' => false, 'message' => 'Inventory item not found'], 404);
                }
                json_response(['success' => true, 'data' => $item]);
            }
            $items = tenant_all('inventory_items', $orgId, 'ORDER BY name');
            json_response(['success' => true, 'data' => $items]);
            break;

        case 'POST':
            $input = api_body();
            api_verify_csrf($input);

            $errors = validate($input, [
                'category' => 'required|max:100',
                'name' => 'required|max:255',
            ]);
            if ($errors) {
                json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
            }

            if (!empty($input['supplier_id']) && !tenant_find('suppliers', $orgId, (int) $input['supplier_id'])) {
                json_response(['success' => false, 'message' => 'Invalid supplier_id for this organization'], 422);
            }

            $data = [
                'category' => clean_string($input['category']),
                'name' => clean_string($input['name']),
                'sku' => clean_string($input['sku'] ?? ''),
                'unit' => clean_string($input['unit'] ?? ''),
                'quantity' => clean_float($input['quantity'] ?? 0) ?? 0,
                'reorder_level' => clean_float($input['reorder_level'] ?? null),
                'supplier_id' => clean_int($input['supplier_id'] ?? null),
                'purchase_price' => clean_float($input['purchase_price'] ?? null),
                'expiry_date' => $input['expiry_date'] ?? null,
                'storage_location' => clean_string($input['storage_location'] ?? ''),
                'created_by' => $currentUser['id'],
            ];

            $newId = tenant_insert('inventory_items', $orgId, $data);
            audit_log($orgId, $currentUser['id'], 'create', 'inventory_items', $newId, null, $data);
            json_response(['success' => true, 'data' => tenant_find('inventory_items', $orgId, $newId)], 201);
            break;

        case 'PUT':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('inventory_items', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Inventory item not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            if (isset($input['supplier_id']) && $input['supplier_id'] && !tenant_find('suppliers', $orgId, (int) $input['supplier_id'])) {
                json_response(['success' => false, 'message' => 'Invalid supplier_id for this organization'], 422);
            }

            $allowed = [
                'category', 'name', 'sku', 'unit', 'quantity', 'reorder_level', 'supplier_id',
                'purchase_price', 'expiry_date', 'storage_location', 'status',
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

            tenant_update('inventory_items', $orgId, $id, $data);
            audit_log($orgId, $currentUser['id'], 'update', 'inventory_items', $id, $existing, $data);
            json_response(['success' => true, 'data' => tenant_find('inventory_items', $orgId, $id)]);
            break;

        case 'DELETE':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('inventory_items', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Inventory item not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            tenant_delete('inventory_items', $orgId, $id);
            audit_log($orgId, $currentUser['id'], 'delete', 'inventory_items', $id, $existing, null);
            json_response(['success' => true, 'data' => ['id' => $id]]);
            break;

        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

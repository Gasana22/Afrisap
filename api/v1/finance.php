<?php
/**
 * CRUD API for financial_transactions.
 * GET (list, filterable by ?type=income|expense and ?from=&to= date range, or ?id=),
 * POST (create), PUT ?id= (update), DELETE ?id=.
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
                $tx = tenant_find('financial_transactions', $orgId, $id);
                if (!$tx) {
                    json_response(['success' => false, 'message' => 'Transaction not found'], 404);
                }
                json_response(['success' => true, 'data' => $tx]);
            }

            $extraSql = '';
            $params = [];
            if (!empty($_GET['type']) && in_array($_GET['type'], ['income', 'expense'], true)) {
                $extraSql .= ' AND type = :type';
                $params['type'] = $_GET['type'];
            }
            if (!empty($_GET['from'])) {
                $extraSql .= ' AND transaction_date >= :from_date';
                $params['from_date'] = $_GET['from'];
            }
            if (!empty($_GET['to'])) {
                $extraSql .= ' AND transaction_date <= :to_date';
                $params['to_date'] = $_GET['to'];
            }
            $extraSql .= ' ORDER BY transaction_date DESC, created_at DESC';

            $transactions = tenant_all('financial_transactions', $orgId, $extraSql, $params);
            json_response(['success' => true, 'data' => $transactions]);
            break;

        case 'POST':
            $input = api_body();
            api_verify_csrf($input);

            $errors = validate($input, [
                'type' => 'required|in:income,expense',
                'category' => 'required|max:100',
                'amount' => 'required|numeric',
                'transaction_date' => 'required|date',
            ]);
            if ($errors) {
                json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
            }

            $data = [
                'type' => $input['type'],
                'category' => clean_string($input['category']),
                'sub_category' => clean_string($input['sub_category'] ?? ''),
                'amount' => (float) $input['amount'],
                'description' => clean_string($input['description'] ?? ''),
                'transaction_date' => $input['transaction_date'],
                'created_by' => $currentUser['id'],
            ];

            $newId = tenant_insert('financial_transactions', $orgId, $data);
            audit_log($orgId, $currentUser['id'], 'create', 'financial_transactions', $newId, null, $data);
            json_response(['success' => true, 'data' => tenant_find('financial_transactions', $orgId, $newId)], 201);
            break;

        case 'PUT':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('financial_transactions', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Transaction not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            if (isset($input['type']) && !in_array($input['type'], ['income', 'expense'], true)) {
                json_response(['success' => false, 'message' => 'type must be income or expense'], 422);
            }

            $allowed = ['type', 'category', 'sub_category', 'amount', 'description', 'transaction_date'];
            $data = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $input)) {
                    $data[$field] = $input[$field];
                }
            }
            if (!$data) {
                json_response(['success' => false, 'message' => 'No updatable fields provided'], 400);
            }

            tenant_update('financial_transactions', $orgId, $id, $data);
            audit_log($orgId, $currentUser['id'], 'update', 'financial_transactions', $id, $existing, $data);
            json_response(['success' => true, 'data' => tenant_find('financial_transactions', $orgId, $id)]);
            break;

        case 'DELETE':
            $id = clean_int($_GET['id'] ?? null);
            if (!$id) {
                json_response(['success' => false, 'message' => 'Missing id parameter'], 400);
            }
            $existing = tenant_find('financial_transactions', $orgId, $id);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Transaction not found'], 404);
            }

            $input = api_body();
            api_verify_csrf($input);

            tenant_delete('financial_transactions', $orgId, $id);
            audit_log($orgId, $currentUser['id'], 'delete', 'financial_transactions', $id, $existing, null);
            json_response(['success' => true, 'data' => ['id' => $id]]);
            break;

        default:
            json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

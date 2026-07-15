<?php
/**
 * Generic authenticated file upload endpoint.
 * Accepts multipart/form-data POST with `file` and `category` fields.
 * NOTE: unlike the other api/v1/*.php endpoints, this one is NOT JSON-body -
 * file uploads use multipart/form-data, so the CSRF token arrives as a
 * normal $_POST field, not inside a JSON payload.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

// Support either an org-admin session or a worker session - both need to
// upload photos/receipts from their respective portals.
$currentUser = current_org_user();
$orgId = null;

if ($currentUser) {
    $organization = current_organization();
    $orgId = (int) $organization['id'];
} else {
    $worker = current_worker();
    if ($worker) {
        $orgId = (int) $worker['organization_id'];
    }
}

if ($orgId === null) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) ($_POST['csrf_token'] ?? ''))) {
        json_response(['success' => false, 'message' => 'Invalid or missing CSRF token'], 403);
    }

    if (empty($_FILES['file'])) {
        json_response(['success' => false, 'message' => 'No file was uploaded'], 400);
    }

    $allowedCategories = ['crops', 'livestock', 'receipts', 'workers', 'inventory', 'finance', 'traceability', 'tasks', 'general'];
    $category = clean_string($_POST['category'] ?? 'general');
    $category = preg_replace('/[^a-z0-9_-]/', '', strtolower($category)) ?: 'general';
    if (!in_array($category, $allowedCategories, true)) {
        $category = 'general';
    }

    $relativePath = handle_upload($_FILES['file'], $orgId, $category);
    if (!$relativePath) {
        json_response(['success' => false, 'message' => 'Upload failed - no file received'], 400);
    }

    json_response([
        'success' => true,
        'path' => $relativePath,
        'url' => uploaded_file_url($relativePath),
    ], 201);
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

<?php
/**
 * GET /api/v1/auth.php - "who am I" session check for the frontend JS.
 * Checks all three guard types (org user, platform admin, worker) and
 * reports whichever is currently active. No login here - that is
 * public/login.php's job.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    $orgUser = current_org_user();
    if ($orgUser) {
        json_response([
            'authenticated' => true,
            'guard' => 'org_user',
            'user' => [
                'id' => (int) $orgUser['id'],
                'name' => $orgUser['name'],
                'email' => $orgUser['email'],
                'role' => $orgUser['role'],
            ],
            'organization' => current_organization(),
        ]);
    }

    $admin = current_platform_admin();
    if ($admin) {
        json_response([
            'authenticated' => true,
            'guard' => 'platform_admin',
            'user' => [
                'id' => (int) $admin['id'],
                'name' => $admin['name'],
                'email' => $admin['email'],
            ],
            'organization' => null,
        ]);
    }

    $worker = current_worker();
    if ($worker) {
        json_response([
            'authenticated' => true,
            'guard' => 'worker',
            'user' => [
                'id' => (int) $worker['id'],
                'name' => $worker['name'],
                'email' => $worker['email'] ?? $worker['login_email'] ?? null,
                'role' => 'worker',
            ],
            'organization' => current_organization(),
        ]);
    }

    json_response(['authenticated' => false, 'user' => null, 'organization' => null]);
} catch (RuntimeException | PDOException $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 400);
}

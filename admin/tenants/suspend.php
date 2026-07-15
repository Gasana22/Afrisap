<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$admin = require_platform_admin();

if (!is_post() || !csrf_verify()) {
    redirect('admin/tenants/index.php');
}

$id = clean_int($_POST['id'] ?? 0);
$redirectTo = ($_POST['redirect'] ?? '') === 'view' ? 'admin/tenants/view.php?id=' . $id : 'admin/tenants/index.php';

$organization = db_one('SELECT * FROM organizations WHERE id = :id', ['id' => $id]);

if (!$organization) {
    session_flash('error', 'Organization not found.');
    redirect($redirectTo);
}

$newStatus = $organization['subscription_status'] === 'suspended' ? 'active' : 'suspended';
$action = $newStatus === 'suspended' ? 'suspend' : 'reactivate';

db_update('organizations', ['subscription_status' => $newStatus], 'id = :id', ['id' => $id]);
audit_log($id, $admin['id'], $action, 'organizations', $id, ['subscription_status' => $organization['subscription_status']], ['subscription_status' => $newStatus]);

session_flash('success', 'Organization "' . $organization['name'] . '" has been ' . ($newStatus === 'suspended' ? 'suspended' : 'reactivated') . '.');
redirect($redirectTo);

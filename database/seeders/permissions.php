<?php
/**
 * Seeds the master list of permission keys the platform recognizes, stored
 * under system_settings (group "permissions") so admin/users/roles.php can
 * render a checklist instead of hard-coding it in a view.
 */

require_once __DIR__ . '/../../config/database.php';

function master_permission_list(): array
{
    return [
        'farms.view', 'farms.create', 'farms.edit', 'farms.delete',
        'crops.view', 'crops.create', 'crops.edit', 'crops.delete',
        'livestock.view', 'livestock.create', 'livestock.edit', 'livestock.delete',
        'workers.view', 'workers.create', 'workers.edit', 'workers.delete',
        'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete',
        'procurement.view', 'procurement.create', 'procurement.edit',
        'finance.view', 'finance.create', 'finance.edit',
        'traceability.view', 'traceability.create',
        'reporting.view', 'reporting.export',
        'settings.manage',
        'tasks.view', 'tasks.create', 'tasks.assign',
        'attendance.self', 'attendance.view_all',
        'activities.self',
    ];
}

function seed_permissions(): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO system_settings (setting_key, setting_value, setting_group)
         VALUES ("permissions.master_list", :value, "permissions")
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute(['value' => json_encode(master_permission_list())]);

    echo "Seeded permissions\n";
}

if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    seed_permissions();
}

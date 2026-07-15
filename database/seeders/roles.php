<?php
/**
 * Seeds the default permission matrix per organization role into
 * system_settings (group "roles"), used as the starting point when an
 * organization customizes roles under org-admin/settings/roles.php.
 */

require_once __DIR__ . '/../../config/database.php';

function default_role_permissions(): array
{
    return [
        'owner' => ['*'],
        'manager' => ['farms.*', 'crops.*', 'livestock.*', 'workers.*', 'inventory.*', 'procurement.*', 'reporting.view'],
        'agronomist' => ['farms.view', 'crops.*', 'reporting.view'],
        'livestock_manager' => ['farms.view', 'livestock.*', 'reporting.view'],
        'store_manager' => ['inventory.*', 'procurement.*', 'reporting.view'],
        'accountant' => ['finance.*', 'reporting.view', 'reporting.export'],
        'viewer' => ['farms.view', 'crops.view', 'livestock.view', 'reporting.view'],
        'worker' => ['tasks.view', 'attendance.self', 'activities.self'],
    ];
}

function seed_roles(): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO system_settings (setting_key, setting_value, setting_group)
         VALUES (:key, :value, "roles")
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );

    foreach (default_role_permissions() as $role => $permissions) {
        $stmt->execute([
            'key' => "role_permissions.$role",
            'value' => json_encode($permissions),
        ]);
    }

    echo "Seeded roles\n";
}

if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    seed_roles();
}

<?php
/**
 * One-step database setup: runs every migration then every seeder,
 * so you don't need to run migrate.php and seed.php separately.
 *
 * Usage: php database/setup.php
 */

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

$files = glob(__DIR__ . '/migrations/*.php');
sort($files);

foreach ($files as $file) {
    $statements = require $file;

    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }

    echo 'Migrated: ' . basename($file) . "\n";
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

require_once __DIR__ . '/seeders/roles.php';
require_once __DIR__ . '/seeders/permissions.php';
require_once __DIR__ . '/seeders/subscription_plans.php';
require_once __DIR__ . '/seeders/demo_tenant.php';

seed_roles();
seed_permissions();
seed_subscription_plans();
seed_demo_tenant();

echo "\nDatabase setup complete - tables created and demo data seeded.\n";
echo "Log in at public/login.php with:\n";
echo "  Platform admin : admin@sfmtp.local / Password123!\n";
echo "  Farm owner     : owner@greenvalley.test / Password123!\n";
echo "  Worker portal  : worker@greenvalley.test / Password123! (via worker/login.php)\n";

<?php
/**
 * CLI seed runner. Usage: php database/seed.php
 */

require_once __DIR__ . '/seeders/roles.php';
require_once __DIR__ . '/seeders/permissions.php';
require_once __DIR__ . '/seeders/subscription_plans.php';
require_once __DIR__ . '/seeders/demo_tenant.php';

seed_roles();
seed_permissions();
seed_subscription_plans();
seed_demo_tenant();

echo "All seeders complete.\n";

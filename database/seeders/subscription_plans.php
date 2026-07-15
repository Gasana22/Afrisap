<?php
/**
 * Seeds the four subscription plans referenced throughout the platform.
 */

require_once __DIR__ . '/../../config/database.php';

function seed_subscription_plans(): void
{
    $pdo = db();

    $plans = [
        ['name' => 'Free', 'slug' => 'free', 'price' => 0, 'max_farms' => 1, 'max_users' => 3, 'max_storage_mb' => 500,
            'features' => ['crop_management', 'basic_reporting']],
        ['name' => 'Basic', 'slug' => 'basic', 'price' => 29, 'max_farms' => 5, 'max_users' => 10, 'max_storage_mb' => 2000,
            'features' => ['crop_management', 'livestock_management', 'inventory', 'basic_reporting']],
        ['name' => 'Professional', 'slug' => 'professional', 'price' => 99, 'max_farms' => 20, 'max_users' => 50, 'max_storage_mb' => 10000,
            'features' => ['crop_management', 'livestock_management', 'inventory', 'finance', 'traceability', 'advanced_reporting']],
        ['name' => 'Enterprise', 'slug' => 'enterprise', 'price' => 299, 'max_farms' => 999, 'max_users' => 999, 'max_storage_mb' => 100000,
            'features' => ['crop_management', 'livestock_management', 'inventory', 'finance', 'traceability', 'advanced_reporting', 'api_access', 'priority_support']],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO subscription_plans (name, slug, price, currency, billing_period, max_farms, max_users, max_storage_mb, features, is_active)
         VALUES (:name, :slug, :price, "USD", "monthly", :max_farms, :max_users, :max_storage_mb, :features, 1)
         ON DUPLICATE KEY UPDATE price = VALUES(price), max_farms = VALUES(max_farms), max_users = VALUES(max_users)'
    );

    foreach ($plans as $plan) {
        $stmt->execute([
            'name' => $plan['name'],
            'slug' => $plan['slug'],
            'price' => $plan['price'],
            'max_farms' => $plan['max_farms'],
            'max_users' => $plan['max_users'],
            'max_storage_mb' => $plan['max_storage_mb'],
            'features' => json_encode($plan['features']),
        ]);
    }

    echo "Seeded subscription_plans\n";
}

if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    seed_subscription_plans();
}

<?php
/**
 * Application-wide constants.
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOADS_PATH', STORAGE_PATH . '/uploads');
define('EXPORTS_PATH', STORAGE_PATH . '/exports');
define('CACHE_PATH', STORAGE_PATH . '/cache');
define('LOGS_PATH', ROOT_PATH . '/logs');

define('ROLE_OWNER', 'owner');
define('ROLE_MANAGER', 'manager');
define('ROLE_AGRONOMIST', 'agronomist');
define('ROLE_LIVESTOCK_MANAGER', 'livestock_manager');
define('ROLE_STORE_MANAGER', 'store_manager');
define('ROLE_ACCOUNTANT', 'accountant');
define('ROLE_VIEWER', 'viewer');
define('ROLE_WORKER', 'worker');

// Roles allowed to sign in through org-admin/ (everything except plain field workers)
define('ORG_ADMIN_ROLES', [
    ROLE_OWNER, ROLE_MANAGER, ROLE_AGRONOMIST, ROLE_LIVESTOCK_MANAGER,
    ROLE_STORE_MANAGER, ROLE_ACCOUNTANT, ROLE_VIEWER,
]);

define('SUBSCRIPTION_PLAN_LIMITS', [
    'free' => ['max_farms' => (int) env('PLAN_FREE_MAX_FARMS', 1), 'max_users' => (int) env('PLAN_FREE_MAX_USERS', 3)],
    'basic' => ['max_farms' => (int) env('PLAN_BASIC_MAX_FARMS', 5), 'max_users' => (int) env('PLAN_BASIC_MAX_USERS', 10)],
    'professional' => ['max_farms' => (int) env('PLAN_PROFESSIONAL_MAX_FARMS', 20), 'max_users' => (int) env('PLAN_PROFESSIONAL_MAX_USERS', 50)],
    'enterprise' => ['max_farms' => (int) env('PLAN_ENTERPRISE_MAX_FARMS', 999), 'max_users' => (int) env('PLAN_ENTERPRISE_MAX_USERS', 999)],
]);

define('TASK_STATUSES', ['pending', 'in_progress', 'completed', 'verified', 'canceled']);
define('CROP_CYCLE_STATUSES', ['planning', 'procurement', 'nursery', 'field', 'monitoring', 'harvest', 'sales', 'completed']);

// Suggested canonical vocabulary for product_journey.stage, used to render a
// horizontal progress bar on the public traceability pages. Organizations
// are free to log any stage name they like (product_journey.stage is plain
// text) - stages that don't match this list simply aren't marked "done" in
// the progress bar, but still appear in the detailed journey list beneath it.
define('TRACE_JOURNEY_STAGES', [
    'seed', 'nursery', 'field', 'harvest', 'storage',
    'processing', 'packaging', 'distribution', 'sold',
]);

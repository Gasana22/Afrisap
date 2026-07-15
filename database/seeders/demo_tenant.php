<?php
/**
 * Seeds a demo tenant with enough sample data to explore every module:
 * a platform super-admin, one organization with an owner login, a farm
 * with a block/plot, a crop cycle, an animal, two workers, some tasks,
 * inventory, a supplier, financial transactions and a traceability batch.
 *
 * Login credentials (all use the password below):
 *   Platform admin : admin@sfmtp.local
 *   Farm owner     : owner@greenvalley.test
 *   Worker         : worker@greenvalley.test  (worker/login.php)
 * Password for all: Password123!
 */

require_once __DIR__ . '/../../config/database.php';

function seed_demo_tenant(): void
{
    $pdo = db();
    $password = password_hash('Password123!', PASSWORD_BCRYPT);

    // --- Platform super admin ---
    $pdo->prepare(
        'INSERT INTO users (email, password_hash, name, is_admin, is_super_admin, status)
         VALUES (:email, :password, "Platform Admin", 1, 1, "active")
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)'
    )->execute(['email' => 'admin@sfmtp.local', 'password' => $password]);
    $adminId = (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@sfmtp.local'")->fetchColumn();

    // --- Demo organization ---
    $pdo->prepare(
        'INSERT INTO organizations (name, slug, email, subscription_plan, subscription_status, trial_ends_at)
         VALUES ("Green Valley Farms", "green-valley-farms", "owner@greenvalley.test", "professional", "active", DATE_ADD(NOW(), INTERVAL 30 DAY))
         ON DUPLICATE KEY UPDATE name = VALUES(name)'
    )->execute();
    $orgId = (int) $pdo->query("SELECT id FROM organizations WHERE slug = 'green-valley-farms'")->fetchColumn();

    // --- Owner user + membership ---
    $pdo->prepare(
        'INSERT INTO users (email, password_hash, name, phone, is_admin, status)
         VALUES (:email, :password, "Amara Owner", "+250700000001", 0, "active")
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)'
    )->execute(['email' => 'owner@greenvalley.test', 'password' => $password]);
    $ownerId = (int) $pdo->query("SELECT id FROM users WHERE email = 'owner@greenvalley.test'")->fetchColumn();

    $pdo->prepare(
        'INSERT INTO organization_users (organization_id, user_id, role, status)
         VALUES (:org_id, :user_id, "owner", "active")
         ON DUPLICATE KEY UPDATE role = VALUES(role)'
    )->execute(['org_id' => $orgId, 'user_id' => $ownerId]);

    // --- Farm / block / plot ---
    $pdo->prepare(
        'INSERT INTO farms (organization_id, name, size, gps_latitude, gps_longitude, district, village, status, created_by)
         VALUES (:org_id, "Green Valley Main Farm", 1200, -1.9441, 30.0619, "Kigali", "Kacyiru", "active", :created_by)
         ON DUPLICATE KEY UPDATE name = VALUES(name)'
    )->execute(['org_id' => $orgId, 'created_by' => $ownerId]);
    $farmId = (int) $pdo->query("SELECT id FROM farms WHERE organization_id = $orgId ORDER BY id LIMIT 1")->fetchColumn();

    $pdo->prepare('INSERT INTO blocks (farm_id, name, area) VALUES (:farm_id, "Block A", 300)')->execute(['farm_id' => $farmId]);
    $blockId = (int) $pdo->query("SELECT id FROM blocks WHERE farm_id = $farmId ORDER BY id LIMIT 1")->fetchColumn();

    $pdo->prepare('INSERT INTO plots (block_id, name, size, soil_type) VALUES (:block_id, "Plot A1", 50, "Loam")')->execute(['block_id' => $blockId]);
    $plotId = (int) $pdo->query("SELECT id FROM plots WHERE block_id = $blockId ORDER BY id LIMIT 1")->fetchColumn();

    // --- Workers (one of which also gets a worker-portal login) ---
    $pdo->prepare(
        'INSERT INTO users (email, password_hash, name, phone, status) VALUES (:email, :password, "Jane Smith", "+250700000002", "active")
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)'
    )->execute(['email' => 'worker@greenvalley.test', 'password' => $password]);
    $workerUserId = (int) $pdo->query("SELECT id FROM users WHERE email = 'worker@greenvalley.test'")->fetchColumn();

    $pdo->prepare(
        'INSERT INTO organization_users (organization_id, user_id, role, status)
         VALUES (:org_id, :user_id, "worker", "active") ON DUPLICATE KEY UPDATE role = VALUES(role)'
    )->execute(['org_id' => $orgId, 'user_id' => $workerUserId]);

    $pdo->prepare(
        'INSERT INTO workers (organization_id, user_id, employee_id, name, phone, email, role, department, hire_date, hourly_rate, status, created_by)
         VALUES (:org_id, :user_id, "EMP-000001", "Jane Smith", "+250700000002", "worker@greenvalley.test", "Field Worker", "Crop Operations", CURDATE(), 3.5, "active", :created_by)
         ON DUPLICATE KEY UPDATE name = VALUES(name)'
    )->execute(['org_id' => $orgId, 'user_id' => $workerUserId, 'created_by' => $ownerId]);
    $workerId = (int) $pdo->query("SELECT id FROM workers WHERE organization_id = $orgId AND employee_id = 'EMP-000001'")->fetchColumn();

    $pdo->prepare(
        'INSERT INTO workers (organization_id, employee_id, name, phone, role, department, hire_date, hourly_rate, status, created_by)
         VALUES (:org_id, "EMP-000002", "Tom Richards", "+250700000003", "Field Worker", "Livestock", CURDATE(), 3.0, "active", :created_by)
         ON DUPLICATE KEY UPDATE name = VALUES(name)'
    )->execute(['org_id' => $orgId, 'created_by' => $ownerId]);
    $worker2Id = (int) $pdo->query("SELECT id FROM workers WHERE organization_id = $orgId AND employee_id = 'EMP-000002'")->fetchColumn();

    // --- Crop cycle + operations ---
    $pdo->prepare(
        "INSERT INTO crop_cycles (organization_id, farm_id, plot_id, crop_type, variety, season, crop_batch_id, start_date, status, budget, expected_yield, actual_yield, created_by)
         VALUES (:org_id, :farm_id, :plot_id, 'Maize', 'H614', '2026A', 'CROP-20260301-DEMO01', '2026-03-01', 'field', 5000, 2500, NULL, :created_by)
         ON DUPLICATE KEY UPDATE crop_type = VALUES(crop_type)"
    )->execute(['org_id' => $orgId, 'farm_id' => $farmId, 'plot_id' => $plotId, 'created_by' => $ownerId]);
    $cropId = (int) $pdo->query("SELECT id FROM crop_cycles WHERE crop_batch_id = 'CROP-20260301-DEMO01'")->fetchColumn();

    $pdo->prepare(
        "INSERT INTO crop_operations (crop_cycle_id, activity_type, activity_date, description, cost, created_by)
         VALUES (:crop_id, 'planting', '2026-03-01', 'Planted maize seeds across Plot A1', 400, :created_by)"
    )->execute(['crop_id' => $cropId, 'created_by' => $ownerId]);

    // --- Livestock ---
    $pdo->prepare(
        "INSERT INTO animals (organization_id, farm_id, animal_id, tag_number, name, species, breed, gender, birth_date, status, purchase_price, created_by)
         VALUES (:org_id, :farm_id, 'AN-DEMO001', 'T-101', 'Bella', 'Cattle', 'Friesian', 'female', '2023-05-10', 'active', 800, :created_by)
         ON DUPLICATE KEY UPDATE name = VALUES(name)"
    )->execute(['org_id' => $orgId, 'farm_id' => $farmId, 'created_by' => $ownerId]);

    // --- Supplier + inventory ---
    $pdo->prepare(
        'INSERT INTO suppliers (organization_id, name, contact_person, phone, email, status, created_by)
         VALUES (:org_id, "AgroSupply Rwanda", "Eric Niyonzima", "+250780000000", "sales@agrosupply.test", "active", :created_by)'
    )->execute(['org_id' => $orgId, 'created_by' => $ownerId]);
    $supplierId = (int) $pdo->query("SELECT id FROM suppliers WHERE organization_id = $orgId ORDER BY id LIMIT 1")->fetchColumn();

    $pdo->prepare(
        'INSERT INTO inventory_items (organization_id, category, name, sku, unit, quantity, reorder_level, supplier_id, purchase_price, status, created_by)
         VALUES (:org_id, "Fertilizer", "NPK 17-17-17", "SKU-NPK-001", "kg", 40, 100, :supplier_id, 1.2, "active", :created_by)'
    )->execute(['org_id' => $orgId, 'supplier_id' => $supplierId, 'created_by' => $ownerId]);

    // --- Tasks ---
    $pdo->prepare(
        "INSERT INTO tasks (organization_id, assigned_to, assigned_by, farm_id, block_id, task_type, title, description, priority, deadline, status)
         VALUES (:org_id, :worker_id, :assigner_id, :farm_id, :block_id, 'fertilizing', 'Apply Fertilizer to Maize', 'Apply NPK fertilizer to Block A maize', 'high', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'in_progress')"
    )->execute(['org_id' => $orgId, 'worker_id' => $workerId, 'assigner_id' => $workerId, 'farm_id' => $farmId, 'block_id' => $blockId]);

    $pdo->prepare(
        "INSERT INTO tasks (organization_id, assigned_to, assigned_by, farm_id, task_type, title, description, priority, deadline, status)
         VALUES (:org_id, :worker2_id, :assigner_id, :farm_id, 'harvesting', 'Harvest Wheat', 'Harvest ready wheat plots', 'medium', DATE_ADD(CURDATE(), INTERVAL 8 DAY), 'pending')"
    )->execute(['org_id' => $orgId, 'worker2_id' => $worker2Id, 'assigner_id' => $workerId, 'farm_id' => $farmId]);

    // --- Finance ---
    $pdo->prepare(
        "INSERT INTO financial_transactions (organization_id, type, category, amount, description, transaction_date, created_by)
         VALUES (:org_id, 'expense', 'Inputs', 400, 'Seeds and fertilizer purchase', CURDATE(), :created_by)"
    )->execute(['org_id' => $orgId, 'created_by' => $ownerId]);

    $pdo->prepare(
        "INSERT INTO financial_transactions (organization_id, type, category, amount, description, transaction_date, created_by)
         VALUES (:org_id, 'income', 'Crop Sales', 1500, 'Sale of previous maize harvest', CURDATE(), :created_by)"
    )->execute(['org_id' => $orgId, 'created_by' => $ownerId]);

    // --- Traceability batch ---
    $pdo->prepare(
        "INSERT INTO trace_batches (organization_id, batch_id, product_type, crop_cycle_id, farm_id, block_id, plot_id, production_date, quantity, unit, current_location, status)
         VALUES (:org_id, 'TRC-DEMO0001', 'Maize', :crop_id, :farm_id, :block_id, :plot_id, '2026-03-01', 2500, 'kg', 'Green Valley Main Farm Store', 'active')
         ON DUPLICATE KEY UPDATE product_type = VALUES(product_type)"
    )->execute(['org_id' => $orgId, 'crop_id' => $cropId, 'farm_id' => $farmId, 'block_id' => $blockId, 'plot_id' => $plotId]);
    $batchId = (int) $pdo->query("SELECT id FROM trace_batches WHERE batch_id = 'TRC-DEMO0001'")->fetchColumn();

    $pdo->prepare(
        "INSERT INTO trace_events (batch_id, event_type, location, description) VALUES (:batch_id, 'planted', 'Plot A1', 'Maize seeds planted')"
    )->execute(['batch_id' => $batchId]);
    $pdo->prepare(
        "INSERT INTO trace_events (batch_id, event_type, location, description) VALUES (:batch_id, 'harvested', 'Plot A1', 'Maize harvested and moved to store')"
    )->execute(['batch_id' => $batchId]);

    $pdo->prepare(
        "INSERT INTO product_journey (batch_id, stage, stage_order, start_date, responsible_party)
         VALUES (:batch_id, 'Planting', 1, '2026-03-01', 'Green Valley Farms')"
    )->execute(['batch_id' => $batchId]);
    $pdo->prepare(
        "INSERT INTO product_journey (batch_id, stage, stage_order, start_date, responsible_party)
         VALUES (:batch_id, 'Storage', 2, CURDATE(), 'Green Valley Farms')"
    )->execute(['batch_id' => $batchId]);

    echo "Seeded demo tenant 'Green Valley Farms' (slug: green-valley-farms)\n";
    echo "  Platform admin : admin@sfmtp.local / Password123!\n";
    echo "  Farm owner     : owner@greenvalley.test / Password123!\n";
    echo "  Worker portal  : worker@greenvalley.test / Password123!\n";
}

if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    seed_demo_tenant();
}

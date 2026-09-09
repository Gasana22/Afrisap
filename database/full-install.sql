-- ==========================================================
-- Smart Farm Management & Traceability Platform (SFMTP)
-- ONE-FILE INSTALL: creates the database, every table, and the
-- demo tenant/login data in a single import.
--
-- How to use (phpMyAdmin): open phpMyAdmin, click "Import" in the top
-- menu (no need to create/select a database first), choose this file,
-- and click "Go". That's it - schema + demo data are both created.
--
-- How to use (command line): mysql -u root < database/full-install.sql
--
-- Demo logins created by this file (all use the same password):
--   Platform admin : admin@sfmtp.local       / Password123!
--   Farm owner     : owner@greenvalley.test  / Password123!  (org-admin)
--   Worker portal  : worker@greenvalley.test / Password123!  (worker/login.php)
--
-- Safe to re-run: DROPs and recreates the database from scratch each
-- time, so running it twice does not error out on duplicate rows.
-- ==========================================================

DROP DATABASE IF EXISTS farm;
CREATE DATABASE farm CHARACTER SET utf8mb4;
USE farm;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- SCHEMA (all 35 tables)
-- ==========================================

CREATE TABLE organizations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(50),
    logo VARCHAR(255),
    subscription_plan ENUM('free', 'basic', 'professional', 'enterprise') DEFAULT 'free',
    subscription_status ENUM('active', 'suspended', 'expired', 'trial') DEFAULT 'trial',
    trial_ends_at TIMESTAMP NULL,
    settings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subscription_status (subscription_status),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    avatar VARCHAR(255),
    mfa_enabled BOOLEAN DEFAULT FALSE,
    mfa_secret VARCHAR(255),
    is_admin BOOLEAN DEFAULT FALSE,
    is_super_admin BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    last_ip VARCHAR(45),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE organization_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'manager', 'agronomist', 'livestock_manager',
              'store_manager', 'accountant', 'viewer', 'worker') NOT NULL,
    permissions JSON,
    invited_by INT,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_org_user (organization_id, user_id),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subscription_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    billing_period ENUM('monthly', 'yearly') DEFAULT 'monthly',
    max_farms INT DEFAULT 1,
    max_users INT DEFAULT 5,
    max_storage_mb INT DEFAULT 500,
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('active', 'canceled', 'expired', 'pending') DEFAULT 'pending',
    starts_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NULL,
    auto_renew BOOLEAN DEFAULT TRUE,
    payment_method VARCHAR(50),
    payment_reference VARCHAR(255),
    amount DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE farms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    size DECIMAL(10, 2),
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    district VARCHAR(100),
    village VARCHAR(100),
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_organization_id (organization_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE blocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farm_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    area DECIMAL(10, 2),
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    INDEX idx_farm_id (farm_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE plots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    block_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    size DECIMAL(10, 2),
    soil_type VARCHAR(100),
    crop_assignment VARCHAR(255),
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE CASCADE,
    INDEX idx_block_id (block_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE crop_cycles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    farm_id INT NOT NULL,
    plot_id INT NOT NULL,
    crop_type VARCHAR(100) NOT NULL,
    variety VARCHAR(100),
    season VARCHAR(50),
    crop_batch_id VARCHAR(100) UNIQUE NOT NULL,
    start_date DATE,
    end_date DATE,
    status ENUM('planning', 'procurement', 'nursery', 'field',
                'monitoring', 'harvest', 'sales', 'completed') DEFAULT 'planning',
    budget DECIMAL(15, 2),
    expected_yield DECIMAL(10, 2),
    actual_yield DECIMAL(10, 2),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (plot_id) REFERENCES plots(id),
    INDEX idx_organization_id (organization_id),
    INDEX idx_status (status),
    INDEX idx_crop_batch_id (crop_batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE crop_operations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    crop_cycle_id INT NOT NULL,
    activity_type ENUM('planting', 'irrigation', 'spraying', 'weeding',
                       'fertilizing', 'monitoring', 'harvesting', 'other') NOT NULL,
    activity_date DATE NOT NULL,
    description TEXT,
    cost DECIMAL(15, 2),
    worker_ids JSON,
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    photo_urls JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id) ON DELETE CASCADE,
    INDEX idx_crop_cycle_id (crop_cycle_id),
    INDEX idx_activity_date (activity_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    tax_id VARCHAR(100),
    rating INT DEFAULT 1,
    payment_terms VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_organization_id (organization_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE crop_procurements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    crop_cycle_id INT NOT NULL,
    supplier_id INT,
    item_type ENUM('seeds', 'fertilizers', 'tools', 'pesticides', 'other') NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10, 2),
    unit VARCHAR(50),
    cost DECIMAL(15, 2),
    purchase_date DATE,
    batch_number VARCHAR(100),
    expiry_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    INDEX idx_crop_cycle_id (crop_cycle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE harvest_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    crop_cycle_id INT NOT NULL,
    harvest_date DATE NOT NULL,
    quantity DECIMAL(10, 2),
    quality_grade VARCHAR(50),
    storage_location VARCHAR(255),
    photos JSON,
    verified_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id) ON DELETE CASCADE,
    INDEX idx_crop_cycle_id (crop_cycle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE crop_sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    crop_cycle_id INT NOT NULL,
    sale_date DATE NOT NULL,
    buyer_name VARCHAR(255),
    quantity DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(50),
    unit_price DECIMAL(10, 2),
    total_amount DECIMAL(15, 2),
    payment_status ENUM('pending', 'paid', 'partial') DEFAULT 'pending',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id) ON DELETE CASCADE,
    INDEX idx_crop_cycle_id (crop_cycle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE animals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    farm_id INT NOT NULL,
    animal_id VARCHAR(100) UNIQUE NOT NULL,
    tag_number VARCHAR(100),
    name VARCHAR(255),
    species VARCHAR(100) NOT NULL,
    breed VARCHAR(100),
    gender ENUM('male', 'female') NOT NULL,
    birth_date DATE,
    parent_ids JSON,
    status ENUM('active', 'sold', 'deceased', 'transferred') DEFAULT 'active',
    purchase_price DECIMAL(10, 2),
    sale_price DECIMAL(10, 2),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    INDEX idx_organization_id (organization_id),
    INDEX idx_animal_id (animal_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE animal_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    animal_id INT NOT NULL,
    event_type ENUM('birth', 'vaccination', 'feeding', 'weight',
                    'breeding', 'production', 'treatment', 'death', 'sale') NOT NULL,
    event_date DATE NOT NULL,
    details TEXT,
    cost DECIMAL(10, 2),
    handler_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    INDEX idx_animal_id (animal_id),
    INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE animal_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    animal_id INT NOT NULL,
    from_farm_id INT,
    to_farm_id INT,
    movement_date DATE NOT NULL,
    reason VARCHAR(255),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (from_farm_id) REFERENCES farms(id),
    FOREIGN KEY (to_farm_id) REFERENCES farms(id),
    INDEX idx_animal_id (animal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE workers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    user_id INT,
    employee_id VARCHAR(100) UNIQUE,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(255),
    role VARCHAR(100),
    department VARCHAR(100),
    hire_date DATE,
    hourly_rate DECIMAL(10, 2),
    emergency_contact VARCHAR(255),
    status ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_organization_id (organization_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    worker_id INT NOT NULL,
    date DATE NOT NULL,
    clock_in TIME,
    clock_out TIME,
    gps_latitude_in DECIMAL(10, 8),
    gps_longitude_in DECIMAL(11, 8),
    gps_latitude_out DECIMAL(10, 8),
    gps_longitude_out DECIMAL(11, 8),
    photo_url VARCHAR(255),
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (worker_id, date),
    INDEX idx_worker_id (worker_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    assigned_to INT NOT NULL,
    assigned_by INT NOT NULL,
    farm_id INT,
    block_id INT,
    task_type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    deadline DATE,
    completed_at TIMESTAMP NULL,
    verified_at TIMESTAMP NULL,
    verification_photos JSON,
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    status ENUM('pending', 'in_progress', 'completed', 'verified', 'canceled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES workers(id),
    FOREIGN KEY (assigned_by) REFERENCES workers(id),
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (block_id) REFERENCES blocks(id),
    INDEX idx_organization_id (organization_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_status (status),
    INDEX idx_deadline (deadline)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payroll (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    worker_id INT NOT NULL,
    pay_period_start DATE NOT NULL,
    pay_period_end DATE NOT NULL,
    hours_worked DECIMAL(8,2) DEFAULT 0,
    gross_amount DECIMAL(12,2) DEFAULT 0,
    deductions DECIMAL(12,2) DEFAULT 0,
    net_amount DECIMAL(12,2) DEFAULT 0,
    status ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
    INDEX idx_organization_id (organization_id),
    INDEX idx_worker_id (worker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventory_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100),
    unit VARCHAR(50),
    quantity DECIMAL(10, 2) DEFAULT 0,
    reorder_level DECIMAL(10, 2),
    supplier_id INT,
    purchase_price DECIMAL(10, 2),
    expiry_date DATE,
    storage_location VARCHAR(255),
    batch_number VARCHAR(100),
    description TEXT,
    status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    INDEX idx_organization_id (organization_id),
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    type ENUM('stock_in', 'stock_out', 'transfer', 'adjustment') NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    unit_cost DECIMAL(10, 2),
    total_cost DECIMAL(10, 2),
    reference_type VARCHAR(100),
    reference_id INT,
    from_location VARCHAR(255),
    to_location VARCHAR(255),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    supplier_id INT NOT NULL,
    order_number VARCHAR(100) UNIQUE,
    order_date DATE NOT NULL,
    expected_delivery DATE,
    status ENUM('draft', 'sent', 'received', 'partial', 'canceled') DEFAULT 'draft',
    subtotal DECIMAL(15, 2),
    tax DECIMAL(15, 2),
    total_amount DECIMAL(15, 2),
    payment_status ENUM('pending', 'paid', 'partial') DEFAULT 'pending',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    INDEX idx_organization_id (organization_id),
    INDEX idx_order_number (order_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_order_id INT NOT NULL,
    item_id INT,
    item_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(50),
    unit_price DECIMAL(10, 2),
    line_total DECIMAL(15, 2),
    quantity_received DECIMAL(10, 2) DEFAULT 0,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id),
    INDEX idx_purchase_order_id (purchase_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE supplier_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    supplier_id INT NOT NULL,
    purchase_order_id INT,
    amount DECIMAL(15, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(100),
    reference VARCHAR(255),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    INDEX idx_organization_id (organization_id),
    INDEX idx_supplier_id (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE financial_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(100) NOT NULL,
    sub_category VARCHAR(100),
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    reference_type VARCHAR(100),
    reference_id INT,
    receipt_url VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_organization_id (organization_id),
    INDEX idx_type (type),
    INDEX idx_category (category),
    INDEX idx_transaction_date (transaction_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE budgets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    farm_id INT,
    fiscal_year VARCHAR(10) NOT NULL,
    category VARCHAR(100) NOT NULL,
    allocated_amount DECIMAL(15, 2) NOT NULL,
    spent_amount DECIMAL(15, 2) DEFAULT 0,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    UNIQUE KEY unique_budget (organization_id, farm_id, fiscal_year, category),
    INDEX idx_organization_id (organization_id),
    INDEX idx_fiscal_year (fiscal_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE trace_batches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    batch_id VARCHAR(100) UNIQUE NOT NULL,
    product_type VARCHAR(100) NOT NULL,
    crop_cycle_id INT,
    farm_id INT,
    block_id INT,
    plot_id INT,
    production_date DATE,
    quantity DECIMAL(10, 2),
    unit VARCHAR(50),
    current_location VARCHAR(255),
    status ENUM('active', 'completed', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id),
    FOREIGN KEY (farm_id) REFERENCES farms(id),
    FOREIGN KEY (block_id) REFERENCES blocks(id),
    FOREIGN KEY (plot_id) REFERENCES plots(id),
    INDEX idx_organization_id (organization_id),
    INDEX idx_batch_id (batch_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE trace_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    event_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    location VARCHAR(255),
    gps_latitude DECIMAL(10, 8),
    gps_longitude DECIMAL(11, 8),
    actor_id INT,
    description TEXT,
    attachments JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES trace_batches(id) ON DELETE CASCADE,
    INDEX idx_batch_id (batch_id),
    INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE trace_qr_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    qr_code VARCHAR(500) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    scans_count INT DEFAULT 0,
    last_scanned_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES trace_batches(id) ON DELETE CASCADE,
    INDEX idx_batch_id (batch_id),
    INDEX idx_qr_code (qr_code(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_journey (
    id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    stage VARCHAR(100) NOT NULL,
    stage_order INT DEFAULT 0,
    start_date DATE,
    end_date DATE,
    responsible_party VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES trace_batches(id) ON DELETE CASCADE,
    INDEX idx_batch_id (batch_id),
    INDEX idx_stage_order (stage_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_organization_id (organization_id),
    INDEX idx_user_id (user_id),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    user_id INT,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_organization_id (organization_id),
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE offline_actions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    worker_id INT NOT NULL,
    device_id VARCHAR(255),
    action_type VARCHAR(100) NOT NULL,
    action_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    synced_at TIMESTAMP NULL,
    status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
    error_message TEXT,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES workers(id),
    INDEX idx_organization_id (organization_id),
    INDEX idx_worker_id (worker_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_created (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50),
    is_encrypted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_setting_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE blog_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    author_id INT,
    category VARCHAR(100),
    tags JSON,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE demo_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    organization_name VARCHAR(255),
    farm_size VARCHAR(100),
    message TEXT,
    status ENUM('new', 'contacted', 'scheduled', 'completed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- SEED DATA: subscription plans
-- ==========================================

INSERT INTO subscription_plans (name, slug, price, currency, billing_period, max_farms, max_users, max_storage_mb, features, is_active) VALUES
('Free', 'free', 0.00, 'USD', 'monthly', 1, 3, 500, '["crop_management","basic_reporting"]', 1),
('Basic', 'basic', 29.00, 'USD', 'monthly', 5, 10, 2000, '["crop_management","livestock_management","inventory","basic_reporting"]', 1),
('Professional', 'professional', 99.00, 'USD', 'monthly', 20, 50, 10000, '["crop_management","livestock_management","inventory","finance","traceability","advanced_reporting"]', 1),
('Enterprise', 'enterprise', 299.00, 'USD', 'monthly', 999, 999, 100000, '["crop_management","livestock_management","inventory","finance","traceability","advanced_reporting","api_access","priority_support"]', 1);

-- ==========================================
-- SEED DATA: role permission matrix + master permission list
-- ==========================================

INSERT INTO system_settings (setting_key, setting_value, setting_group) VALUES
('role_permissions.owner', '["*"]', 'roles'),
('role_permissions.manager', '["farms.*","crops.*","livestock.*","workers.*","inventory.*","procurement.*","reporting.view"]', 'roles'),
('role_permissions.agronomist', '["farms.view","crops.*","reporting.view"]', 'roles'),
('role_permissions.livestock_manager', '["farms.view","livestock.*","reporting.view"]', 'roles'),
('role_permissions.store_manager', '["inventory.*","procurement.*","reporting.view"]', 'roles'),
('role_permissions.accountant', '["finance.*","reporting.view","reporting.export"]', 'roles'),
('role_permissions.viewer', '["farms.view","crops.view","livestock.view","reporting.view"]', 'roles'),
('role_permissions.worker', '["tasks.view","attendance.self","activities.self"]', 'roles'),
('permissions.master_list', '["farms.view","farms.create","farms.edit","farms.delete","crops.view","crops.create","crops.edit","crops.delete","livestock.view","livestock.create","livestock.edit","livestock.delete","workers.view","workers.create","workers.edit","workers.delete","inventory.view","inventory.create","inventory.edit","inventory.delete","procurement.view","procurement.create","procurement.edit","finance.view","finance.create","finance.edit","traceability.view","traceability.create","reporting.view","reporting.export","settings.manage","tasks.view","tasks.create","tasks.assign","attendance.self","attendance.view_all","activities.self"]', 'permissions');

-- ==========================================
-- SEED DATA: demo tenant "Green Valley Farms"
-- Password for every demo login below is: Password123!
-- ==========================================

-- Platform admin
INSERT INTO users (id, email, password_hash, name, is_admin, is_super_admin, status) VALUES
(1, 'admin@sfmtp.local', '$2y$12$iVlXU7G6XCKpfyKUpFU.XOLywb1w4U/4V9MHdxEDn8zd9SITimNYi', 'Platform Admin', 1, 1, 'active');

-- Demo organization
INSERT INTO organizations (id, name, slug, email, phone, subscription_plan, subscription_status, trial_ends_at) VALUES
(1, 'Green Valley Farms', 'green-valley-farms', 'owner@greenvalley.test', '+255700000123', 'professional', 'active', DATE_ADD(NOW(), INTERVAL 30 DAY));

-- Farm owner + worker-portal login
INSERT INTO users (id, email, password_hash, name, phone, status) VALUES
(2, 'owner@greenvalley.test', '$2y$12$iVlXU7G6XCKpfyKUpFU.XOLywb1w4U/4V9MHdxEDn8zd9SITimNYi', 'Amara Owner', '+250700000001', 'active'),
(3, 'worker@greenvalley.test', '$2y$12$iVlXU7G6XCKpfyKUpFU.XOLywb1w4U/4V9MHdxEDn8zd9SITimNYi', 'Jane Smith', '+250700000002', 'active');

INSERT INTO organization_users (organization_id, user_id, role, status) VALUES
(1, 2, 'owner', 'active'),
(1, 3, 'worker', 'active');

-- Farm structure
INSERT INTO farms (id, organization_id, name, size, gps_latitude, gps_longitude, district, village, status, created_by) VALUES
(1, 1, 'Green Valley Main Farm', 1200.00, -1.94410000, 30.06190000, 'Kigali', 'Kacyiru', 'active', 2);

INSERT INTO blocks (id, farm_id, name, area) VALUES
(1, 1, 'Block A', 300.00);

INSERT INTO plots (id, block_id, name, size, soil_type) VALUES
(1, 1, 'Plot A1', 50.00, 'Loam');

-- Workers
INSERT INTO workers (id, organization_id, user_id, employee_id, name, phone, email, role, department, hire_date, hourly_rate, status, created_by) VALUES
(1, 1, 3, 'EMP-000001', 'Jane Smith', '+250700000002', 'worker@greenvalley.test', 'Field Worker', 'Crop Operations', CURDATE(), 3.50, 'active', 2),
(2, 1, NULL, 'EMP-000002', 'Tom Richards', '+250700000003', NULL, 'Field Worker', 'Livestock', CURDATE(), 3.00, 'active', 2);

-- Crop cycle + operation
INSERT INTO crop_cycles (id, organization_id, farm_id, plot_id, crop_type, variety, season, crop_batch_id, start_date, status, budget, expected_yield, created_by) VALUES
(1, 1, 1, 1, 'Maize', 'H614', '2026A', 'CROP-20260301-DEMO01', '2026-03-01', 'field', 5000.00, 2500.00, 2);

INSERT INTO crop_operations (crop_cycle_id, activity_type, activity_date, description, cost, created_by) VALUES
(1, 'planting', '2026-03-01', 'Planted maize seeds across Plot A1', 400.00, 2);

-- Livestock
INSERT INTO animals (id, organization_id, farm_id, animal_id, tag_number, name, species, breed, gender, birth_date, status, purchase_price, created_by) VALUES
(1, 1, 1, 'AN-DEMO001', 'T-101', 'Bella', 'Cattle', 'Friesian', 'female', '2023-05-10', 'active', 800.00, 2);

-- Supplier + inventory
INSERT INTO suppliers (id, organization_id, name, contact_person, phone, email, status, created_by) VALUES
(1, 1, 'AgroSupply Rwanda', 'Eric Niyonzima', '+250780000000', 'sales@agrosupply.test', 'active', 2);

INSERT INTO inventory_items (id, organization_id, category, name, sku, unit, quantity, reorder_level, supplier_id, purchase_price, status, created_by) VALUES
(1, 1, 'Fertilizer', 'NPK 17-17-17', 'SKU-NPK-001', 'kg', 40.00, 100.00, 1, 1.20, 'active', 2);

-- Tasks
INSERT INTO tasks (organization_id, assigned_to, assigned_by, farm_id, block_id, task_type, title, description, priority, deadline, status) VALUES
(1, 1, 1, 1, 1, 'fertilizing', 'Apply Fertilizer to Maize', 'Apply NPK fertilizer to Block A maize', 'high', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'in_progress'),
(1, 2, 1, 1, NULL, 'harvesting', 'Harvest Wheat', 'Harvest ready wheat plots', 'medium', DATE_ADD(CURDATE(), INTERVAL 8 DAY), 'pending');

-- Finance
INSERT INTO financial_transactions (organization_id, type, category, amount, description, transaction_date, created_by) VALUES
(1, 'expense', 'Inputs', 400.00, 'Seeds and fertilizer purchase', CURDATE(), 2),
(1, 'income', 'Crop Sales', 1500.00, 'Sale of previous maize harvest', CURDATE(), 2);

-- Traceability batch + journey
INSERT INTO trace_batches (id, organization_id, batch_id, product_type, crop_cycle_id, farm_id, block_id, plot_id, production_date, quantity, unit, current_location, status) VALUES
(1, 1, 'TRC-DEMO0001', 'Maize', 1, 1, 1, 1, '2026-03-01', 2500.00, 'kg', 'Green Valley Main Farm Store', 'active');

INSERT INTO trace_events (batch_id, event_type, location, description) VALUES
(1, 'planted', 'Plot A1', 'Maize seeds planted'),
(1, 'harvested', 'Plot A1', 'Maize harvested and moved to store');

INSERT INTO product_journey (batch_id, stage, stage_order, start_date, responsible_party) VALUES
(1, 'Planting', 1, '2026-03-01', 'Green Valley Farms'),
(1, 'Storage', 2, CURDATE(), 'Green Valley Farms');

SET FOREIGN_KEY_CHECKS = 1;

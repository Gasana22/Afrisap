<?php

return [
    "CREATE TABLE IF NOT EXISTS organizations (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS organization_users (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS subscription_plans (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS subscriptions (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS system_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_group VARCHAR(50),
        is_encrypted BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_setting_key (setting_key),
        INDEX idx_setting_group (setting_group)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

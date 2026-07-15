<?php

return [
    "CREATE TABLE IF NOT EXISTS financial_transactions (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS budgets (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

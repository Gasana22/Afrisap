CREATE TABLE IF NOT EXISTS crop_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seasons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crop_cycles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_code VARCHAR(60) NOT NULL UNIQUE,
    plot_id INT UNSIGNED NOT NULL,
    crop_type_id INT UNSIGNED NOT NULL,
    season_id INT UNSIGNED NULL,
    budget DECIMAL(12,2) NULL,
    expected_yield DECIMAL(12,2) NULL,
    start_date DATE NULL,
    status ENUM('planning','procurement','nursery','field','monitoring','harvested','closed') NOT NULL DEFAULT 'planning',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plot_id) REFERENCES plots(id) ON DELETE CASCADE,
    FOREIGN KEY (crop_type_id) REFERENCES crop_types(id),
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crop_inputs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crop_cycle_id INT UNSIGNED NOT NULL,
    input_type ENUM('seed','fertilizer','chemical','tool') NOT NULL,
    supplier_name VARCHAR(150) NULL,
    quantity DECIMAL(10,2) NULL,
    unit VARCHAR(30) NULL,
    cost DECIMAL(12,2) NULL,
    purchase_date DATE NULL,
    expiry_date DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nursery_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crop_cycle_id INT UNSIGNED NOT NULL,
    record_date DATE NOT NULL,
    germination_rate DECIMAL(5,2) NULL,
    treatment VARCHAR(255) NULL,
    survival_rate DECIMAL(5,2) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS field_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crop_cycle_id INT UNSIGNED NOT NULL,
    activity_type ENUM('planting','weeding','irrigation','spraying','fertilizing','other') NOT NULL,
    activity_date DATE NOT NULL,
    worker_name VARCHAR(150) NULL,
    gps_lat DECIMAL(10,7) NULL,
    gps_lng DECIMAL(10,7) NULL,
    cost DECIMAL(12,2) NULL,
    notes TEXT NULL,
    status ENUM('pending','ongoing','completed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    field_activity_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_activity_id) REFERENCES field_activities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monitoring_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crop_cycle_id INT UNSIGNED NOT NULL,
    type ENUM('disease','pest','growth') NOT NULL,
    record_date DATE NOT NULL,
    description TEXT NULL,
    severity ENUM('low','medium','high') NULL,
    photo_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS yield_forecasts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crop_cycle_id INT UNSIGNED NOT NULL,
    forecast_date DATE NOT NULL,
    estimated_yield DECIMAL(12,2) NOT NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS harvests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    crop_cycle_id INT UNSIGNED NOT NULL,
    harvest_date DATE NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    unit VARCHAR(30) NULL,
    quality_grade VARCHAR(50) NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS crop_sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    harvest_id INT UNSIGNED NOT NULL,
    buyer_name VARCHAR(150) NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    revenue DECIMAL(14,2) NOT NULL,
    sale_date DATE NOT NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (harvest_id) REFERENCES harvests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

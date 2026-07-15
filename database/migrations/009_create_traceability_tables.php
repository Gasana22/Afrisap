<?php

return [
    "CREATE TABLE IF NOT EXISTS trace_batches (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS trace_events (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS trace_qr_codes (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS product_journey (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

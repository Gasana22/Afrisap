<?php

return [
    "CREATE TABLE IF NOT EXISTS farms (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS blocks (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS plots (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

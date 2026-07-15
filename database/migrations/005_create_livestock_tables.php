<?php

return [
    "CREATE TABLE IF NOT EXISTS animals (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS animal_events (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS animal_movements (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

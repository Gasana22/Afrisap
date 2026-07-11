CREATE TABLE IF NOT EXISTS inventory_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category ENUM('seed','fertilizer','chemical','equipment','other') NOT NULL DEFAULT 'other',
    unit VARCHAR(30) NULL,
    reorder_level DECIMAL(10,2) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_stock (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    farm_id INT UNSIGNED NOT NULL,
    quantity_on_hand DECIMAL(12,2) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_item_farm (item_id, farm_id),
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    farm_id INT UNSIGNED NOT NULL,
    type ENUM('in','out','transfer_in','transfer_out') NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    related_farm_id INT UNSIGNED NULL,
    reference VARCHAR(150) NULL,
    performed_by INT UNSIGNED NOT NULL,
    movement_date DATE NOT NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE CASCADE,
    FOREIGN KEY (related_farm_id) REFERENCES farms(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

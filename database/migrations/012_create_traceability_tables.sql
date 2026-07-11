CREATE TABLE IF NOT EXISTS trace_batches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_type ENUM('crop','livestock') NOT NULL,
    crop_cycle_id INT UNSIGNED NULL,
    animal_id INT UNSIGNED NULL,
    batch_code VARCHAR(60) NOT NULL UNIQUE,
    status ENUM('active','completed','recalled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_crop_cycle (crop_cycle_id),
    UNIQUE KEY uq_animal (animal_id),
    FOREIGN KEY (crop_cycle_id) REFERENCES crop_cycles(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trace_qr_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trace_batch_id INT UNSIGNED NOT NULL UNIQUE,
    public_token VARCHAR(64) NOT NULL UNIQUE,
    qr_image_path VARCHAR(255) NOT NULL,
    generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trace_batch_id) REFERENCES trace_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trace_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trace_batch_id INT UNSIGNED NOT NULL,
    document_type ENUM('certificate','invoice','contract','other') NOT NULL DEFAULT 'other',
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes VARCHAR(255) NULL,
    FOREIGN KEY (trace_batch_id) REFERENCES trace_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trace_approvals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trace_batch_id INT UNSIGNED NOT NULL,
    approval_type VARCHAR(100) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trace_batch_id) REFERENCES trace_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_journey (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trace_batch_id INT UNSIGNED NOT NULL,
    stage ENUM('storage','processing','packaging','distribution','delivered') NOT NULL,
    stage_date DATE NOT NULL,
    location VARCHAR(150) NULL,
    responsible_user_id INT UNSIGNED NOT NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trace_batch_id) REFERENCES trace_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (responsible_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO trace_batches (batch_type, crop_cycle_id, batch_code, status)
SELECT 'crop', cc.id, cc.batch_code, 'active'
FROM crop_cycles cc
WHERE NOT EXISTS (SELECT 1 FROM trace_batches tb WHERE tb.crop_cycle_id = cc.id);

INSERT INTO trace_batches (batch_type, animal_id, batch_code, status)
SELECT 'livestock', a.id, a.animal_code, 'active'
FROM animals a
WHERE NOT EXISTS (SELECT 1 FROM trace_batches tb WHERE tb.animal_id = a.id);

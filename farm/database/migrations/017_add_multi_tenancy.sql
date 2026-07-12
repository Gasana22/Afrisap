CREATE TABLE organizations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    owner_user_id INT UNSIGNED NULL,
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER role_id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT;

ALTER TABLE organizations
    ADD FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE farms
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER owner_id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT;

INSERT INTO organizations (name, owner_user_id, status)
SELECT CONCAT(u.name, '''s Organization'), u.id, 'active'
FROM (SELECT DISTINCT owner_id FROM farms WHERE owner_id IS NOT NULL AND organization_id IS NULL) fo
JOIN users u ON u.id = fo.owner_id
WHERE NOT EXISTS (SELECT 1 FROM organizations o WHERE o.owner_user_id = u.id);

UPDATE farms f
JOIN organizations o ON o.owner_user_id = f.owner_id
SET f.organization_id = o.id
WHERE f.organization_id IS NULL AND f.owner_id IS NOT NULL;

UPDATE users u
JOIN organizations o ON o.owner_user_id = u.id
SET u.organization_id = o.id
WHERE u.organization_id IS NULL;

ALTER TABLE roles
    ADD COLUMN scope ENUM('platform','tenant') NOT NULL DEFAULT 'tenant' AFTER slug;

UPDATE roles SET slug = 'super_admin', name = 'Super Admin',
    description = 'Full platform administration and oversight', scope = 'platform'
    WHERE slug = 'system_administrator';

ALTER TABLE settings
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    DROP INDEX setting_key,
    ADD UNIQUE KEY uq_org_setting (organization_id, setting_key);

ALTER TABLE audit_logs
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER user_id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL;

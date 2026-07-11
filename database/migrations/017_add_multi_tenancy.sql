-- Multi-tenancy foundation: organizations are the tenant. A Farm Owner's
-- organization can own multiple farms. users.organization_id NULL means a
-- platform-tier staff member (Super Admin / Manager / Accountant); set means
-- which tenant that user belongs to. roles.scope distinguishes the two pools.

CREATE TABLE IF NOT EXISTS organizations (
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

-- Added now that users exists structurally; owner_user_id was left FK-less above
-- to avoid a create-order cycle between organizations and users.
ALTER TABLE organizations
    ADD FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Nullable at the DB level deliberately: farms.owner_id was already nullable,
-- so a full backfill can't be guaranteed for pre-existing orphaned rows (none
-- exist in this app's data today, but the migration must still be safe if it
-- ever runs against a database that has some). Every new farm going forward is
-- required to have one at the application layer (FarmController).
ALTER TABLE farms
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER owner_id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT;

-- Backfill: one organization per existing distinct farm owner, then attach
-- their farms to it. No-op on a fresh install (no farms yet).
INSERT INTO organizations (name, owner_user_id, status)
SELECT CONCAT(u.name, '''s Organization'), u.id, 'active'
FROM (SELECT DISTINCT owner_id FROM farms WHERE owner_id IS NOT NULL AND organization_id IS NULL) fo
JOIN users u ON u.id = fo.owner_id
WHERE NOT EXISTS (SELECT 1 FROM organizations o WHERE o.owner_user_id = u.id);

UPDATE farms f
JOIN organizations o ON o.owner_user_id = f.owner_id
SET f.organization_id = o.id
WHERE f.organization_id IS NULL AND f.owner_id IS NOT NULL;

-- Backfilled owners become tenant users of their own new organization.
UPDATE users u
JOIN organizations o ON o.owner_user_id = u.id
SET u.organization_id = o.id
WHERE u.organization_id IS NULL;

ALTER TABLE roles
    ADD COLUMN scope ENUM('platform','tenant') NOT NULL DEFAULT 'tenant' AFTER slug;

-- The original single "does everything" role becomes the platform tier's
-- Super Admin. Same role_id, same role_permissions, same seeded admin user --
-- just correctly typed as platform-scoped now, so admin@afrisap.test keeps
-- working via the new platform login without any data loss.
UPDATE roles SET slug = 'super_admin', name = 'Super Admin',
    description = 'Full platform administration and oversight', scope = 'platform'
    WHERE slug = 'system_administrator';

-- Tenant-owned config (company display name, default currency/units) lives
-- alongside the existing platform-wide rows (organization_id IS NULL).
ALTER TABLE settings
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    DROP INDEX setting_key,
    ADD UNIQUE KEY uq_org_setting (organization_id, setting_key);

ALTER TABLE audit_logs
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER user_id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL;

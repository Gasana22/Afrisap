-- media_files.farm_id is nullable (org-wide documents, not tied to one farm),
-- so a farm join can't scope those rows to a tenant -- add organization_id
-- directly so every file, farm-linked or not, is still tenant-scoped.
ALTER TABLE media_files
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER farm_id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

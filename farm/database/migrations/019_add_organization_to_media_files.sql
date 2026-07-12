ALTER TABLE media_files
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER farm_id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

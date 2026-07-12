ALTER TABLE suppliers
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE inventory_items
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

-- suppliers and inventory_items hold tenant-specific business data (a farm's
-- own supplier relationships, a farm's own item catalog) but had no farm/org
-- link at all -- unlike crop_types/seasons, which are legitimately global
-- reference lists, these were an unscoped cross-tenant leak once multi-tenant.

ALTER TABLE suppliers
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

ALTER TABLE inventory_items
    ADD COLUMN organization_id INT UNSIGNED NULL AFTER id,
    ADD FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE;

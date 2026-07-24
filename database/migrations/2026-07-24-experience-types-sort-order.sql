-- Client feedback on the nav dropdown numbering: "These numbers are meant
-- to come from the database" -- the 01/02/03... badges on Experiential
-- Tours were a computed loop index, not a real per-row value the admin
-- could control (unlike tour_categories.sort_order, already used for Trip
-- Tours and admin-editable via "Menu order"). Adds the same sort_order
-- column to experience_types so both dropdowns work the same way.

ALTER TABLE `experience_types`
  ADD COLUMN `sort_order` int(10) unsigned NOT NULL DEFAULT 0 AFTER `slug`;

UPDATE `experience_types` SET `sort_order` = 1 WHERE `slug` = 'cultural-experience';
UPDATE `experience_types` SET `sort_order` = 2 WHERE `slug` = 'farm-experience';
UPDATE `experience_types` SET `sort_order` = 3 WHERE `slug` = 'ghetto-experience';
UPDATE `experience_types` SET `sort_order` = 4 WHERE `slug` = 'manufactural-factory-experience';
UPDATE `experience_types` SET `sort_order` = 5 WHERE `slug` = 'sports-experience';

-- tour_categories.sort_order is scoped per menu_group, so "School Trips"
-- (its own group) shared sort_order=1 with "Island Trips" -- both would
-- show the same "01" badge in the combined Trip Tours dropdown (trip +
-- school). Bumped to 8 so the Trip Tours dropdown keeps unique numbers.
UPDATE `tour_categories` SET `sort_order` = 8 WHERE `slug` = 'school-trips';

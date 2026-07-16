-- Client feedback: add "Group Tours" inside the Specialised Tours nav
-- dropdown (alongside Scheduled Tours / Create Your Own Tour / Virtual
-- Experience). Per follow-up: a real tour category admins can assign tours
-- to, not a static info page -- so it needs its own menu_group value to
-- keep it out of the Safari Tours / Trip Tours dropdowns (those pull
-- categories by menu_group='safari' / 'trip','school' respectively) while
-- still being a normal, admin-manageable category.

ALTER TABLE `tour_categories`
  MODIFY COLUMN `menu_group` enum('safari','trip','school','specialised') NOT NULL DEFAULT 'safari';

INSERT INTO `tour_categories` (`menu_group`, `name`, `slug`, `sort_order`)
SELECT 'specialised', 'Group Tours', 'group-tours', 1
WHERE NOT EXISTS (SELECT 1 FROM `tour_categories` WHERE `slug` = 'group-tours');

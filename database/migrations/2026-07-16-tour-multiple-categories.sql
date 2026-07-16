-- Client feedback: a multi-day tour can span more than one category (e.g.
-- day 1 is gorilla trekking, day 6 is a wildlife game drive), so the Add/
-- Edit Tour form's Category field needs to allow multiple selections.
--
-- tours.category_id stays as the required "primary" category (everything
-- that already depends on a single category -- the required-field
-- validation, the tour card's category label, nav links) is untouched.
-- This new join table holds any additional categories a tour also belongs
-- to; tours.php's ?category= filter matches a tour if its primary OR any
-- extra category matches, so browsing any of the tour's categories surfaces
-- it. Same relationship shape as the existing tour_destinations table.

CREATE TABLE IF NOT EXISTS `tour_extra_categories` (
  `tour_id` int unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  PRIMARY KEY (`tour_id`,`category_id`),
  KEY `fk_tour_extra_categories_category` (`category_id`),
  CONSTRAINT `fk_tour_extra_categories_category` FOREIGN KEY (`category_id`) REFERENCES `tour_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tour_extra_categories_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

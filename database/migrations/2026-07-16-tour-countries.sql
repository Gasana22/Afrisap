-- Client feedback (sketched directly on the Add/Edit Tour form): a
-- "Countries" checkbox grid, same multi-select pattern as Destination
-- Parks, so an admin can tick one or several countries (e.g. Uganda,
-- Kenya, Tanzania, Rwanda, DR Congo) a tour covers -- captured directly,
-- not just inferred from which destinations/parks are picked.

CREATE TABLE IF NOT EXISTS `tour_countries` (
  `tour_id` int unsigned NOT NULL,
  `country_id` int unsigned NOT NULL,
  PRIMARY KEY (`tour_id`,`country_id`),
  KEY `fk_tour_countries_country` (`country_id`),
  CONSTRAINT `fk_tour_countries_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tour_countries_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

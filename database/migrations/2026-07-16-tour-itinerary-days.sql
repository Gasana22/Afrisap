-- Client feedback: an "Itinerary Builder" on the Manage Tour screen -- a
-- day-by-day breakdown (Day 1, Day 2, ...), each with its own title and
-- description, shown above the existing Destinations / Activities / FAQs
-- panels (which are unchanged). Also rendered on the public tour page so
-- visitors can see the day-by-day plan, not just the overall overview.

CREATE TABLE IF NOT EXISTS `tour_itinerary_days` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int unsigned NOT NULL,
  `day_number` int unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tour_itinerary_days_day` (`tour_id`,`day_number`),
  CONSTRAINT `fk_tour_itinerary_days_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

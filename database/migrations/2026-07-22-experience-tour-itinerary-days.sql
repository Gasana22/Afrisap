-- Client feedback: "one may want his tour to have only activities without
-- itinerary while other may want to be like tours have both sides, let it
-- be flexible" -- Experiential tours get their own optional day-by-day
-- itinerary builder, same shape as tour_itinerary_days for Safari tours.
-- Entirely independent of the Activities list, so a tour can have either,
-- both, or neither.

CREATE TABLE IF NOT EXISTS `experience_tour_itinerary_days` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `experience_tour_id` int(10) unsigned NOT NULL,
  `day_number` int(10) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_experience_tour_itinerary_days_day` (`experience_tour_id`,`day_number`),
  CONSTRAINT `fk_experience_tour_itinerary_days_tour` FOREIGN KEY (`experience_tour_id`) REFERENCES `experience_tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

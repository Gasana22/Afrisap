-- Client feedback: "the tourists may come for a safari tour and they want
-- probably just a day for farm visit and some cultural, here by we have to
-- think on how to make it combined and also stand alone on experiential" --
-- lets a visitor enquiring about a Safari tour optionally add one or more
-- published Experiential tours to the same enquiry, without those
-- Experiential tours losing their own standalone booking flow.
--
-- experience_tour_title is a snapshot (like tour_itinerary_inquiries'
-- other snapshot columns) so the add-on still reads correctly even if the
-- experience tour is later renamed or removed.

CREATE TABLE IF NOT EXISTS `tour_itinerary_inquiry_addons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inquiry_id` int(10) unsigned NOT NULL,
  `experience_tour_id` int(10) unsigned DEFAULT NULL,
  `experience_tour_title` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_itinerary_addons_inquiry` (`inquiry_id`),
  KEY `fk_itinerary_addons_experience_tour` (`experience_tour_id`),
  CONSTRAINT `fk_itinerary_addons_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `tour_itinerary_inquiries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_itinerary_addons_experience_tour` FOREIGN KEY (`experience_tour_id`) REFERENCES `experience_tours` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

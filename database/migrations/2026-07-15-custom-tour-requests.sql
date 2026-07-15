-- "Create Your Own Tour" feature: a tourist describes the trip they want
-- (destinations, budget, activities, an optional experiential mix, pax,
-- days) and a consultant follows up. This is a lead/request record, not a
-- bookable entity, so selections are stored as plain text rather than
-- normalized join tables -- there's nothing else that needs to reference
-- "the destinations on custom tour request #12" the way tours/destinations
-- are cross-referenced elsewhere.

CREATE TABLE IF NOT EXISTS `custom_tour_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tour_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pax` int unsigned NOT NULL DEFAULT '1',
  `days` int unsigned NOT NULL,
  `budget_type` enum('Luxury','Mid-Range','Budget') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Mid-Range',
  `destinations` text COLLATE utf8mb4_general_ci,
  `activities` text COLLATE utf8mb4_general_ci,
  `experience_types` text COLLATE utf8mb4_general_ci,
  `notes` text COLLATE utf8mb4_general_ci,
  `status` enum('new','contacted','closed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

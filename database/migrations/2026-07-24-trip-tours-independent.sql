-- Client feedback: "The panel is mixed up separate them please, let every
-- section work independently for now until we get done then we do table
-- relationships" -- Safari Tours, Trip Tours and Specialised Tours all
-- shared one `tours` table and one `tour_categories` table, distinguished
-- only by a `menu_group` column. This gives Trip Tours its own fully
-- independent tables (categories, tours, destinations, activities,
-- itinerary days, FAQs), mirroring the Safari tour system exactly. No
-- trip tours existed yet in the shared `tours` table, so there's no tour
-- data to migrate -- only the 8 existing Trip/School categories, moved
-- across with their original names/slugs/order preserved.

CREATE TABLE `trip_tour_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `trip_tour_categories` (`name`, `slug`, `sort_order`)
SELECT `name`, `slug`, `sort_order` FROM `tour_categories` WHERE `menu_group` IN ('trip', 'school') ORDER BY `sort_order`;

DELETE FROM `tour_categories` WHERE `menu_group` IN ('trip', 'school');

CREATE TABLE `trip_tours` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `budget_type` enum('Luxury','Mid-Range','Budget') NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `days` int(10) unsigned NOT NULL,
  `scheduled_date` date DEFAULT NULL,
  `min_pax` int(10) unsigned NOT NULL DEFAULT 1,
  `max_pax` int(10) unsigned NOT NULL,
  `short_overview` text DEFAULT NULL,
  `full_overview` text DEFAULT NULL,
  `top_highlights` text DEFAULT NULL,
  `hotel_info` text DEFAULT NULL,
  `vehicle_info` text DEFAULT NULL,
  `flight_info` text DEFAULT NULL,
  `includes` text DEFAULT NULL,
  `excludes` text DEFAULT NULL,
  `operator_id` int(10) unsigned DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_trip_tours_category` (`category_id`),
  KEY `fk_trip_tours_operator` (`operator_id`),
  CONSTRAINT `fk_trip_tours_category` FOREIGN KEY (`category_id`) REFERENCES `trip_tour_categories` (`id`),
  CONSTRAINT `fk_trip_tours_operator` FOREIGN KEY (`operator_id`) REFERENCES `tour_operators` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `trip_tour_destinations` (
  `trip_tour_id` int(10) unsigned NOT NULL,
  `destination_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`trip_tour_id`,`destination_id`),
  KEY `fk_trip_tour_destinations_destination` (`destination_id`),
  CONSTRAINT `fk_trip_tour_destinations_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`),
  CONSTRAINT `fk_trip_tour_destinations_tour` FOREIGN KEY (`trip_tour_id`) REFERENCES `trip_tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `trip_tour_activities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_tour_id` int(10) unsigned NOT NULL,
  `activity_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_trip_tour_activities_tour` (`trip_tour_id`),
  KEY `fk_trip_tour_activities_activity` (`activity_id`),
  CONSTRAINT `fk_trip_tour_activities_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trip_tour_activities_tour` FOREIGN KEY (`trip_tour_id`) REFERENCES `trip_tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `trip_tour_itinerary_days` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_tour_id` int(10) unsigned NOT NULL,
  `day_number` int(10) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_trip_tour_itinerary_days_day` (`trip_tour_id`,`day_number`),
  CONSTRAINT `fk_trip_tour_itinerary_days_tour` FOREIGN KEY (`trip_tour_id`) REFERENCES `trip_tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `trip_tour_faqs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_tour_id` int(10) unsigned NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_trip_tour_faqs_tour` (`trip_tour_id`),
  CONSTRAINT `fk_trip_tour_faqs_tour` FOREIGN KEY (`trip_tour_id`) REFERENCES `trip_tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `bookings`
  MODIFY COLUMN `bookable_type` enum('tour','experience_tour','trip_tour') NOT NULL;

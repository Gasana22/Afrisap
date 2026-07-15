-- Adds the site_settings singleton table used to make the homepage hero
-- copy and background photo editable from the admin panel (client feedback:
-- hero should be dynamic from the backend to run low/high season promos).
-- Safe to run against an existing database that was seeded from
-- Dump20260710.sql before this table was added.

CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` tinyint unsigned NOT NULL,
  `hero_eyebrow` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hero_title` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hero_subtitle` text COLLATE utf8mb4_general_ci,
  `hero_background_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `site_settings` (`id`) VALUES (1);

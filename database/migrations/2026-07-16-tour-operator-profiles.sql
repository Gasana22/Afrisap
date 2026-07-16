-- Client feedback: capture a full tour operator profile (not just name/
-- logo/phone/email) so operators can have their own profile page listing
-- everything a customer would want to see before trusting them -- contact
-- person, website, a larger profile photo, tour type, association
-- memberships (e.g. AUTO/UTB/UWA), TripAdvisor link, budget tier, years of
-- experience, destination country, and a company overview -- plus their
-- associated tours shown on the same page.

ALTER TABLE `tour_operators`
  ADD COLUMN `contact_person` varchar(150) DEFAULT NULL AFTER `email`,
  ADD COLUMN `website` varchar(255) DEFAULT NULL AFTER `contact_person`,
  ADD COLUMN `profile_image_path` varchar(255) DEFAULT NULL AFTER `website`,
  ADD COLUMN `tour_type` varchar(150) DEFAULT NULL AFTER `profile_image_path`,
  ADD COLUMN `member_of` varchar(255) DEFAULT NULL AFTER `tour_type`,
  ADD COLUMN `trip_advisor_link` varchar(255) DEFAULT NULL AFTER `member_of`,
  ADD COLUMN `budget_type` enum('Luxury','Mid-Range','Budget') DEFAULT NULL AFTER `trip_advisor_link`,
  ADD COLUMN `years_experience` smallint unsigned DEFAULT NULL AFTER `budget_type`,
  ADD COLUMN `country_id` int unsigned DEFAULT NULL AFTER `years_experience`,
  ADD COLUMN `overview` text DEFAULT NULL AFTER `country_id`,
  ADD KEY `fk_tour_operators_country` (`country_id`),
  ADD CONSTRAINT `fk_tour_operators_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL;

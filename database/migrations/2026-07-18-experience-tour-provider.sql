-- Client feedback: the tour detail page's price/facts card shows "Tour
-- operator in charge" for Safari tours -- the client wants the same shown
-- for Experiential tours. There was no link from an experience tour to a
-- specific provider (only experience_types -> service_providers, a whole
-- category to a list of providers), so this adds a direct, optional link,
-- same shape as tours.operator_id -> tour_operators.

ALTER TABLE `experience_tours`
  ADD COLUMN `provider_id` int(10) unsigned DEFAULT NULL AFTER `experience_type_id`,
  ADD KEY `fk_experience_tours_provider` (`provider_id`),
  ADD CONSTRAINT `fk_experience_tours_provider` FOREIGN KEY (`provider_id`) REFERENCES `service_providers` (`id`) ON DELETE SET NULL;

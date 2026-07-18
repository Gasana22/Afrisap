-- Client sketch on the Buganda Cultural Experiences destination page: a
-- sidebar of "activity tiles" each showing an Amount -- a per-activity
-- price, which didn't exist anywhere in the schema (destination
-- activities, tour activities, etc. all have title/description only).

ALTER TABLE `experience_destination_activities`
  ADD COLUMN `amount` decimal(10,2) DEFAULT NULL AFTER `description`;

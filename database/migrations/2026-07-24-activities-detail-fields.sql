-- Client feedback: "In activities, create Description, under description
-- images, additional information, location, national parks around, number
-- of hrs, includes and excludes in that activity, then Itineraries
-- associated with that activity should appear."
--
-- Images (gallery) and number of hours (duration_hours) already existed.
-- Adds the remaining fields so an activity can carry the same kind of
-- detail a tour does.

ALTER TABLE `activities`
  ADD COLUMN `location` varchar(150) DEFAULT NULL AFTER `destination_id`,
  ADD COLUMN `national_parks_around` text DEFAULT NULL AFTER `location`,
  ADD COLUMN `additional_info` text DEFAULT NULL AFTER `national_parks_around`,
  ADD COLUMN `includes` text DEFAULT NULL AFTER `additional_info`,
  ADD COLUMN `excludes` text DEFAULT NULL AFTER `includes`;

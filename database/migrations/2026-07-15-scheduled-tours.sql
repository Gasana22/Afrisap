-- Adds an optional fixed departure date to tours, for the "Scheduled Tours"
-- feature: a scheduled tour is just a normal tour that also happens to run
-- on a specific date, rather than being bookable on any date the customer
-- picks (that's what bookings.travel_date is already for).

ALTER TABLE `tours`
  ADD COLUMN `scheduled_date` date DEFAULT NULL AFTER `days`;

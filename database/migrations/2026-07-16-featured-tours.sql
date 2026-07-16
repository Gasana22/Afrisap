-- Client feedback: "Add where to enable it to feature on Home page or
-- not" -- the homepage's "Featured Itineraries" section was just showing
-- the 6 most recently created published tours, with no way to choose
-- which ones. Same pattern as the earlier "Featured" flag added to
-- destinations (2026-07-15-featured-destinations.sql): an is_featured
-- flag, admin-toggleable per tour.

ALTER TABLE `tours`
  ADD COLUMN `is_featured` tinyint(1) NOT NULL DEFAULT 0 AFTER `status`;

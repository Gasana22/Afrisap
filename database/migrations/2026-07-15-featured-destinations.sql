-- Client feedback: the homepage's "National parks & game reserves" list
-- should only show parks the admin has picked, not just the most recent
-- ones -- there will eventually be too many destinations to show them all.
-- Adds an is_featured flag, admin-toggleable per destination.

ALTER TABLE `destinations`
  ADD COLUMN `is_featured` tinyint(1) NOT NULL DEFAULT 0 AFTER `additional_info`;

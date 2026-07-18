-- Client feedback (sketched on the Add/Edit Experience Tour form): a
-- "Feature in Home page" checkbox, same as Safari tours already have
-- (tours.is_featured), plus a matching "Featured Experiences" section
-- on the homepage, right after "Recently added safaris".

ALTER TABLE `experience_tours`
  ADD COLUMN `is_featured` tinyint(1) NOT NULL DEFAULT 0 AFTER `status`;

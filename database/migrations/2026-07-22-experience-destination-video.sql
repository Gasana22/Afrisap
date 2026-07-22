-- Client feedback: red box around the destination tiles on the Cultural
-- Experience listing page, "Videos that play from here, usually try using
-- Iframe for enabling it". Mirrors tours.video_url / experience_tours.video_url
-- -- an optional YouTube link, embedded via iframe on the destination's page.

ALTER TABLE `experience_destinations`
  ADD COLUMN `video_url` varchar(255) DEFAULT NULL AFTER `short_overview`;

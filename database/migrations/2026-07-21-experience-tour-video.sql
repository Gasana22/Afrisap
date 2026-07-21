-- Client feedback: "in every experience, we can have a short video of it
-- to play directly from the website even if its hosted in google [YouTube]".
-- A YouTube link per experience tour, embedded on its public page.

ALTER TABLE `experience_tours`
  ADD COLUMN `video_url` varchar(255) DEFAULT NULL AFTER `top_highlights`;

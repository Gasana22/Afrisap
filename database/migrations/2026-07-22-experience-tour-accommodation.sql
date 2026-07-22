-- Client feedback: "They are coming to live like us in uganda, meaning they
-- will have to sleep in the village with villagers in grass thatched houses.
-- they need to be put in those highlights of accommodation and its images."
-- Mirrors tours.hotel_info + the tour_hotel gallery for Experiential tours.

ALTER TABLE `experience_tours`
  ADD COLUMN `accommodation_info` text DEFAULT NULL AFTER `top_highlights`;

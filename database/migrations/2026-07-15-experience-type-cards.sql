-- Client feedback: the "Five ways into everyday Uganda" homepage tiles
-- (Cultural, Farm, Ghetto, Manufactural/Factory, Sports) should each carry
-- a photo and a short line of text instead of just an icon and a name.
-- experience_types had nowhere to store either, so this adds them.

ALTER TABLE `experience_types`
  ADD COLUMN `image_path` varchar(255) DEFAULT NULL AFTER `slug`,
  ADD COLUMN `short_description` varchar(200) DEFAULT NULL AFTER `image_path`;

UPDATE `experience_types` SET `short_description` = "Meet Uganda's tribes and traditions" WHERE `slug` = 'cultural-experience' AND `short_description` IS NULL;
UPDATE `experience_types` SET `short_description` = 'Hands-on visits to working farms' WHERE `slug` = 'farm-experience' AND `short_description` IS NULL;
UPDATE `experience_types` SET `short_description` = 'Behind the scenes on the factory floor' WHERE `slug` = 'manufactural-factory-experience' AND `short_description` IS NULL;
UPDATE `experience_types` SET `short_description` = 'Local matches, pitches and players' WHERE `slug` = 'sports-experience' AND `short_description` IS NULL;
UPDATE `experience_types` SET `short_description` = 'Real neighbourhood life, guided and safe' WHERE `slug` = 'ghetto-experience' AND `short_description` IS NULL;

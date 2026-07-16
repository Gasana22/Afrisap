-- Client feedback: "those one are both safaris and activities" -- Gorilla
-- Trekking, Chimpanzee Trekking, Birding and Game Drives already exist as
-- Safari Tour categories, but the client also wants them listed in the
-- Activities nav dropdown (which pulls straight from the `activities`
-- table, see includes/site_header.php's $nav_activities query), so a
-- visitor browsing by activity instead of by tour category can find them
-- too.

INSERT INTO `activities` (`name`)
SELECT 'Gorilla Trekking' WHERE NOT EXISTS (SELECT 1 FROM `activities` WHERE `name` = 'Gorilla Trekking');

INSERT INTO `activities` (`name`)
SELECT 'Chimpanzee Trekking' WHERE NOT EXISTS (SELECT 1 FROM `activities` WHERE `name` = 'Chimpanzee Trekking');

INSERT INTO `activities` (`name`)
SELECT 'Birding' WHERE NOT EXISTS (SELECT 1 FROM `activities` WHERE `name` = 'Birding');

INSERT INTO `activities` (`name`)
SELECT 'Game Drives' WHERE NOT EXISTS (SELECT 1 FROM `activities` WHERE `name` = 'Game Drives');

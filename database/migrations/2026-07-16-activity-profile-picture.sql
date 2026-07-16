-- Client feedback: activities need a distinct profile picture that appears
-- on the activity's own page, plus a gallery of at least 3-5 images. The
-- gallery already existed (get_media('activity', $id), used on activity.php
-- and manageable via admin/activities/manage.php) but there was no single
-- cover/profile image field, and the gallery link wasn't visible from the
-- Edit Activity screen the client was on -- both addressed in code.

ALTER TABLE `activities`
  ADD COLUMN `image_path` varchar(255) DEFAULT NULL AFTER `duration_hours`;

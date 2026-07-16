-- Client feedback: on the tour page's price/facts card, also show the tour
-- operator's "Office Location" (a physical office city/address, distinct
-- from the "Destination Country" field already captured, which is the
-- country the operator runs tours in, not necessarily where their office
-- sits).

ALTER TABLE `tour_operators`
  ADD COLUMN `office_location` varchar(255) DEFAULT NULL AFTER `contact_person`;

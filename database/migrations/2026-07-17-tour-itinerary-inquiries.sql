-- Client feedback: booking a tour directly from its itinerary/facts card
-- should ask for Name, Email, WhatsApp number, Number of visitors,
-- Country, Expected travel date, Message -- and on submit, save the
-- itinerary details shown on that card (Itinerary No., price, tour name,
-- tour operator in charge, budget type) alongside it, so admins see the
-- full context without clicking through. These land in their own
-- "Tour Itinerary Inquiries" Inbox section, separate from the existing
-- generic Bookings (which is shared with experience tours).

CREATE TABLE IF NOT EXISTS `tour_itinerary_inquiries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int unsigned DEFAULT NULL,
  `itinerary_no` varchar(20) DEFAULT NULL,
  `tour_title` varchar(200) NOT NULL,
  `tour_price` decimal(12,2) DEFAULT NULL,
  `operator_name` varchar(150) DEFAULT NULL,
  `budget_type` varchar(20) DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `whatsapp_number` varchar(50) DEFAULT NULL,
  `num_visitors` int unsigned NOT NULL DEFAULT 1,
  `country` varchar(100) DEFAULT NULL,
  `travel_date` date DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tour_itinerary_inquiries_tour` (`tour_id`),
  CONSTRAINT `fk_tour_itinerary_inquiries_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

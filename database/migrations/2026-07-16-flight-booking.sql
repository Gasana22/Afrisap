-- Client feedback: add a Flight Booking menu (Internal Flights, Chartered
-- Flights) with a request-a-quote page -- same shape as the Car Rental
-- feature added the day before: no pricing/booking engine yet, just a lead
-- capture the admin follows up on.

CREATE TABLE IF NOT EXISTS `flight_booking_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `flight_type` enum('Internal Flights','Chartered Flights') COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `travel_date` date DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `status` enum('new','contacted','closed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

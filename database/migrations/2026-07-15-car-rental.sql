-- Client feedback: add a Car Rental menu (Safari 4X4 Landcruiser, VANs,
-- Airport Transfer, Luxury Cars) with a request-a-quote page, same pattern
-- as the existing safari/experiential quote_requests -- no pricing/booking
-- engine yet, just a lead capture the admin follows up on.

CREATE TABLE IF NOT EXISTS `car_rental_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_type` enum('Safari 4X4 Landcruiser','VANs','Airport Transfer','Luxury Cars') COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `dropoff_date` date DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `status` enum('new','contacted','closed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

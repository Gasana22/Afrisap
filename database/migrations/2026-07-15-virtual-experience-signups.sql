-- "Virtual Experience" is flagged by the client themselves as coming soon --
-- a paid live-stream product needing its own streaming infrastructure, out
-- of scope for now. This table backs a lightweight interest waitlist
-- (name + email) so the client can start capturing demand today without
-- building payments/streaming.

CREATE TABLE IF NOT EXISTS `virtual_experience_signups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

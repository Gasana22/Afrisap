/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `duration_hours` decimal(5,2) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `full_description` text DEFAULT NULL,
  `operator_id` int(10) unsigned DEFAULT NULL,
  `destination_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_activities_operator` (`operator_id`),
  KEY `fk_activities_destination` (`destination_id`),
  CONSTRAINT `fk_activities_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_activities_operator` FOREIGN KEY (`operator_id`) REFERENCES `tour_operators` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','editor') NOT NULL DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','approved','rejected') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `cover_image_path` varchar(255) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bookable_type` enum('tour','experience_tour') NOT NULL,
  `bookable_id` int(10) unsigned NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `travel_date` date DEFAULT NULL,
  `num_people` int(10) unsigned NOT NULL DEFAULT 1,
  `message` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bookings_bookable` (`bookable_type`,`bookable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `career_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `career_applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `career_id` int(10) unsigned NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_career_applications_career` (`career_id`),
  CONSTRAINT `fk_career_applications_career` FOREIGN KEY (`career_id`) REFERENCES `careers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `careers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `careers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `posted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_page_parks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_page_parks` (
  `category_page_id` int(10) unsigned NOT NULL,
  `destination_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`category_page_id`,`destination_id`),
  KEY `fk_cpp_destination` (`destination_id`),
  CONSTRAINT `fk_cpp_category_page` FOREIGN KEY (`category_page_id`) REFERENCES `category_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpp_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `brief_overview` text DEFAULT NULL,
  `detailed_overview` text DEFAULT NULL,
  `highlights` text DEFAULT NULL,
  `when_to_visit` text DEFAULT NULL,
  `unique_about` text DEFAULT NULL,
  `more_activities` text DEFAULT NULL,
  `country_id` int(10) unsigned DEFAULT NULL,
  `gorilla_permit_info` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_pages_category` (`category_id`),
  KEY `fk_category_pages_country` (`country_id`),
  CONSTRAINT `fk_category_pages_category` FOREIGN KEY (`category_id`) REFERENCES `tour_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_category_pages_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_tour_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_tour_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_name` varchar(150) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `pax` int(10) unsigned NOT NULL DEFAULT 1,
  `days` int(10) unsigned NOT NULL,
  `budget_type` enum('Luxury','Mid-Range','Budget') NOT NULL DEFAULT 'Mid-Range',
  `destinations` text DEFAULT NULL,
  `activities` text DEFAULT NULL,
  `experience_types` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('new','contacted','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `destination_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `destination_activities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `destination_id` int(10) unsigned NOT NULL,
  `activity_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_destination_activities_destination` (`destination_id`),
  KEY `fk_destination_activities_activity` (`activity_id`),
  CONSTRAINT `fk_destination_activities_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_destination_activities_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `destination_animals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `destination_animals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `destination_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_destination_animals_destination` (`destination_id`),
  CONSTRAINT `fk_destination_animals_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `destination_birds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `destination_birds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `destination_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_destination_birds_destination` (`destination_id`),
  CONSTRAINT `fk_destination_birds_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `destinations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `overview` text DEFAULT NULL,
  `why_consider` text DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_destinations_country` (`country_id`),
  CONSTRAINT `fk_destinations_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `experience_destination_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_destination_activities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `experience_destination_id` int(10) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_eda_destination` (`experience_destination_id`),
  CONSTRAINT `fk_eda_destination` FOREIGN KEY (`experience_destination_id`) REFERENCES `experience_destinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `experience_destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_destinations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `experience_type_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `short_overview` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_experience_destinations_type` (`experience_type_id`),
  CONSTRAINT `fk_experience_destinations_type` FOREIGN KEY (`experience_type_id`) REFERENCES `experience_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `experience_tour_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_tour_activities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `experience_tour_id` int(10) unsigned NOT NULL,
  `source_activity_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_eta_tour` (`experience_tour_id`),
  KEY `fk_eta_source` (`source_activity_id`),
  CONSTRAINT `fk_eta_source` FOREIGN KEY (`source_activity_id`) REFERENCES `experience_destination_activities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_eta_tour` FOREIGN KEY (`experience_tour_id`) REFERENCES `experience_tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `experience_tour_destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_tour_destinations` (
  `experience_tour_id` int(10) unsigned NOT NULL,
  `experience_destination_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`experience_tour_id`,`experience_destination_id`),
  KEY `fk_etd_destination` (`experience_destination_id`),
  CONSTRAINT `fk_etd_destination` FOREIGN KEY (`experience_destination_id`) REFERENCES `experience_destinations` (`id`),
  CONSTRAINT `fk_etd_tour` FOREIGN KEY (`experience_tour_id`) REFERENCES `experience_tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `experience_tour_itinerary_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_tour_itinerary_days` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `experience_tour_id` int(10) unsigned NOT NULL,
  `day_number` int(10) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_experience_tour_itinerary_days_day` (`experience_tour_id`,`day_number`),
  CONSTRAINT `fk_experience_tour_itinerary_days_tour` FOREIGN KEY (`experience_tour_id`) REFERENCES `experience_tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `experience_tours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_tours` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `experience_type_id` int(10) unsigned NOT NULL,
  `provider_id` int(10) unsigned DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `days` int(10) unsigned NOT NULL,
  `min_pax` int(10) unsigned NOT NULL DEFAULT 1,
  `max_pax` int(10) unsigned NOT NULL,
  `short_overview` text DEFAULT NULL,
  `full_overview` text DEFAULT NULL,
  `top_highlights` text DEFAULT NULL,
  `accommodation_info` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_experience_tours_type` (`experience_type_id`),
  KEY `fk_experience_tours_provider` (`provider_id`),
  CONSTRAINT `fk_experience_tours_type` FOREIGN KEY (`experience_type_id`) REFERENCES `experience_types` (`id`),
  CONSTRAINT `fk_experience_tours_provider` FOREIGN KEY (`provider_id`) REFERENCES `service_providers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `experience_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `short_description` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(10) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_media_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(120) NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quote_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quote_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `quote_type` enum('safari','experiential') NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `status` enum('new','contacted','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `car_rental_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `car_rental_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_type` enum('Safari 4X4 Landcruiser','VANs','Airport Transfer','Luxury Cars') NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `dropoff_date` date DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flight_booking_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `flight_booking_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `flight_type` enum('Internal Flights','Chartered Flights') NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `travel_date` date DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_providers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(150) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `experience_type_id` int(10) unsigned NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_service_providers_type` (`experience_type_id`),
  CONSTRAINT `fk_service_providers_type` FOREIGN KEY (`experience_type_id`) REFERENCES `experience_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_settings` (
  `id` tinyint(3) unsigned NOT NULL,
  `hero_eyebrow` varchar(200) DEFAULT NULL,
  `hero_title` varchar(200) DEFAULT NULL,
  `hero_subtitle` text DEFAULT NULL,
  `hero_background_path` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tour_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_activities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `activity_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_tour_activities_tour` (`tour_id`),
  KEY `fk_tour_activities_activity` (`activity_id`),
  CONSTRAINT `fk_tour_activities_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tour_activities_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tour_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_group` enum('safari','trip','school','specialised') NOT NULL DEFAULT 'safari',
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tour_countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_countries` (
  `tour_id` int(10) unsigned NOT NULL,
  `country_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`tour_id`,`country_id`),
  KEY `fk_tour_countries_country` (`country_id`),
  CONSTRAINT `fk_tour_countries_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tour_countries_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tour_destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_destinations` (
  `tour_id` int(10) unsigned NOT NULL,
  `destination_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`tour_id`,`destination_id`),
  KEY `fk_tour_destinations_destination` (`destination_id`),
  CONSTRAINT `fk_tour_destinations_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`),
  CONSTRAINT `fk_tour_destinations_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tour_extra_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_extra_categories` (
  `tour_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`tour_id`,`category_id`),
  KEY `fk_tour_extra_categories_category` (`category_id`),
  CONSTRAINT `fk_tour_extra_categories_category` FOREIGN KEY (`category_id`) REFERENCES `tour_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tour_extra_categories_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tour_faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_faqs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_tour_faqs_tour` (`tour_id`),
  CONSTRAINT `fk_tour_faqs_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tour_itinerary_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_itinerary_days` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned NOT NULL,
  `day_number` int(10) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tour_itinerary_days_day` (`tour_id`,`day_number`),
  CONSTRAINT `fk_tour_itinerary_days_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tour_itinerary_inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_itinerary_inquiries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int(10) unsigned DEFAULT NULL,
  `itinerary_no` varchar(20) DEFAULT NULL,
  `tour_title` varchar(200) NOT NULL,
  `tour_price` decimal(12,2) DEFAULT NULL,
  `operator_name` varchar(150) DEFAULT NULL,
  `budget_type` varchar(20) DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `whatsapp_number` varchar(50) DEFAULT NULL,
  `num_visitors` int(10) unsigned NOT NULL DEFAULT 1,
  `country` varchar(100) DEFAULT NULL,
  `travel_date` date DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tour_itinerary_inquiries_tour` (`tour_id`),
  CONSTRAINT `fk_tour_itinerary_inquiries_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tour_itinerary_inquiry_addons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_itinerary_inquiry_addons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `inquiry_id` int(10) unsigned NOT NULL,
  `experience_tour_id` int(10) unsigned DEFAULT NULL,
  `experience_tour_title` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_itinerary_addons_inquiry` (`inquiry_id`),
  KEY `fk_itinerary_addons_experience_tour` (`experience_tour_id`),
  CONSTRAINT `fk_itinerary_addons_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `tour_itinerary_inquiries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_itinerary_addons_experience_tour` FOREIGN KEY (`experience_tour_id`) REFERENCES `experience_tours` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tour_operators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_operators` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(150) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `office_location` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `profile_image_path` varchar(255) DEFAULT NULL,
  `tour_type` varchar(150) DEFAULT NULL,
  `member_of` varchar(255) DEFAULT NULL,
  `trip_advisor_link` varchar(255) DEFAULT NULL,
  `years_experience` smallint(5) unsigned DEFAULT NULL,
  `country_id` int(10) unsigned DEFAULT NULL,
  `overview` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tour_operators_country` (`country_id`),
  CONSTRAINT `fk_tour_operators_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tours` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `budget_type` enum('Luxury','Mid-Range','Budget') NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `days` int(10) unsigned NOT NULL,
  `scheduled_date` date DEFAULT NULL,
  `min_pax` int(10) unsigned NOT NULL DEFAULT 1,
  `max_pax` int(10) unsigned NOT NULL,
  `short_overview` text DEFAULT NULL,
  `full_overview` text DEFAULT NULL,
  `top_highlights` text DEFAULT NULL,
  `hotel_info` text DEFAULT NULL,
  `vehicle_info` text DEFAULT NULL,
  `flight_info` text DEFAULT NULL,
  `includes` text DEFAULT NULL,
  `excludes` text DEFAULT NULL,
  `operator_id` int(10) unsigned DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tours_category` (`category_id`),
  KEY `fk_tours_operator` (`operator_id`),
  CONSTRAINT `fk_tours_category` FOREIGN KEY (`category_id`) REFERENCES `tour_categories` (`id`),
  CONSTRAINT `fk_tours_operator` FOREIGN KEY (`operator_id`) REFERENCES `tour_operators` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `virtual_experience_signups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `virtual_experience_signups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Starter data (categories, activities, experience types, sample destinations, default admin login) --

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
INSERT INTO `activities` VALUES
(1,'Skydiving',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(2,'White Water Rafting',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(3,'Horseback Riding',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(4,'Mountain Hiking',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(5,'Nature Walks',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(6,'Kayaking',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(7,'Boat Cruises',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(8,'Sport Fishing',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(9,'Bungee Jumping',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(10,'Cycling',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(11,'Quad Biking',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(12,'Gorilla Trekking',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-16 00:00:00','2026-07-16 00:00:00'),
(13,'Chimpanzee Trekking',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-16 00:00:00','2026-07-16 00:00:00'),
(14,'Birding',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-16 00:00:00','2026-07-16 00:00:00'),
(15,'Game Drives',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-16 00:00:00','2026-07-16 00:00:00');
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES
(1,'Mark Coulson','mark@safarisap.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','super_admin','2026-07-10 17:55:12','2026-07-10 18:38:43');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `agents` WRITE;
/*!40000 ALTER TABLE `agents` DISABLE KEYS */;
/*!40000 ALTER TABLE `agents` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `career_applications` WRITE;
/*!40000 ALTER TABLE `career_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `career_applications` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `careers` WRITE;
/*!40000 ALTER TABLE `careers` DISABLE KEYS */;
/*!40000 ALTER TABLE `careers` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `category_page_parks` WRITE;
/*!40000 ALTER TABLE `category_page_parks` DISABLE KEYS */;
/*!40000 ALTER TABLE `category_page_parks` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `category_pages` WRITE;
/*!40000 ALTER TABLE `category_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `category_pages` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES
(5,'Burundi'),
(7,'DR Congo'),
(2,'Kenya'),
(4,'Rwanda'),
(6,'South Sudan'),
(3,'Tanzania'),
(1,'Uganda');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `custom_tour_requests` WRITE;
/*!40000 ALTER TABLE `custom_tour_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `custom_tour_requests` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `destination_activities` WRITE;
/*!40000 ALTER TABLE `destination_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `destination_activities` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `destination_animals` WRITE;
/*!40000 ALTER TABLE `destination_animals` DISABLE KEYS */;
/*!40000 ALTER TABLE `destination_animals` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `destination_birds` WRITE;
/*!40000 ALTER TABLE `destination_birds` DISABLE KEYS */;
/*!40000 ALTER TABLE `destination_birds` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `destinations` WRITE;
/*!40000 ALTER TABLE `destinations` DISABLE KEYS */;
/*!40000 ALTER TABLE `destinations` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `experience_destination_activities` WRITE;
/*!40000 ALTER TABLE `experience_destination_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `experience_destination_activities` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `experience_destinations` WRITE;
/*!40000 ALTER TABLE `experience_destinations` DISABLE KEYS */;
INSERT INTO `experience_destinations` VALUES
(1,1,'Karamoja',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(2,1,'Buganda',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(3,1,'Teso',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(4,1,'Busoga',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(5,1,'Ankole',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(6,1,'Acholi',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(7,1,'Tooro',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(8,1,'Bugisu',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(9,1,'Alur',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(16,2,'Coffee Farm',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(17,2,'Cocoa Farm',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(18,2,'Tea Farm',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(19,2,'Banana Farm',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(20,2,'Cassava Farm',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(21,2,'Cattle Farm',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(22,2,'Fish Farming',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(23,2,'Cotton Farm',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(24,2,'Vegetable Farms',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(25,2,'Poultry Farm',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(31,4,'Football',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(32,4,'Swimming',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(33,4,'Cycling',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(34,4,'Marathons',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(35,4,'Basketball',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(36,4,'Beach Sports',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(37,4,'Walking',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(38,4,'Volleyball',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(39,4,'Golf',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(40,4,'Rugby',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(41,4,'Village Mud Wrestling',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(46,3,'Food Industries',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(47,3,'Textile Industries',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(48,3,'Motor Industries',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(49,3,'Construction Material Industries',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(50,3,'Agro Input Industries',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(53,5,'Katanga Ghetto',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(54,5,'Kamonkya',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(55,5,'Kalwere',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(56,5,'Bwaise',NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25');
/*!40000 ALTER TABLE `experience_destinations` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `experience_tour_activities` WRITE;
/*!40000 ALTER TABLE `experience_tour_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `experience_tour_activities` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `experience_tour_destinations` WRITE;
/*!40000 ALTER TABLE `experience_tour_destinations` DISABLE KEYS */;
/*!40000 ALTER TABLE `experience_tour_destinations` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `experience_tours` WRITE;
/*!40000 ALTER TABLE `experience_tours` DISABLE KEYS */;
/*!40000 ALTER TABLE `experience_tours` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `experience_types` WRITE;
/*!40000 ALTER TABLE `experience_types` DISABLE KEYS */;
INSERT INTO `experience_types` (`id`, `name`, `slug`, `short_description`) VALUES
(1,'Cultural Experience','cultural-experience',"Meet Uganda's tribes and traditions"),
(2,'Farm Experience','farm-experience','Hands-on visits to working farms'),
(3,'Manufactural/Factory Experience','manufactural-factory-experience','Behind the scenes on the factory floor'),
(4,'Sports Experience','sports-experience','Local matches, pitches and players'),
(5,'Ghetto Experience','ghetto-experience','Real neighbourhood life, guided and safe');
/*!40000 ALTER TABLE `experience_types` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES
(1,'about','About Safarisap','Safarisap connects travellers to East Africa on two tracks: safari tours into the region\'s national parks and game reserves, and experiential tours into the everyday life of the communities that call this region home.\n\nWe work with licensed, named tour operators and local service providers across Uganda, Kenya, Tanzania, Rwanda, Burundi, South Sudan and DR Congo, with branches in Kampala, Nairobi, Addis Ababa and London.','2026-07-10 12:07:25'),
(2,'uganda-travel-tips','Uganda Travel Tips','Visas: most visitors can apply for a Uganda e-visa online before travel.\n\nCurrency: the Ugandan Shilling (UGX) is the local currency; US Dollars are widely accepted for larger payments like gorilla permits and hotel bills.\n\nHealth: a yellow fever vaccination certificate is required on arrival, and antimalarial medication is strongly recommended.\n\nPacking: neutral-coloured clothing, a light rain jacket and sturdy walking shoes cover most itineraries, especially forest treks.','2026-07-10 12:07:25');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `quote_requests` WRITE;
/*!40000 ALTER TABLE `quote_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `quote_requests` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `service_providers` WRITE;
/*!40000 ALTER TABLE `service_providers` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_providers` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES
(1,NULL,NULL,NULL,NULL,'2026-07-14 10:24:38');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `tour_activities` WRITE;
/*!40000 ALTER TABLE `tour_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_activities` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `tour_categories` WRITE;
/*!40000 ALTER TABLE `tour_categories` DISABLE KEYS */;
INSERT INTO `tour_categories` VALUES
(1,'safari','Gorilla Trekking Safaris','gorilla-trekking-safaris',1),
(2,'safari','Chimpanzee Trekking Safaris','chimpanzee-trekking-safaris',2),
(3,'safari','Wildlife/Game Drives Safaris','wildlife-game-drives-safaris',3),
(4,'safari','Birding Safaris','birding-safaris',4),
(5,'safari','Mixed Safaris','mixed-safaris',5),
(6,'safari','Adventure Safaris','adventure-safaris',6),
(7,'safari','East Africa Combined Safaris','east-africa-combined-safaris',7),
(8,'trip','Island Trips','island-trips',1),
(9,'trip','Camping Trips','camping-trips',2),
(10,'trip','Flying Experience Trips','flying-experience-trips',3),
(11,'trip','Shopping Trips (East Africa)','shopping-trips',4),
(12,'trip','Boat Cruise Trips','boat-cruise-trips',5),
(13,'trip','Beach Trips (Lake Victoria)','beach-trips',6),
(14,'trip','City Trips (East Africa)','city-trips',7),
(15,'school','School Trips','school-trips',1),
(16,'specialised','Group Tours','group-tours',1);
/*!40000 ALTER TABLE `tour_categories` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `tour_destinations` WRITE;
/*!40000 ALTER TABLE `tour_destinations` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_destinations` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `tour_faqs` WRITE;
/*!40000 ALTER TABLE `tour_faqs` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_faqs` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `tour_operators` WRITE;
/*!40000 ALTER TABLE `tour_operators` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_operators` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `tours` WRITE;
/*!40000 ALTER TABLE `tours` DISABLE KEYS */;
/*!40000 ALTER TABLE `tours` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `virtual_experience_signups` WRITE;
/*!40000 ALTER TABLE `virtual_experience_signups` DISABLE KEYS */;
/*!40000 ALTER TABLE `virtual_experience_signups` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


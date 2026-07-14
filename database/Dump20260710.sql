-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: safarisap
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `duration_hours` decimal(5,2) DEFAULT NULL,
  `short_description` text COLLATE utf8mb4_general_ci,
  `full_description` text COLLATE utf8mb4_general_ci,
  `operator_id` int unsigned DEFAULT NULL,
  `destination_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_activities_operator` (`operator_id`),
  KEY `fk_activities_destination` (`destination_id`),
  CONSTRAINT `fk_activities_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_activities_operator` FOREIGN KEY (`operator_id`) REFERENCES `tour_operators` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
INSERT INTO `activities` VALUES (1,'Skydiving',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(2,'White Water Rafting',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(3,'Horseback Riding',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(4,'Mountain Hiking',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(5,'Nature Walks',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(6,'Kayaking',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(7,'Boat Cruises',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(8,'Sport Fishing',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(9,'Bungee Jumping',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(10,'Cycling',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(11,'Quad Biking',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25');
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('super_admin','editor') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'Mark Coulson','mark@safarisap.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','super_admin','2026-07-10 17:55:12','2026-07-10 18:38:43');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agents`
--

DROP TABLE IF EXISTS `agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `company_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `region` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `status` enum('new','approved','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agents`
--

LOCK TABLES `agents` WRITE;
/*!40000 ALTER TABLE `agents` DISABLE KEYS */;
/*!40000 ALTER TABLE `agents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(220) COLLATE utf8mb4_general_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_general_ci,
  `body` longtext COLLATE utf8mb4_general_ci,
  `cover_image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `author` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('draft','published') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bookable_type` enum('tour','experience_tour') COLLATE utf8mb4_general_ci NOT NULL,
  `bookable_id` int unsigned NOT NULL,
  `customer_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `customer_email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `customer_phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `travel_date` date DEFAULT NULL,
  `num_people` int unsigned NOT NULL DEFAULT '1',
  `message` text COLLATE utf8mb4_general_ci,
  `status` enum('pending','confirmed','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bookings_bookable` (`bookable_type`,`bookable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `career_applications`
--

DROP TABLE IF EXISTS `career_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `career_applications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `career_id` int unsigned NOT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cv_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cover_letter` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_career_applications_career` (`career_id`),
  CONSTRAINT `fk_career_applications_career` FOREIGN KEY (`career_id`) REFERENCES `careers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `career_applications`
--

LOCK TABLES `career_applications` WRITE;
/*!40000 ALTER TABLE `career_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `career_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `careers`
--

DROP TABLE IF EXISTS `careers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `careers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `location` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('open','closed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'open',
  `posted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `careers`
--

LOCK TABLES `careers` WRITE;
/*!40000 ALTER TABLE `careers` DISABLE KEYS */;
/*!40000 ALTER TABLE `careers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_page_parks`
--

DROP TABLE IF EXISTS `category_page_parks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_page_parks` (
  `category_page_id` int unsigned NOT NULL,
  `destination_id` int unsigned NOT NULL,
  PRIMARY KEY (`category_page_id`,`destination_id`),
  KEY `fk_cpp_destination` (`destination_id`),
  CONSTRAINT `fk_cpp_category_page` FOREIGN KEY (`category_page_id`) REFERENCES `category_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpp_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_page_parks`
--

LOCK TABLES `category_page_parks` WRITE;
/*!40000 ALTER TABLE `category_page_parks` DISABLE KEYS */;
/*!40000 ALTER TABLE `category_page_parks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category_pages`
--

DROP TABLE IF EXISTS `category_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_pages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `brief_overview` text COLLATE utf8mb4_general_ci,
  `detailed_overview` text COLLATE utf8mb4_general_ci,
  `highlights` text COLLATE utf8mb4_general_ci,
  `when_to_visit` text COLLATE utf8mb4_general_ci,
  `unique_about` text COLLATE utf8mb4_general_ci,
  `more_activities` text COLLATE utf8mb4_general_ci,
  `country_id` int unsigned DEFAULT NULL,
  `gorilla_permit_info` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_pages_category` (`category_id`),
  KEY `fk_category_pages_country` (`country_id`),
  CONSTRAINT `fk_category_pages_category` FOREIGN KEY (`category_id`) REFERENCES `tour_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_category_pages_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_pages`
--

LOCK TABLES `category_pages` WRITE;
/*!40000 ALTER TABLE `category_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `category_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('new','read','closed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (5,'Burundi'),(7,'DR Congo'),(2,'Kenya'),(4,'Rwanda'),(6,'South Sudan'),(3,'Tanzania'),(1,'Uganda');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destination_activities`
--

DROP TABLE IF EXISTS `destination_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `destination_activities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `destination_id` int unsigned NOT NULL,
  `activity_id` int unsigned DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_destination_activities_destination` (`destination_id`),
  KEY `fk_destination_activities_activity` (`activity_id`),
  CONSTRAINT `fk_destination_activities_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_destination_activities_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destination_activities`
--

LOCK TABLES `destination_activities` WRITE;
/*!40000 ALTER TABLE `destination_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `destination_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destination_animals`
--

DROP TABLE IF EXISTS `destination_animals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `destination_animals` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `destination_id` int unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_destination_animals_destination` (`destination_id`),
  CONSTRAINT `fk_destination_animals_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destination_animals`
--

LOCK TABLES `destination_animals` WRITE;
/*!40000 ALTER TABLE `destination_animals` DISABLE KEYS */;
/*!40000 ALTER TABLE `destination_animals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destination_birds`
--

DROP TABLE IF EXISTS `destination_birds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `destination_birds` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `destination_id` int unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_destination_birds_destination` (`destination_id`),
  CONSTRAINT `fk_destination_birds_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destination_birds`
--

LOCK TABLES `destination_birds` WRITE;
/*!40000 ALTER TABLE `destination_birds` DISABLE KEYS */;
/*!40000 ALTER TABLE `destination_birds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destinations`
--

DROP TABLE IF EXISTS `destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `destinations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `overview` text COLLATE utf8mb4_general_ci,
  `why_consider` text COLLATE utf8mb4_general_ci,
  `additional_info` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_destinations_country` (`country_id`),
  CONSTRAINT `fk_destinations_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destinations`
--

LOCK TABLES `destinations` WRITE;
/*!40000 ALTER TABLE `destinations` DISABLE KEYS */;
/*!40000 ALTER TABLE `destinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `experience_destination_activities`
--

DROP TABLE IF EXISTS `experience_destination_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_destination_activities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `experience_destination_id` int unsigned NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_eda_destination` (`experience_destination_id`),
  CONSTRAINT `fk_eda_destination` FOREIGN KEY (`experience_destination_id`) REFERENCES `experience_destinations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `experience_destination_activities`
--

LOCK TABLES `experience_destination_activities` WRITE;
/*!40000 ALTER TABLE `experience_destination_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `experience_destination_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `experience_destinations`
--

DROP TABLE IF EXISTS `experience_destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_destinations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `experience_type_id` int unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `short_overview` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_experience_destinations_type` (`experience_type_id`),
  CONSTRAINT `fk_experience_destinations_type` FOREIGN KEY (`experience_type_id`) REFERENCES `experience_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `experience_destinations`
--

LOCK TABLES `experience_destinations` WRITE;
/*!40000 ALTER TABLE `experience_destinations` DISABLE KEYS */;
INSERT INTO `experience_destinations` VALUES (1,1,'Karamoja',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(2,1,'Buganda',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(3,1,'Teso',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(4,1,'Busoga',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(5,1,'Ankole',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(6,1,'Acholi',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(7,1,'Tooro',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(8,1,'Bugisu',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(9,1,'Alur',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(16,2,'Coffee Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(17,2,'Cocoa Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(18,2,'Tea Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(19,2,'Banana Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(20,2,'Cassava Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(21,2,'Cattle Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(22,2,'Fish Farming',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(23,2,'Cotton Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(24,2,'Vegetable Farms',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(25,2,'Poultry Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(31,4,'Football',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(32,4,'Swimming',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(33,4,'Cycling',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(34,4,'Marathons',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(35,4,'Basketball',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(36,4,'Beach Sports',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(37,4,'Walking',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(38,4,'Volleyball',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(39,4,'Golf',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(40,4,'Rugby',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(41,4,'Village Mud Wrestling',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(46,3,'Food Industries',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(47,3,'Textile Industries',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(48,3,'Motor Industries',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(49,3,'Construction Material Industries',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(50,3,'Agro Input Industries',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(53,5,'Katanga Ghetto',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(54,5,'Kamonkya',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(55,5,'Kalwere',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),(56,5,'Bwaise',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25');
/*!40000 ALTER TABLE `experience_destinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `experience_tour_activities`
--

DROP TABLE IF EXISTS `experience_tour_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_tour_activities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `experience_tour_id` int unsigned NOT NULL,
  `source_activity_id` int unsigned DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_eta_tour` (`experience_tour_id`),
  KEY `fk_eta_source` (`source_activity_id`),
  CONSTRAINT `fk_eta_source` FOREIGN KEY (`source_activity_id`) REFERENCES `experience_destination_activities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_eta_tour` FOREIGN KEY (`experience_tour_id`) REFERENCES `experience_tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `experience_tour_activities`
--

LOCK TABLES `experience_tour_activities` WRITE;
/*!40000 ALTER TABLE `experience_tour_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `experience_tour_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `experience_tour_destinations`
--

DROP TABLE IF EXISTS `experience_tour_destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_tour_destinations` (
  `experience_tour_id` int unsigned NOT NULL,
  `experience_destination_id` int unsigned NOT NULL,
  PRIMARY KEY (`experience_tour_id`,`experience_destination_id`),
  KEY `fk_etd_destination` (`experience_destination_id`),
  CONSTRAINT `fk_etd_destination` FOREIGN KEY (`experience_destination_id`) REFERENCES `experience_destinations` (`id`),
  CONSTRAINT `fk_etd_tour` FOREIGN KEY (`experience_tour_id`) REFERENCES `experience_tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `experience_tour_destinations`
--

LOCK TABLES `experience_tour_destinations` WRITE;
/*!40000 ALTER TABLE `experience_tour_destinations` DISABLE KEYS */;
/*!40000 ALTER TABLE `experience_tour_destinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `experience_tours`
--

DROP TABLE IF EXISTS `experience_tours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_tours` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `experience_type_id` int unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `days` int unsigned NOT NULL,
  `min_pax` int unsigned NOT NULL DEFAULT '1',
  `max_pax` int unsigned NOT NULL,
  `short_overview` text COLLATE utf8mb4_general_ci,
  `full_overview` text COLLATE utf8mb4_general_ci,
  `top_highlights` text COLLATE utf8mb4_general_ci,
  `status` enum('draft','published') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_experience_tours_type` (`experience_type_id`),
  CONSTRAINT `fk_experience_tours_type` FOREIGN KEY (`experience_type_id`) REFERENCES `experience_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `experience_tours`
--

LOCK TABLES `experience_tours` WRITE;
/*!40000 ALTER TABLE `experience_tours` DISABLE KEYS */;
/*!40000 ALTER TABLE `experience_tours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `experience_types`
--

DROP TABLE IF EXISTS `experience_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `experience_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `experience_types`
--

LOCK TABLES `experience_types` WRITE;
/*!40000 ALTER TABLE `experience_types` DISABLE KEYS */;
INSERT INTO `experience_types` VALUES (1,'Cultural Experience','cultural-experience'),(2,'Farm Experience','farm-experience'),(3,'Manufactural/Factory Experience','manufactural-factory-experience'),(4,'Sports Experience','sports-experience'),(5,'Ghetto Experience','ghetto-experience');
/*!40000 ALTER TABLE `experience_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `entity_id` int unsigned NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `caption` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'about','About Safarisap','Safarisap connects travellers to East Africa on two tracks: safari tours into the region\'s national parks and game reserves, and experiential tours into the everyday life of the communities that call this region home.\n\nWe work with licensed, named tour operators and local service providers across Uganda, Kenya, Tanzania, Rwanda, Burundi, South Sudan and DR Congo, with branches in Kampala, Nairobi, Addis Ababa and London.','2026-07-10 12:07:25'),(2,'uganda-travel-tips','Uganda Travel Tips','Visas: most visitors can apply for a Uganda e-visa online before travel.\n\nCurrency: the Ugandan Shilling (UGX) is the local currency; US Dollars are widely accepted for larger payments like gorilla permits and hotel bills.\n\nHealth: a yellow fever vaccination certificate is required on arrival, and antimalarial medication is strongly recommended.\n\nPacking: neutral-coloured clothing, a light rain jacket and sturdy walking shoes cover most itineraries, especially forest treks.','2026-07-10 12:07:25');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quote_requests`
--

DROP TABLE IF EXISTS `quote_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quote_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `quote_type` enum('safari','experiential') COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `status` enum('new','contacted','closed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quote_requests`
--

LOCK TABLES `quote_requests` WRITE;
/*!40000 ALTER TABLE `quote_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `quote_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_providers`
--

DROP TABLE IF EXISTS `service_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_providers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `region` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `experience_type_id` int unsigned NOT NULL,
  `contact_person` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_service_providers_type` (`experience_type_id`),
  CONSTRAINT `fk_service_providers_type` FOREIGN KEY (`experience_type_id`) REFERENCES `experience_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_providers`
--

LOCK TABLES `service_providers` WRITE;
/*!40000 ALTER TABLE `service_providers` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tour_activities`
--

DROP TABLE IF EXISTS `tour_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_activities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int unsigned NOT NULL,
  `activity_id` int unsigned DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_tour_activities_tour` (`tour_id`),
  KEY `fk_tour_activities_activity` (`activity_id`),
  CONSTRAINT `fk_tour_activities_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tour_activities_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tour_activities`
--

LOCK TABLES `tour_activities` WRITE;
/*!40000 ALTER TABLE `tour_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tour_categories`
--

DROP TABLE IF EXISTS `tour_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `menu_group` enum('safari','trip','school') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'safari',
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tour_categories`
--

LOCK TABLES `tour_categories` WRITE;
/*!40000 ALTER TABLE `tour_categories` DISABLE KEYS */;
INSERT INTO `tour_categories` VALUES (1,'safari','Gorilla Trekking Safaris','gorilla-trekking-safaris',1),(2,'safari','Chimpanzee Trekking Safaris','chimpanzee-trekking-safaris',2),(3,'safari','Wildlife/Game Drives Safaris','wildlife-game-drives-safaris',3),(4,'safari','Birding Safaris','birding-safaris',4),(5,'safari','Mixed Safaris','mixed-safaris',5),(6,'safari','Adventure Safaris','adventure-safaris',6),(7,'safari','East Africa Combined Safaris','east-africa-combined-safaris',7),(8,'trip','Island Trips','island-trips',1),(9,'trip','Camping Trips','camping-trips',2),(10,'trip','Flying Experience Trips','flying-experience-trips',3),(11,'trip','Shopping Trips (East Africa)','shopping-trips',4),(12,'trip','Boat Cruise Trips','boat-cruise-trips',5),(13,'trip','Beach Trips (Lake Victoria)','beach-trips',6),(14,'trip','City Trips (East Africa)','city-trips',7),(15,'school','School Trips','school-trips',1);
/*!40000 ALTER TABLE `tour_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tour_destinations`
--

DROP TABLE IF EXISTS `tour_destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_destinations` (
  `tour_id` int unsigned NOT NULL,
  `destination_id` int unsigned NOT NULL,
  PRIMARY KEY (`tour_id`,`destination_id`),
  KEY `fk_tour_destinations_destination` (`destination_id`),
  CONSTRAINT `fk_tour_destinations_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`),
  CONSTRAINT `fk_tour_destinations_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tour_destinations`
--

LOCK TABLES `tour_destinations` WRITE;
/*!40000 ALTER TABLE `tour_destinations` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_destinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tour_faqs`
--

DROP TABLE IF EXISTS `tour_faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_faqs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` int unsigned NOT NULL,
  `question` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `answer` text COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_tour_faqs_tour` (`tour_id`),
  CONSTRAINT `fk_tour_faqs_tour` FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tour_faqs`
--

LOCK TABLES `tour_faqs` WRITE;
/*!40000 ALTER TABLE `tour_faqs` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tour_operators`
--

DROP TABLE IF EXISTS `tour_operators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tour_operators` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tour_operators`
--

LOCK TABLES `tour_operators` WRITE;
/*!40000 ALTER TABLE `tour_operators` DISABLE KEYS */;
/*!40000 ALTER TABLE `tour_operators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tours`
--

DROP TABLE IF EXISTS `tours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tours` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int unsigned NOT NULL,
  `budget_type` enum('Luxury','Mid-Range','Budget') COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `days` int unsigned NOT NULL,
  `min_pax` int unsigned NOT NULL DEFAULT '1',
  `max_pax` int unsigned NOT NULL,
  `short_overview` text COLLATE utf8mb4_general_ci,
  `full_overview` text COLLATE utf8mb4_general_ci,
  `top_highlights` text COLLATE utf8mb4_general_ci,
  `hotel_info` text COLLATE utf8mb4_general_ci,
  `vehicle_info` text COLLATE utf8mb4_general_ci,
  `flight_info` text COLLATE utf8mb4_general_ci,
  `includes` text COLLATE utf8mb4_general_ci,
  `excludes` text COLLATE utf8mb4_general_ci,
  `operator_id` int unsigned DEFAULT NULL,
  `status` enum('draft','published') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_tours_category` (`category_id`),
  KEY `fk_tours_operator` (`operator_id`),
  CONSTRAINT `fk_tours_category` FOREIGN KEY (`category_id`) REFERENCES `tour_categories` (`id`),
  CONSTRAINT `fk_tours_operator` FOREIGN KEY (`operator_id`) REFERENCES `tour_operators` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tours`
--

LOCK TABLES `tours` WRITE;
/*!40000 ALTER TABLE `tours` DISABLE KEYS */;
/*!40000 ALTER TABLE `tours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'safarisap'
--

--
-- Dumping routines for database 'safarisap'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-10 21:47:40

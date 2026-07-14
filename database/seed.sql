/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
INSERT INTO `activities` VALUES
(1,'Skydiving',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(2,'White Water Rafting',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(3,'Horseback Riding',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(4,'Mountain Hiking',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(5,'Nature Walks',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(6,'Kayaking',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(7,'Boat Cruises',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(8,'Sport Fishing',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(9,'Bungee Jumping',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(10,'Cycling',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(11,'Quad Biking',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25');
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
(1,1,'Karamoja',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(2,1,'Buganda',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(3,1,'Teso',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(4,1,'Busoga',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(5,1,'Ankole',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(6,1,'Acholi',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(7,1,'Tooro',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(8,1,'Bugisu',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(9,1,'Alur',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(16,2,'Coffee Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(17,2,'Cocoa Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(18,2,'Tea Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(19,2,'Banana Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(20,2,'Cassava Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(21,2,'Cattle Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(22,2,'Fish Farming',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(23,2,'Cotton Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(24,2,'Vegetable Farms',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(25,2,'Poultry Farm',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(31,4,'Football',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(32,4,'Swimming',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(33,4,'Cycling',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(34,4,'Marathons',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(35,4,'Basketball',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(36,4,'Beach Sports',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(37,4,'Walking',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(38,4,'Volleyball',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(39,4,'Golf',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(40,4,'Rugby',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(41,4,'Village Mud Wrestling',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(46,3,'Food Industries',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(47,3,'Textile Industries',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(48,3,'Motor Industries',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(49,3,'Construction Material Industries',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(50,3,'Agro Input Industries',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(53,5,'Katanga Ghetto',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(54,5,'Kamonkya',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(55,5,'Kalwere',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25'),
(56,5,'Bwaise',NULL,NULL,'2026-07-10 12:07:25','2026-07-10 12:07:25');
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
INSERT INTO `experience_types` VALUES
(1,'Cultural Experience','cultural-experience'),
(2,'Farm Experience','farm-experience'),
(3,'Manufactural/Factory Experience','manufactural-factory-experience'),
(4,'Sports Experience','sports-experience'),
(5,'Ghetto Experience','ghetto-experience');
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
(1,NULL,NULL,NULL,NULL,'2026-07-14 10:12:50');
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
(15,'school','School Trips','school-trips',1);
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


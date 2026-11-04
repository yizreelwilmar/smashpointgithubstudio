-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: powershoot_db
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `assigned_session_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','$2y$10$x2ItN8SwCQnUQ2ymlhF5GuVp.3WumjzPvc1dssMi7PtV0QLvzMGqu','Captain Powershoot',NULL,'admin',NULL,'2026-11-02 05:31:07'),(3,'pic_yiz','$2y$10$kAWv.iCa.gO4tk18eRBmbuTqq42yPj965CmLwoaPjPwiVs5mfpXcy','Yizreel','082125051665','pic',9,'2026-11-03 06:11:07');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `articles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(220) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pengumuman',
  `excerpt` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `views_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `articles`
--

LOCK TABLES `articles` WRITE;
/*!40000 ALTER TABLE `articles` DISABLE KEYS */;
INSERT INTO `articles` VALUES (1,'Tata Tertib Mabar & Standar Shuttlecock Powershoot 2026','tata-tertib-mabar-standar-shuttlecock-2026','Aturan Lapangan','Wajib sepatu sol karet non-marking, shuttlecock Speed 77 bulu angsa, dan konfirmasi kehadiran H-4 jam.','Demi kenyamanan bersama seluruh anggota komunitas dalam setiap sesi mabar mingguan:\n\n1. **Sepatu Lapangan:** Wajib menggunakan sepatu khusus badminton dengan outsole karet non-marking demi menjaga karpet lapangan dan mencegah cedera lutut.\n2. **Shuttlecock Resmi:** Menggunakan shuttlecock bulu angsa resmi Speed 77 yang telah teruji kestabilan putarannya.\n3. **Pembayaran Iuran:** Harap melunasi iuran kok via QRIS sebelum sesi dimulai atau tunai kepada PIC di lokasi GOR.','https://images.unsplash.com/photo-1613918108466-292b78a8ef95?auto=format&fit=crop&w=1200&q=80',1,0,'2026-11-02 05:31:07'),(2,'Sistem Akumulasi Poin Leaderboard: Mengapa Setiap Poin Lapangan Berharga','sistem-akumulasi-poin-leaderboard','Sistem Poin','Di Powershoot, peringkat tidak hanya dihitung dari menang/kalah, melainkan akumulasi poin nyata yang dicetak di setiap game!','Dalam sistem Powershoot Badminton Management, setiap poin yang kamu cetak dalam pertandingan langsung diakumulasikan ke profilmu.\n\nMeskipun kalah dengan skor tipis 19-21, kamu tetap mendapatkan 19 poin untuk mendongkrak posisimu di Leaderboard Hall of Fame Komunitas. Jadi, jangan pernah menyerah di setiap reli shuttlecock!','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80',1,0,'2026-11-02 05:31:07');
/*!40000 ALTER TABLE `articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financial_records`
--

DROP TABLE IF EXISTS `financial_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int DEFAULT NULL,
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'expense',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int NOT NULL DEFAULT '0',
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `record_date` date NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fin_session` (`session_id`),
  KEY `idx_fin_date` (`record_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_records`
--

LOCK TABLES `financial_records` WRITE;
/*!40000 ALTER TABLE `financial_records` DISABLE KEYS */;
INSERT INTO `financial_records` VALUES (1,9,'expense','Sewa Lapangan',240000,'Sewa 3 Lapangan x 2 Jam (Main Court)','Transfer ke Pengelola GOR Senen','2026-11-07',NULL,'2026-11-03 09:28:17'),(2,9,'expense','Shuttlecock',150000,'1 Slop Kok Bulu Angsa JP Gold Speed 77','Stok kok mabar sesi 07 Nov','2026-11-07',NULL,'2026-11-03 09:28:17'),(3,9,'expense','Konsumsi',25000,'1 Dus Air Mineral Le Minerale 600ml','Untuk hidrasi peserta di courtside','2026-11-07',NULL,'2026-11-03 09:28:17');
/*!40000 ALTER TABLE `financial_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery`
--

DROP TABLE IF EXISTS `gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gallery` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `activity_date` date DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery`
--

LOCK TABLES `gallery` WRITE;
/*!40000 ALTER TABLE `gallery` DISABLE KEYS */;
/*!40000 ALTER TABLE `gallery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matches`
--

DROP TABLE IF EXISTS `matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `round_number` int NOT NULL DEFAULT '1',
  `court_number` int NOT NULL DEFAULT '1',
  `team_a_p1` int NOT NULL,
  `team_a_p2` int NOT NULL,
  `team_b_p1` int NOT NULL,
  `team_b_p2` int NOT NULL,
  `score_a` int NOT NULL DEFAULT '0',
  `score_b` int NOT NULL DEFAULT '0',
  `winner_team` enum('team_a','team_b','draw','none') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `status` enum('pending','playing','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `team_a_p1` (`team_a_p1`),
  KEY `team_a_p2` (`team_a_p2`),
  KEY `team_b_p1` (`team_b_p1`),
  KEY `team_b_p2` (`team_b_p2`),
  KEY `idx_matches_session_round` (`session_id`,`round_number`),
  CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`team_a_p1`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`team_a_p2`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matches_ibfk_4` FOREIGN KEY (`team_b_p1`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matches_ibfk_5` FOREIGN KEY (`team_b_p2`) REFERENCES `players` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matches`
--

LOCK TABLES `matches` WRITE;
/*!40000 ALTER TABLE `matches` DISABLE KEYS */;
/*!40000 ALTER TABLE `matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `players` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` enum('Beginner','Intermediate','Advance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Intermediate',
  `total_matches` int NOT NULL DEFAULT '0',
  `wins` int NOT NULL DEFAULT '0',
  `losses` int NOT NULL DEFAULT '0',
  `total_points` int NOT NULL DEFAULT '0',
  `points_conceded` int NOT NULL DEFAULT '0',
  `streak` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_players_points` (`total_points`),
  KEY `idx_players_wins` (`wins`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `players`
--

LOCK TABLES `players` WRITE;
/*!40000 ALTER TABLE `players` DISABLE KEYS */;
INSERT INTO `players` VALUES (37,'Dori',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:12:14','2026-11-03 06:12:14'),(38,'alv',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:12:14','2026-11-03 06:12:14'),(39,'Wikan',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:12:14','2026-11-03 06:12:14'),(40,'hilmy',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:12:14','2026-11-03 06:12:14'),(41,'fadil',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:12:14','2026-11-03 06:12:14'),(42,'ali',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:12:14','2026-11-03 06:12:14'),(43,'Setiawan',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:12:14','2026-11-03 06:12:14'),(44,'Tr',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:12:14','2026-11-03 06:12:14'),(45,'11',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:36:28','2026-11-03 06:36:28'),(46,'1231',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:36:34','2026-11-03 06:36:34'),(47,'123123',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:36:46','2026-11-03 06:36:46'),(48,'123123',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:36:50','2026-11-03 06:36:50'),(49,'12312312312312',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:36:57','2026-11-03 06:36:57'),(50,'12312312312312',NULL,'Intermediate',0,0,0,0,0,0,1,'2026-11-03 06:36:58','2026-11-03 06:36:58');
/*!40000 ALTER TABLE `players` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day_name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_area` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Jakarta Selatan',
  `schedule_date` date NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `venue_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `venue_maps_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `court_count` int NOT NULL DEFAULT '2',
  `fee_per_person` int NOT NULL DEFAULT '35000',
  `max_slots` int NOT NULL DEFAULT '16',
  `pic_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Admin Powershoot',
  `pic_whatsapp` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '081299887766',
  `status` enum('open','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `completed_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `facilities` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `venue_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'BCA - 3350280374 - Dewi Putri',
  `host_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Lina / Juni',
  `cancellation_rules` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'CANCEL MAX JAM 14.00 DI HARI-H, LEBIH DARI ITU TETAP BAYAR FULL ATAU CARI PENGGANTI PLISS‼️',
  `court_desc` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Lapangan 1 - 3',
  PRIMARY KEY (`id`),
  KEY `idx_schedules_date` (`schedule_date`),
  KEY `idx_schedules_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (9,'Main Court Badminton','Sabtu Malam','Senen','2026-11-07','20:00:00','22:00:00','Main Court Badminton','https://www.google.com/maps/search/?api=1&query=Plaza%20Kenari%20Mas%20Jalan%20Kramat%20Raya%20No.%20101%2C%20RW%2007%2C%20Senen%2C%20Jakarta%2C%20Java',3,40000,24,'Yizreel','082125051665','in_progress',NULL,'','2026-11-03 06:08:38','2026-11-03 06:41:55','JP Gold','Jalan Kramat Raya No. 101, RW 07, Senen, Jakarta, Java','BCA 1234567890 a.n Bendahara','Yizreel','Konfirmasi pembatalan slot maksimal H-1 atau cari pemain pengganti.','Lapangan 1 - 3');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session_participants`
--

DROP TABLE IF EXISTS `session_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_participants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `player_id` int NOT NULL,
  `payment_status` enum('unpaid','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `payment_amount` int NOT NULL DEFAULT '35000',
  `paid_at` datetime DEFAULT NULL,
  `is_present` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('confirmed','waiting_list') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'confirmed',
  `bench_count` int NOT NULL DEFAULT '0',
  `notes` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_session_player` (`session_id`,`player_id`),
  KEY `player_id` (`player_id`),
  KEY `idx_participants_payment` (`session_id`,`payment_status`),
  KEY `idx_sess_status` (`session_id`,`status`),
  CONSTRAINT `session_participants_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `session_participants_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session_participants`
--

LOCK TABLES `session_participants` WRITE;
/*!40000 ALTER TABLE `session_participants` DISABLE KEYS */;
INSERT INTO `session_participants` VALUES (86,9,37,'paid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:12:14'),(87,9,38,'paid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:12:14'),(88,9,39,'unpaid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:12:14'),(89,9,40,'paid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:12:14'),(90,9,41,'paid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:12:14'),(91,9,42,'unpaid',40000,'2026-11-03 13:22:16',1,'confirmed',0,NULL,'2026-11-03 06:12:14'),(92,9,43,'unpaid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:12:14'),(94,9,45,'unpaid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:36:28'),(95,9,46,'unpaid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:36:34'),(96,9,47,'unpaid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:36:46'),(97,9,48,'unpaid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:36:50'),(98,9,49,'unpaid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:36:57'),(99,9,50,'unpaid',40000,NULL,1,'confirmed',0,NULL,'2026-11-03 06:36:58');
/*!40000 ALTER TABLE `session_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `community_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tagline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `instagram` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_default` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Jakarta Selatan',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `focus_keyword` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keywords` text COLLATE utf8mb4_unicode_ci,
  `og_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_description` text COLLATE utf8mb4_unicode_ci,
  `og_image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canonical_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `robots_meta` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'index, follow',
  `established_year` int NOT NULL DEFAULT '2023',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `hero_title` text COLLATE utf8mb4_unicode_ci,
  `hero_subtitle` text COLLATE utf8mb4_unicode_ci,
  `hero_cta_text` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT 'Pilih Jadwal & Daftar via WhatsApp PIC →',
  `announcement_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Komunitas Badminton Resmi Jakarta Selatan (Est. 2023)',
  `announcement_subtext` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT 'Slot Dibuka Setiap Pekan',
  `step_1_title` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT 'Pilih Jadwal & Hubungi PIC WA',
  `step_1_desc` text COLLATE utf8mb4_unicode_ci,
  `step_2_title` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT 'Konfirmasi & Selesaikan Iuran',
  `step_2_desc` text COLLATE utf8mb4_unicode_ci,
  `step_3_title` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT 'Datang & Main Sesuai Rotasi Otomatis',
  `step_3_desc` text COLLATE utf8mb4_unicode_ci,
  `faq_json` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'Powershoot Badminton Club','/smashpoint/public/uploads/powershoot_logo.jpg','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=800&q=80','Komunitas Badminton Kompetitif & Mabar Terorganisir Tanpa Catat Manual','Powershoot Badminton adalah komunitas bulutangkis aktif di Jakarta Selatan yang mengadakan sesi main bareng 3-4 kali seminggu di berbagai GOR terbaik. Dilengkapi sistem rotasi lapangan otomatis, akumulasi poin leaderboard, dan manajemen iuran transparan.','@powershoot.badminton','081299887766','Jakarta',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'index, follow',2023,'2026-11-02 05:31:07','Main Badminton Bareng Tanpa Repot Cari Lawan & Lapangan.','Selamat datang di Powershoot Badminton Club. Kami menyelenggarakan sesi main bareng ganda 4 kali setiap pekan di berbagai GOR terbaik Jakarta Selatan. Cukup daftar ke PIC WhatsApp, datang ke arena, dan nikmati pertandingan dengan pembagian giliran main yang teratur.','Pilih Jadwal & Daftar via WhatsApp PIC →','Komunitas Badminton Resmi Jakarta Selatan (Est. 2023)','','Pilih Jadwal & Hubungi PIC WA','Cek jadwal mabar di GOR terdekat, slot kuota tersisa, lalu klik tombol pendaftaran WhatsApp PIC untuk konfirmasi kehadiran.','Konfirmasi & Selesaikan Iuran','PIC sesi akan memverifikasi pendaftaran Anda. Cukup selesaikan pembayaran via transfer/QRIS atau tunai di lokasi mabar.','Datang & Main Sesuai Rotasi Otomatis','Cukup bawa raket dan hadir tepat waktu. Shuttlecock bulu angsa resmi berkualitas sudah kami sediakan di lapangan.','[{\"question\":\"Apakah pemain pemula (beginner) boleh ikut bergabung?\",\"answer\":\"Sangat boleh! Komunitas kami terbuka untuk semua tingkatan dari Beginner, Intermediate, hingga Advance. Sistem rotasi digital kami akan membagi pasangan dan lawan secara berimbang agar reli tetap menyenangkan bagi semua orang.\"},{\"question\":\"Bagaimana jika saya mendaftar sendirian tanpa pasangan ganda?\",\"answer\":\"Mayoritas peserta kami mendaftar secara individu! Di lapangan GOR, Anda akan dipasangkan secara bergantian dengan peserta lain oleh sistem rotasi otomatis, sehingga Anda pasti mendapat partner di setiap ronde.\"},{\"question\":\"Apakah saya perlu membawa shuttlecock sendiri?\",\"answer\":\"Tidak perlu. Seluruh biaya iuran mabar sudah mencakup penyediaan shuttlecock bulu angsa resmi berkualitas yang disediakan panitia. Anda hanya perlu membawa raket dan mengenakan sepatu badminton non-marking.\"},{\"question\":\"Bagaimana alur pembayaran iuran mabar?\",\"answer\":\"Pembayaran iuran dapat dilakukan secara cashless via QRIS\\/Transfer bank ke rekening komunitas atau tunai langsung kepada PIC sesi saat tiba di venue lapangan GOR.\"},{\"question\":\"Bagaimana jika saya berhalangan hadir setelah mendaftar?\",\"answer\":\"Mohon segera memberitahu PIC sesi WhatsApp minimal 4 jam sebelum jadwal sesi dimulai agar kuota Anda dapat dialihkan ke peserta waiting list.\"}]');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-03 16:40:04

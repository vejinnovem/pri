-- MySQL dump 10.13  Distrib 8.0.45, for Linux (aarch64)
--
-- Host: localhost    Database: pressreset_inventory_alpha
-- ------------------------------------------------------
-- Server version	8.0.45-0ubuntu0.24.04.1

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
-- Current Database: `pressreset_inventory_alpha`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `pressreset_inventory_alpha` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `pressreset_inventory_alpha`;

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_settings` (
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_settings`
--

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
INSERT INTO `app_settings` VALUES ('festival_deadline','2027-09-01T00:00:00+02:00','2026-03-11 13:59:58'),('threshold_needs_service_danger','20','2026-03-11 13:59:58'),('threshold_needs_service_warning','5','2026-03-11 13:59:58'),('threshold_needs_service_warning_high','10','2026-03-11 13:59:58'),('threshold_open_items_danger','20','2026-03-11 13:59:58'),('threshold_open_items_warning','5','2026-03-11 13:59:58'),('threshold_open_items_warning_high','10','2026-03-11 13:59:58'),('threshold_open_tasks_danger','40','2026-03-11 13:59:58'),('threshold_open_tasks_warning','10','2026-03-11 13:59:58'),('threshold_open_tasks_warning_high','20','2026-03-11 13:59:58');
/*!40000 ALTER TABLE `app_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` int unsigned NOT NULL,
  `action_name` varchar(80) NOT NULL,
  `details_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_audit_logs_user` (`user_id`),
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
-- Audit data intentionally omitted from the release snapshot.
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `code_prefix` varchar(3) NOT NULL DEFAULT 'GEN',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=361 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Komputery','komputery','CMP','2026-03-11 12:22:23'),(2,'Konsole','konsole','CON','2026-03-11 12:22:23'),(3,'Monitory LCD','monitory-lcd','MLC','2026-03-11 12:22:23'),(208,'Monitory CRT','monitory-crt','MCR','2026-03-11 15:01:24'),(215,'TV CRT','tv-crt','TVC','2026-03-11 15:01:28'),(222,'TV LCD','tv-lcd','TVL','2026-03-11 15:01:32');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `condition_statuses`
--

DROP TABLE IF EXISTS `condition_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `condition_statuses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=581 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `condition_statuses`
--

LOCK TABLES `condition_statuses` WRITE;
/*!40000 ALTER TABLE `condition_statuses` DISABLE KEYS */;
INSERT INTO `condition_statuses` VALUES (1,'Nowy wpis','new',10,'2026-03-11 14:48:09'),(2,'W inwentaryzacji','inventory',20,'2026-03-11 14:48:09'),(3,'Uszkodzony','uszkodzony',30,'2026-03-11 14:48:09'),(4,'Przypisany','assigned',40,'2026-03-11 14:48:09'),(5,'Zarchiwizowany','archived',50,'2026-03-11 14:48:09'),(273,'Wymaga serwisu','needs_service',30,'2026-03-11 15:00:36');
/*!40000 ALTER TABLE `condition_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `parent_equipment_id` int unsigned DEFAULT NULL,
  `location_id` int unsigned DEFAULT NULL,
  `location_place_id` int unsigned DEFAULT NULL,
  `inventory_code` varchar(60) NOT NULL,
  `inventory_code_manual_override` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(180) NOT NULL,
  `manufacturer` varchar(120) NOT NULL,
  `model` varchar(120) NOT NULL,
  `production_year` varchar(20) NOT NULL DEFAULT '',
  `condition_status` varchar(120) NOT NULL DEFAULT 'inventory',
  `ownership_status` varchar(120) NOT NULL DEFAULT 'unknown',
  `location_text` varchar(180) NOT NULL DEFAULT '',
  `barcode_value` varchar(120) NOT NULL DEFAULT '',
  `qr_token` varchar(80) NOT NULL,
  `notes` text,
  `created_by` int unsigned NOT NULL,
  `updated_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_code` (`inventory_code`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `fk_equipment_category` (`category_id`),
  KEY `fk_equipment_parent` (`parent_equipment_id`),
  KEY `fk_equipment_created_by` (`created_by`),
  KEY `fk_equipment_updated_by` (`updated_by`),
  KEY `fk_equipment_location` (`location_id`),
  KEY `fk_equipment_location_place` (`location_place_id`),
  CONSTRAINT `fk_equipment_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_equipment_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_equipment_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_equipment_location_place` FOREIGN KEY (`location_place_id`) REFERENCES `location_places` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_equipment_parent` FOREIGN KEY (`parent_equipment_id`) REFERENCES `equipment` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_equipment_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment`
--

LOCK TABLES `equipment` WRITE;
/*!40000 ALTER TABLE `equipment` DISABLE KEYS */;
INSERT INTO `equipment` VALUES (1,1,NULL,1,1,'PR-CMP-0001',0,'Amiga 500','Commodore','Amiga 500','1989','inventory','foundation','Dom Mariusza A / Skrzynka AA','590000000001','pr-cmp-0001','Placeholder alpha: jednostka centralna do testow katalogowania.',1,1,'2026-03-11 12:22:23','2026-03-11 14:59:05'),(2,2,NULL,1,2,'PR-CON-0001',0,'Sega Mega Drive z padem','Sega','Mega Drive II','1993','uszkodzony','pending','Dom Mariusza A / Skrzynka B','590000000002','pr-con-0001','Placeholder alpha: konsola do testow relacji z akcesoriami.',1,1,'2026-03-11 12:22:23','2026-03-11 15:01:02'),(3,3,NULL,2,3,'PR-MLC-0001',0,'Monitor CRT 14 cali','Sony','Trinitron KV-14','1998','uszkodzony','unknown','Dom Cyjanka / Strefa TV','590000000003','pr-dsp-0001','Placeholder alpha: ekran do testow statusow i notatek technicznych.',1,1,'2026-03-11 12:22:23','2026-03-11 15:16:09'),(4,3,NULL,2,3,'PR-MLC-0002',0,'monitor taki taki','huiwo','hu2','1790','needs_service','loan','Dom Cyjanka / Strefa TV','','76dcf8872524d9f2','',1,1,'2026-03-11 15:16:44','2026-03-11 15:17:18'),(5,2,NULL,2,3,'PR-CON-0002',0,'PSX','Sony','SCPH-1001','1996','inventory','foundation','Dom Cyjanka / Strefa TV','','eb3841eab9b3b535','Test',1,1,'2026-03-12 13:46:14','2026-03-21 14:51:52'),(6,215,NULL,2,3,'PR-TVC-0001',0,'TV ranomowy','Słony','Trinity','1976','assigned','pending','Dom Cyjanka / Strefa TV','','05c408212e39512e','Nr do pilota 5843\r\nPodchodzą od LG',1,1,'2026-03-15 17:43:06','2026-03-15 17:43:06');
/*!40000 ALTER TABLE `equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_images`
--

DROP TABLE IF EXISTS `equipment_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `equipment_id` int unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_equipment_images_equipment` (`equipment_id`),
  KEY `fk_equipment_images_user` (`uploaded_by`),
  CONSTRAINT `fk_equipment_images_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_equipment_images_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_images`
--

LOCK TABLES `equipment_images` WRITE;
/*!40000 ALTER TABLE `equipment_images` DISABLE KEYS */;
INSERT INTO `equipment_images` VALUES (1,1,'uploads/1-9cdd7639e0.jpg','IMG_4702.jpg',1,'2026-03-11 12:40:13');
/*!40000 ALTER TABLE `equipment_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_task_updates`
--

DROP TABLE IF EXISTS `equipment_task_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_task_updates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int unsigned NOT NULL,
  `message` text NOT NULL,
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_equipment_task_updates_task` (`task_id`),
  KEY `fk_equipment_task_updates_user` (`created_by`),
  CONSTRAINT `fk_equipment_task_updates_task` FOREIGN KEY (`task_id`) REFERENCES `equipment_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_equipment_task_updates_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_task_updates`
--

LOCK TABLES `equipment_task_updates` WRITE;
/*!40000 ALTER TABLE `equipment_task_updates` DISABLE KEYS */;
INSERT INTO `equipment_task_updates` VALUES (1,1,'Zdiagnozowano uszkodzenie, trzeba znaleźć sprawny układ.',1,'2026-03-11 13:20:50'),(3,3,'na razie tylko tworzę',1,'2026-03-11 13:32:11'),(4,1,'test zakończenia',1,'2026-03-11 13:43:28'),(5,3,'testowe odrzucenie.',1,'2026-03-11 13:45:33'),(6,3,'zakończono: testowe zakończenie',1,'2026-03-11 13:54:49');
/*!40000 ALTER TABLE `equipment_task_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment_tasks`
--

DROP TABLE IF EXISTS `equipment_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_tasks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `equipment_id` int unsigned NOT NULL,
  `title` varchar(220) NOT NULL,
  `status` enum('open','completed','rejected') NOT NULL DEFAULT 'open',
  `created_by` int unsigned NOT NULL,
  `updated_by` int unsigned NOT NULL,
  `completed_by` int unsigned DEFAULT NULL,
  `rejected_by` int unsigned DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_equipment_tasks_equipment` (`equipment_id`),
  KEY `fk_equipment_tasks_created_by` (`created_by`),
  KEY `fk_equipment_tasks_updated_by` (`updated_by`),
  KEY `fk_equipment_tasks_completed_by` (`completed_by`),
  KEY `fk_equipment_tasks_rejected_by` (`rejected_by`),
  CONSTRAINT `fk_equipment_tasks_completed_by` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_equipment_tasks_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_equipment_tasks_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_equipment_tasks_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_equipment_tasks_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment_tasks`
--

LOCK TABLES `equipment_tasks` WRITE;
/*!40000 ALTER TABLE `equipment_tasks` DISABLE KEYS */;
INSERT INTO `equipment_tasks` VALUES (1,1,'Do przelutowania Agnus','open',1,1,NULL,NULL,NULL,NULL,'2026-03-11 13:20:50','2026-03-11 13:43:43'),(3,1,'testowe zadanie 2','completed',1,1,1,NULL,'2026-03-11 13:54:49',NULL,'2026-03-11 13:32:11','2026-03-11 13:54:49');
/*!40000 ALTER TABLE `equipment_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_places`
--

DROP TABLE IF EXISTS `location_places`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_places` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `location_id` int unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_location_place` (`location_id`,`slug`),
  CONSTRAINT `fk_location_places_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_places`
--

LOCK TABLES `location_places` WRITE;
/*!40000 ALTER TABLE `location_places` DISABLE KEYS */;
INSERT INTO `location_places` VALUES (1,1,'Skrzynka AA','skrzynka-aa','2026-03-11 14:12:17'),(2,1,'Skrzynka B','skrzynka-b','2026-03-11 14:12:17'),(3,2,'Strefa TV','strefa-tv','2026-03-11 14:12:17');
/*!40000 ALTER TABLE `location_places` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
INSERT INTO `locations` VALUES (1,'Dom Mariusza A','dom-mariusza-a','2026-03-11 14:12:17'),(2,'Dom Cyjanka','dom-cyjanka','2026-03-11 14:12:17');
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ownership_statuses`
--

DROP TABLE IF EXISTS `ownership_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ownership_statuses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=465 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ownership_statuses`
--

LOCK TABLES `ownership_statuses` WRITE;
/*!40000 ALTER TABLE `ownership_statuses` DISABLE KEYS */;
INSERT INTO `ownership_statuses` VALUES (1,'Nieustalony','unknown',10,'2026-03-11 14:48:09'),(2,'Na fundację','foundation',20,'2026-03-11 14:48:09'),(3,'Wypożyczenie','loan',30,'2026-03-11 14:48:09'),(4,'W trakcie ustaleń','pending',40,'2026-03-11 14:48:09');
/*!40000 ALTER TABLE `ownership_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `can_manage_users` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_roles` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_root_roles` tinyint(1) NOT NULL DEFAULT '0',
  `can_edit_records` tinyint(1) NOT NULL DEFAULT '0',
  `can_upload_images` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete_images` tinyint(1) NOT NULL DEFAULT '0',
  `can_create_tasks` tinyint(1) NOT NULL DEFAULT '0',
  `can_update_tasks` tinyint(1) NOT NULL DEFAULT '0',
  `can_change_task_status` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete_tasks` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_settings` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_dictionaries` tinyint(1) NOT NULL DEFAULT '0',
  `can_view_audit_history` tinyint(1) NOT NULL DEFAULT '0',
  `can_export_csv` tinyint(1) NOT NULL DEFAULT '0',
  `can_import_csv` tinyint(1) NOT NULL DEFAULT '0',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Root SuperAdmin','root_superadmin',10,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,'2026-03-11 15:28:57'),(2,'SuperAdmin','superadmin',20,1,1,0,1,1,1,1,1,1,1,1,1,1,1,1,1,'2026-03-11 15:28:57'),(3,'Admin','admin',30,0,0,0,1,1,1,1,1,1,1,1,0,1,1,0,1,'2026-03-11 15:28:57'),(4,'ReadOnly','readonly',40,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,'2026-03-11 15:28:57');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(80) NOT NULL,
  `display_name` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(120) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'pressreset-root','Press Reset SuperAdmin','$2y$10$/t2RGfVUKycwCjFC3ZbjBuRnZdk6Lv7IWB4VoIBBITZJqZ8XLfifW','root_superadmin',1,0,'2026-03-17 15:37:13','2026-03-11 12:22:23','2026-03-21 14:51:52'),(2,'pressreset-admin','Press Reset Admin','$2y$10$0vsCxDI1zR7qjfZJknx47OqjS6JKiEWNY3d2x/bU1OsdwiE1/VVvy','admin',1,1,NULL,'2026-03-11 12:22:23','2026-03-11 12:22:23'),(3,'pressreset-view','Press Reset ReadOnly','$2y$10$1IW.8Il2iyA/5Z9PkFUmc.Z.p/fpy8Qzw7Mv01FXH1UZ2Pvk1St0W','readonly',1,1,'2026-03-11 12:57:18','2026-03-11 12:22:23','2026-03-21 14:51:52');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'pressreset_inventory_alpha'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-21 14:52:29

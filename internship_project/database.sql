-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: internship_manager
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Current Database: `internship_manager`
--

/*!40000 DROP DATABASE IF EXISTS `internship_manager`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `internship_manager` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `internship_manager`;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `company_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `quota` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`company_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'FPT Software','Software development and IT outsourcing company.',NULL,NULL,NULL,10,'2026-06-21 05:14:50'),(2,'Viettel Digital','Technology, data, and digital transformation services.',NULL,NULL,NULL,8,'2026-06-21 05:14:50'),(3,'Shopee Vietnam','E-commerce platform with business and data internship positions.',NULL,NULL,NULL,6,'2026-06-21 05:14:50'),(4,'SafeGate Company','Công ty An ninh mạng Việt Nam',NULL,NULL,NULL,5,'2026-06-21 05:53:50');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_evaluations`
--

DROP TABLE IF EXISTS `company_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_evaluations` (
  `ce_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `score` decimal(4,2) NOT NULL,
  `comment` text DEFAULT NULL,
  `evaluated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ce_id`),
  UNIQUE KEY `registration_id` (`registration_id`),
  CONSTRAINT `fk_company_evaluation_registration` FOREIGN KEY (`registration_id`) REFERENCES `internship_registrations` (`ir_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_evaluations`
--

LOCK TABLES `company_evaluations` WRITE;
/*!40000 ALTER TABLE `company_evaluations` DISABLE KEYS */;
INSERT INTO `company_evaluations` VALUES (1,1,7.00,'Hoàn thành tốt nhiệm vụ được giao.','2026-06-21 05:32:44'),(2,4,8.50,'Hoàn thành tốt nhiệm vụ được giao.','2026-06-21 05:32:44'),(3,7,8.00,'Hoàn thành tốt nhiệm vụ được giao.','2026-06-21 05:32:44'),(4,13,7.00,'Hoàn thành tốt nhiệm vụ được giao.','2026-06-21 05:32:44'),(5,16,8.50,'Hoàn thành tốt nhiệm vụ được giao.','2026-06-21 05:32:44');
/*!40000 ALTER TABLE `company_evaluations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_logs`
--

DROP TABLE IF EXISTS `compliance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_logs` (
  `cl_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `issue` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cl_id`),
  KEY `fk_compliance_registration` (`registration_id`),
  CONSTRAINT `fk_compliance_registration` FOREIGN KEY (`registration_id`) REFERENCES `internship_registrations` (`ir_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_logs`
--

LOCK TABLES `compliance_logs` WRITE;
/*!40000 ALTER TABLE `compliance_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `files` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `week_number` int(11) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`file_id`),
  KEY `fk_file_registration` (`registration_id`),
  CONSTRAINT `fk_file_registration` FOREIGN KEY (`registration_id`) REFERENCES `internship_registrations` (`ir_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=185 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `files`
--

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
INSERT INTO `files` VALUES (94,1,1,'uploads/report_1_w1_demo.txt','txt','2026-06-21 13:27:27'),(95,1,2,'uploads/report_1_w2_demo.txt','txt','2026-06-21 13:27:27'),(96,1,3,'uploads/report_1_w3_demo.txt','txt','2026-06-21 13:27:27'),(97,2,1,'uploads/report_2_w1_demo.txt','txt','2026-06-21 13:27:27'),(98,2,2,'uploads/report_2_w2_demo.txt','txt','2026-06-21 13:27:27'),(99,2,3,'uploads/report_2_w3_demo.txt','txt','2026-06-21 13:27:27'),(100,2,4,'uploads/report_2_w4_demo.txt','txt','2026-06-21 13:27:27'),(101,3,1,'uploads/report_3_w1_demo.txt','txt','2026-06-21 13:27:27'),(102,3,2,'uploads/report_3_w2_demo.txt','txt','2026-06-21 13:27:27'),(103,3,3,'uploads/report_3_w3_demo.txt','txt','2026-06-21 13:27:27'),(104,3,4,'uploads/report_3_w4_demo.txt','txt','2026-06-21 13:27:27'),(105,3,5,'uploads/report_3_w5_demo.txt','txt','2026-06-21 13:27:27'),(106,4,1,'uploads/report_4_w1_demo.txt','txt','2026-06-21 13:27:27'),(107,4,2,'uploads/report_4_w2_demo.txt','txt','2026-06-21 13:27:27'),(108,4,3,'uploads/report_4_w3_demo.txt','txt','2026-06-21 13:27:27'),(109,4,4,'uploads/report_4_w4_demo.txt','txt','2026-06-21 13:27:27'),(110,4,5,'uploads/report_4_w5_demo.txt','txt','2026-06-21 13:27:27'),(111,4,6,'uploads/report_4_w6_demo.txt','txt','2026-06-21 13:27:27'),(112,5,1,'uploads/report_5_w1_demo.txt','txt','2026-06-21 13:27:27'),(113,5,2,'uploads/report_5_w2_demo.txt','txt','2026-06-21 13:27:27'),(114,5,3,'uploads/report_5_w3_demo.txt','txt','2026-06-21 13:27:27'),(115,6,1,'uploads/report_6_w1_demo.txt','txt','2026-06-21 13:27:27'),(116,6,2,'uploads/report_6_w2_demo.txt','txt','2026-06-21 13:27:27'),(117,6,3,'uploads/report_6_w3_demo.txt','txt','2026-06-21 13:27:27'),(118,6,4,'uploads/report_6_w4_demo.txt','txt','2026-06-21 13:27:27'),(119,7,1,'uploads/report_7_w1_demo.txt','txt','2026-06-21 13:27:27'),(120,7,2,'uploads/report_7_w2_demo.txt','txt','2026-06-21 13:27:27'),(121,7,3,'uploads/report_7_w3_demo.txt','txt','2026-06-21 13:27:27'),(122,7,4,'uploads/report_7_w4_demo.txt','txt','2026-06-21 13:27:27'),(123,7,5,'uploads/report_7_w5_demo.txt','txt','2026-06-21 13:27:27'),(124,9,1,'uploads/report_9_w1_demo.txt','txt','2026-06-21 13:27:27'),(125,9,2,'uploads/report_9_w2_demo.txt','txt','2026-06-21 13:27:27'),(126,9,3,'uploads/report_9_w3_demo.txt','txt','2026-06-21 13:27:27'),(127,9,4,'uploads/report_9_w4_demo.txt','txt','2026-06-21 13:27:27'),(128,9,5,'uploads/report_9_w5_demo.txt','txt','2026-06-21 13:27:27'),(129,9,6,'uploads/report_9_w6_demo.txt','txt','2026-06-21 13:27:27'),(130,10,1,'uploads/report_10_w1_demo.txt','txt','2026-06-21 13:27:27'),(131,10,2,'uploads/report_10_w2_demo.txt','txt','2026-06-21 13:27:27'),(132,10,3,'uploads/report_10_w3_demo.txt','txt','2026-06-21 13:27:27'),(133,11,1,'uploads/report_11_w1_demo.txt','txt','2026-06-21 13:27:27'),(134,11,2,'uploads/report_11_w2_demo.txt','txt','2026-06-21 13:27:27'),(135,11,3,'uploads/report_11_w3_demo.txt','txt','2026-06-21 13:27:27'),(136,11,4,'uploads/report_11_w4_demo.txt','txt','2026-06-21 13:27:27'),(137,12,1,'uploads/report_12_w1_demo.txt','txt','2026-06-21 13:27:27'),(138,12,2,'uploads/report_12_w2_demo.txt','txt','2026-06-21 13:27:27'),(139,12,3,'uploads/report_12_w3_demo.txt','txt','2026-06-21 13:27:27'),(140,12,4,'uploads/report_12_w4_demo.txt','txt','2026-06-21 13:27:27'),(141,12,5,'uploads/report_12_w5_demo.txt','txt','2026-06-21 13:27:27'),(142,13,1,'uploads/report_13_w1_demo.txt','txt','2026-06-21 13:27:27'),(143,13,2,'uploads/report_13_w2_demo.txt','txt','2026-06-21 13:27:27'),(144,13,3,'uploads/report_13_w3_demo.txt','txt','2026-06-21 13:27:27'),(145,13,4,'uploads/report_13_w4_demo.txt','txt','2026-06-21 13:27:27'),(146,13,5,'uploads/report_13_w5_demo.txt','txt','2026-06-21 13:27:27'),(147,13,6,'uploads/report_13_w6_demo.txt','txt','2026-06-21 13:27:27'),(148,14,1,'uploads/report_14_w1_demo.txt','txt','2026-06-21 13:27:27'),(149,14,2,'uploads/report_14_w2_demo.txt','txt','2026-06-21 13:27:27'),(150,14,3,'uploads/report_14_w3_demo.txt','txt','2026-06-21 13:27:27'),(151,15,1,'uploads/report_15_w1_demo.txt','txt','2026-06-21 13:27:27'),(152,15,2,'uploads/report_15_w2_demo.txt','txt','2026-06-21 13:27:27'),(153,15,3,'uploads/report_15_w3_demo.txt','txt','2026-06-21 13:27:27'),(154,15,4,'uploads/report_15_w4_demo.txt','txt','2026-06-21 13:27:27'),(155,16,1,'uploads/report_16_w1_demo.txt','txt','2026-06-21 13:27:27'),(156,16,2,'uploads/report_16_w2_demo.txt','txt','2026-06-21 13:27:27'),(157,16,3,'uploads/report_16_w3_demo.txt','txt','2026-06-21 13:27:27'),(158,16,4,'uploads/report_16_w4_demo.txt','txt','2026-06-21 13:27:27'),(159,16,5,'uploads/report_16_w5_demo.txt','txt','2026-06-21 13:27:27'),(160,17,1,'uploads/report_17_w1_demo.txt','txt','2026-06-21 13:27:27'),(161,17,2,'uploads/report_17_w2_demo.txt','txt','2026-06-21 13:27:27'),(162,17,3,'uploads/report_17_w3_demo.txt','txt','2026-06-21 13:27:27'),(163,17,4,'uploads/report_17_w4_demo.txt','txt','2026-06-21 13:27:27'),(164,17,5,'uploads/report_17_w5_demo.txt','txt','2026-06-21 13:27:27'),(165,17,6,'uploads/report_17_w6_demo.txt','txt','2026-06-21 13:27:27'),(166,18,1,'uploads/report_18_w1_demo.txt','txt','2026-06-21 13:27:27'),(167,18,2,'uploads/report_18_w2_demo.txt','txt','2026-06-21 13:27:27'),(168,18,3,'uploads/report_18_w3_demo.txt','txt','2026-06-21 13:27:27'),(169,19,1,'uploads/report_19_w1_demo.txt','txt','2026-06-21 13:27:27'),(170,19,2,'uploads/report_19_w2_demo.txt','txt','2026-06-21 13:27:27'),(171,19,3,'uploads/report_19_w3_demo.txt','txt','2026-06-21 13:27:27'),(172,19,4,'uploads/report_19_w4_demo.txt','txt','2026-06-21 13:27:27'),(173,20,1,'uploads/report_20_w1_demo.txt','txt','2026-06-21 13:27:27'),(174,20,2,'uploads/report_20_w2_demo.txt','txt','2026-06-21 13:27:27'),(175,20,3,'uploads/report_20_w3_demo.txt','txt','2026-06-21 13:27:27'),(176,20,4,'uploads/report_20_w4_demo.txt','txt','2026-06-21 13:27:27'),(177,20,5,'uploads/report_20_w5_demo.txt','txt','2026-06-21 13:27:27'),(178,31,1,'uploads/report_31_w1_demo.txt','txt','2026-06-21 13:27:27'),(179,31,2,'uploads/report_31_w2_demo.txt','txt','2026-06-21 13:27:27'),(180,31,3,'uploads/report_31_w3_demo.txt','txt','2026-06-21 13:27:27'),(181,31,4,'uploads/report_31_w4_demo.txt','txt','2026-06-21 13:27:27'),(182,31,5,'uploads/report_31_w5_demo.txt','txt','2026-06-21 13:27:27'),(183,31,6,'uploads/report_31_w6_demo.txt','txt','2026-06-21 13:27:27'),(184,2,5,'uploads/report_2_w5_1782048910.docx','docx','2026-06-21 13:35:10');
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `final_grades`
--

DROP TABLE IF EXISTS `final_grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `final_grades` (
  `fg_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `company_score` decimal(4,2) NOT NULL,
  `lecturer_score` decimal(4,2) NOT NULL,
  `final_score` decimal(4,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`fg_id`),
  UNIQUE KEY `registration_id` (`registration_id`),
  CONSTRAINT `fk_final_grade_registration` FOREIGN KEY (`registration_id`) REFERENCES `internship_registrations` (`ir_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `final_grades`
--

LOCK TABLES `final_grades` WRITE;
/*!40000 ALTER TABLE `final_grades` DISABLE KEYS */;
/*!40000 ALTER TABLE `final_grades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internship_assignments`
--

DROP TABLE IF EXISTS `internship_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `internship_assignments` (
  `ia_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ia_id`),
  UNIQUE KEY `registration_id` (`registration_id`),
  KEY `fk_assignment_lecturer` (`lecturer_id`),
  CONSTRAINT `fk_assignment_lecturer` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_assignment_registration` FOREIGN KEY (`registration_id`) REFERENCES `internship_registrations` (`ir_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internship_assignments`
--

LOCK TABLES `internship_assignments` WRITE;
/*!40000 ALTER TABLE `internship_assignments` DISABLE KEYS */;
INSERT INTO `internship_assignments` VALUES (36,1,2,'2026-06-21 13:27:27'),(37,2,2,'2026-06-21 13:27:27'),(38,3,2,'2026-06-21 13:27:27'),(39,4,2,'2026-06-21 13:27:27'),(40,5,2,'2026-06-21 13:27:27'),(41,6,2,'2026-06-21 13:27:27'),(42,7,2,'2026-06-21 13:27:27'),(43,9,5,'2026-06-21 13:27:27'),(44,10,5,'2026-06-21 13:27:27'),(45,11,5,'2026-06-21 13:27:27'),(46,12,5,'2026-06-21 13:27:27'),(47,13,5,'2026-06-21 13:27:27'),(48,14,5,'2026-06-21 13:27:27'),(49,15,5,'2026-06-21 13:27:27'),(50,16,6,'2026-06-21 13:27:27'),(51,17,6,'2026-06-21 13:27:27'),(52,18,6,'2026-06-21 13:27:27'),(53,19,6,'2026-06-21 13:27:27'),(54,20,6,'2026-06-21 13:27:27'),(55,31,6,'2026-06-21 13:27:27'),(56,46,5,'2026-06-21 14:37:40');
/*!40000 ALTER TABLE `internship_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internship_periods`
--

DROP TABLE IF EXISTS `internship_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `internship_periods` (
  `period_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  PRIMARY KEY (`period_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internship_periods`
--

LOCK TABLES `internship_periods` WRITE;
/*!40000 ALTER TABLE `internship_periods` DISABLE KEYS */;
INSERT INTO `internship_periods` VALUES (1,'Summer Internship 2026','2026-06-01','2026-08-30');
/*!40000 ALTER TABLE `internship_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internship_positions`
--

DROP TABLE IF EXISTS `internship_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `internship_positions` (
  `ip_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `jd_file` varchar(255) DEFAULT NULL,
  `major` varchar(100) DEFAULT NULL,
  `quota` int(11) NOT NULL DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ip_id`),
  KEY `fk_positions_company` (`company_id`),
  CONSTRAINT `fk_positions_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internship_positions`
--

LOCK TABLES `internship_positions` WRITE;
/*!40000 ALTER TABLE `internship_positions` DISABLE KEYS */;
INSERT INTO `internship_positions` VALUES (1,1,'Business Analyst Intern','Support requirement analysis and documentation.',NULL,'Information Technology',30,'2026-06-21 05:14:50'),(2,1,'Web Developer Intern','Build and maintain internal web applications.',NULL,'Information Technology',30,'2026-06-21 05:14:50'),(3,2,'Data Analyst Intern','Clean data and create basic dashboards.',NULL,'Data Science',30,'2026-06-21 05:14:50'),(4,3,'Marketing Information Intern','Support marketing data tracking and reporting.',NULL,'Marketing',30,'2026-06-21 05:14:50'),(5,4,'Business Analyst Intern',NULL,'uploads/jd/jd_4_1782022063.pdf','MIS; BDA',3,'2026-06-21 06:07:43'),(6,4,'Firmware intern','',NULL,NULL,5,'2026-06-21 14:13:58'),(7,4,'HR Intern','',NULL,NULL,3,'2026-06-21 14:14:18');
/*!40000 ALTER TABLE `internship_positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internship_registrations`
--

DROP TABLE IF EXISTS `internship_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `internship_registrations` (
  `ir_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `position_id` int(11) DEFAULT NULL,
  `proposed_company` varchar(150) DEFAULT NULL,
  `proposed_position` varchar(150) DEFAULT NULL,
  `cv_file` varchar(255) DEFAULT NULL,
  `period_id` int(11) NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ir_id`),
  UNIQUE KEY `uq_student_position_period` (`student_id`,`position_id`,`period_id`),
  KEY `fk_registration_position` (`position_id`),
  KEY `fk_registration_period` (`period_id`),
  CONSTRAINT `fk_registration_period` FOREIGN KEY (`period_id`) REFERENCES `internship_periods` (`period_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_registration_position` FOREIGN KEY (`position_id`) REFERENCES `internship_positions` (`ip_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_registration_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internship_registrations`
--

LOCK TABLES `internship_registrations` WRITE;
/*!40000 ALTER TABLE `internship_registrations` DISABLE KEYS */;
INSERT INTO `internship_registrations` VALUES (1,25,1,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(2,26,3,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(3,27,4,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(4,28,1,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(5,29,3,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(6,30,4,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(7,31,1,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(9,33,4,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(10,34,1,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(11,35,3,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(12,36,4,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(13,37,1,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(14,38,3,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(15,39,4,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(16,40,1,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(17,41,3,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(18,42,4,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(19,43,1,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(20,44,3,NULL,NULL,NULL,1,'Approved','2026-06-21 05:32:44'),(31,32,1,NULL,NULL,NULL,1,'Approved','2026-06-21 06:33:12'),(46,49,4,NULL,NULL,'uploads/cv/cv_49_4_1782050805.pdf',1,'Approved','2026-06-21 14:06:45');
/*!40000 ALTER TABLE `internship_registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecturer_evaluations`
--

DROP TABLE IF EXISTS `lecturer_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lecturer_evaluations` (
  `le_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `score` decimal(4,2) NOT NULL,
  `comment` text DEFAULT NULL,
  `evaluated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`le_id`),
  UNIQUE KEY `registration_id` (`registration_id`),
  CONSTRAINT `fk_lecturer_evaluation_registration` FOREIGN KEY (`registration_id`) REFERENCES `internship_registrations` (`ir_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecturer_evaluations`
--

LOCK TABLES `lecturer_evaluations` WRITE;
/*!40000 ALTER TABLE `lecturer_evaluations` DISABLE KEYS */;
INSERT INTO `lecturer_evaluations` VALUES (1,1,7.50,'Báo cáo và nhật ký đầy đủ.','2026-06-21 05:32:44'),(2,4,7.50,'Báo cáo và nhật ký đầy đủ.','2026-06-21 05:32:44'),(3,7,7.50,'Báo cáo và nhật ký đầy đủ.','2026-06-21 05:32:44'),(4,13,7.50,'Báo cáo và nhật ký đầy đủ.','2026-06-21 05:32:44'),(5,16,7.50,'Báo cáo và nhật ký đầy đủ.','2026-06-21 05:32:44');
/*!40000 ALTER TABLE `lecturer_evaluations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','lecturer','company','admin') NOT NULL DEFAULT 'student',
  `code` varchar(50) DEFAULT NULL,
  `major` varchar(100) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_company` (`company_id`),
  CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`company_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Dr. Le Minh','lecturer1@ischool.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','lecturer',NULL,'Information Technology',NULL,'2026-06-21 05:14:50'),(3,'FPT Company Account','fpt@example.com','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','company',NULL,NULL,1,'2026-06-21 05:14:50'),(4,'Admin ISchool','admin@ischool.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','admin',NULL,NULL,NULL,'2026-06-21 05:14:50'),(5,'Nguyễn Ngọc Minh','gv1@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','lecturer',NULL,'Information Technology',NULL,'2026-06-21 05:32:44'),(6,'TS. Trần Quốc','gv2@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','lecturer',NULL,'Data Science',NULL,'2026-06-21 05:32:44'),(25,'Nguyễn An','sv1@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070002','Information Technology',NULL,'2026-06-21 05:32:44'),(26,'Nguyễn Bình','sv2@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070003','Data Science',NULL,'2026-06-21 05:32:44'),(27,'Nguyễn Chi','sv3@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070004','Marketing',NULL,'2026-06-21 05:32:44'),(28,'Nguyễn Dũng','sv4@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070005','Information Technology',NULL,'2026-06-21 05:32:44'),(29,'Nguyễn Giang','sv5@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070006','Data Science',NULL,'2026-06-21 05:32:44'),(30,'Nguyễn Hà','sv6@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070007','Marketing',NULL,'2026-06-21 05:32:44'),(31,'Nguyễn Hải','sv7@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070008','Information Technology',NULL,'2026-06-21 05:32:44'),(32,'Nguyễn Hùng','sv8@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070009','Data Science',NULL,'2026-06-21 05:32:44'),(33,'Nguyễn Khoa','sv9@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070010','Marketing',NULL,'2026-06-21 05:32:44'),(34,'Nguyễn Lan','sv10@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070011','Information Technology',NULL,'2026-06-21 05:32:44'),(35,'Nguyễn Linh','sv11@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070012','Data Science',NULL,'2026-06-21 05:32:44'),(36,'Nguyễn Mai','sv12@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070013','Marketing',NULL,'2026-06-21 05:32:44'),(37,'Nguyễn Nam','sv13@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070014','Information Technology',NULL,'2026-06-21 05:32:44'),(38,'Nguyễn Ngọc','sv14@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070015','Data Science',NULL,'2026-06-21 05:32:44'),(39,'Nguyễn Phúc','sv15@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070016','Marketing',NULL,'2026-06-21 05:32:44'),(40,'Nguyễn Quân','sv16@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070017','Information Technology',NULL,'2026-06-21 05:32:44'),(41,'Nguyễn Sơn','sv17@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070018','Data Science',NULL,'2026-06-21 05:32:44'),(42,'Nguyễn Trang','sv18@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070019','Marketing',NULL,'2026-06-21 05:32:44'),(43,'Nguyễn Tú','sv19@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070020','Information Technology',NULL,'2026-06-21 05:32:44'),(44,'Nguyễn Vy','sv20@fake.edu.vn','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','student','22070021','Data Science',NULL,'2026-06-21 05:32:44'),(46,'SafeGate Company Account','safegate@example.com','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','company',NULL,NULL,4,'2026-06-21 12:58:06'),(47,'Shopee Vietnam Account','shopee@example.com','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','company',NULL,NULL,3,'2026-06-21 12:58:06'),(48,'Viettel Digital Account','viettel@example.com','$2y$10$84CVp6gJ9AYdWp4qHR43xukJtDsoEHrfyl4GAj2DQq7GjN3UL1Ee2','company',NULL,NULL,2,'2026-06-21 12:58:06'),(49,'Nguyễn Chiến Trung','sv22@ischool.edu.vn','$2y$10$W8WW2MhfFyy.fGcI/LrpZeJV0kQziqw61/vJmkLduHuhKUippYp02','student','22070999','MIS',NULL,'2026-06-21 13:40:43');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `weekly_journals`
--

DROP TABLE IF EXISTS `weekly_journals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weekly_journals` (
  `wj_id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `content` text NOT NULL,
  `status` enum('Submitted','Late','Missing') NOT NULL DEFAULT 'Submitted',
  `lecturer_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`wj_id`),
  UNIQUE KEY `uq_registration_week` (`registration_id`,`week_number`),
  CONSTRAINT `fk_journal_registration` FOREIGN KEY (`registration_id`) REFERENCES `internship_registrations` (`ir_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=207 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weekly_journals`
--

LOCK TABLES `weekly_journals` WRITE;
/*!40000 ALTER TABLE `weekly_journals` DISABLE KEYS */;
INSERT INTO `weekly_journals` VALUES (116,1,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(117,1,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(118,1,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Late',NULL,'2026-06-21 13:27:27'),(119,2,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(120,2,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(121,2,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(122,2,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(123,3,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(124,3,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(125,3,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(126,3,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(127,3,5,'Tuần 5: Hoàn thiện tài liệu và chuẩn bị báo cáo giữa kỳ.','Submitted',NULL,'2026-06-21 13:27:27'),(128,4,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(129,4,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(130,4,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(131,4,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(132,4,5,'Tuần 5: Hoàn thiện tài liệu và chuẩn bị báo cáo giữa kỳ.','Submitted',NULL,'2026-06-21 13:27:27'),(133,4,6,'Tuần 6: Tổng kết công việc, viết báo cáo cuối kỳ thực tập.','Late','Hoàn thành tốt kỳ thực tập.','2026-06-21 13:27:27'),(134,5,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(135,5,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(136,5,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(137,6,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(138,6,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(139,6,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(140,6,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(141,7,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(142,7,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(143,7,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(144,7,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(145,7,5,'Tuần 5: Hoàn thiện tài liệu và chuẩn bị báo cáo giữa kỳ.','Late',NULL,'2026-06-21 13:27:27'),(146,9,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(147,9,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(148,9,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(149,9,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(150,9,5,'Tuần 5: Hoàn thiện tài liệu và chuẩn bị báo cáo giữa kỳ.','Submitted',NULL,'2026-06-21 13:27:27'),(151,9,6,'Tuần 6: Tổng kết công việc, viết báo cáo cuối kỳ thực tập.','Submitted','Hoàn thành tốt kỳ thực tập.','2026-06-21 13:27:27'),(152,10,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(153,10,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(154,10,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(155,11,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(156,11,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(157,11,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(158,11,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Late','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(159,12,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(160,12,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(161,12,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(162,12,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(163,12,5,'Tuần 5: Hoàn thiện tài liệu và chuẩn bị báo cáo giữa kỳ.','Submitted',NULL,'2026-06-21 13:27:27'),(164,13,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(165,13,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(166,13,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(167,13,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(168,13,5,'Tuần 5: Hoàn thiện tài liệu và chuẩn bị báo cáo giữa kỳ.','Submitted',NULL,'2026-06-21 13:27:27'),(169,13,6,'Tuần 6: Tổng kết công việc, viết báo cáo cuối kỳ thực tập.','Submitted','Hoàn thành tốt kỳ thực tập.','2026-06-21 13:27:27'),(170,14,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(171,14,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(172,14,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Late',NULL,'2026-06-21 13:27:27'),(173,15,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(174,15,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(175,15,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(176,15,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(177,16,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(178,16,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(179,16,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(180,16,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(181,16,5,'Tuần 5: Hoàn thiện tài liệu và chuẩn bị báo cáo giữa kỳ.','Submitted',NULL,'2026-06-21 13:27:27'),(182,17,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(183,17,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(184,17,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(185,17,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(186,17,5,'Tuần 5: Hoàn thiện tài liệu và chuẩn bị báo cáo giữa kỳ.','Submitted',NULL,'2026-06-21 13:27:27'),(187,17,6,'Tuần 6: Tổng kết công việc, viết báo cáo cuối kỳ thực tập.','Late','Hoàn thành tốt kỳ thực tập.','2026-06-21 13:27:27'),(188,18,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(189,18,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(190,18,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(191,19,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(192,19,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(193,19,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(194,19,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(195,20,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(196,20,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(197,20,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(198,20,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(199,20,5,'Tuần 5: Hoàn thiện tài liệu và chuẩn bị báo cáo giữa kỳ.','Late',NULL,'2026-06-21 13:27:27'),(200,31,1,'Tuần 1: Tìm hiểu môi trường làm việc và công cụ nội bộ.','Submitted','Khởi đầu tốt, nắm bắt nhanh.','2026-06-21 13:27:27'),(201,31,2,'Tuần 2: Phân tích yêu cầu nghiệp vụ và viết tài liệu mô tả.','Submitted','Tài liệu rõ ràng, cần bổ sung sơ đồ.','2026-06-21 13:27:27'),(202,31,3,'Tuần 3: Tham gia xây dựng tính năng theo phân công của mentor.','Submitted',NULL,'2026-06-21 13:27:27'),(203,31,4,'Tuần 4: Kiểm thử và sửa lỗi các chức năng đã làm.','Submitted','Chủ động sửa lỗi, tiến bộ.','2026-06-21 13:27:27'),(204,31,5,'Tuần 5: Hoàn thiện tài liệu và chuẩn bị báo cáo giữa kỳ.','Submitted',NULL,'2026-06-21 13:27:27'),(205,31,6,'Tuần 6: Tổng kết công việc, viết báo cáo cuối kỳ thực tập.','Submitted','Hoàn thành tốt kỳ thực tập.','2026-06-21 13:27:27'),(206,2,5,'hihi','Submitted',NULL,'2026-06-21 13:35:10');
/*!40000 ALTER TABLE `weekly_journals` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-21 22:22:02

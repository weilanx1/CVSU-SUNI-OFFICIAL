-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 06, 2026 at 01:11 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `suni_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `registration_id` int NOT NULL,
  `checked_in_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registration_id` (`registration_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `code`, `name`) VALUES
(1, 'CAFENR', 'College of Agriculture, Food, Environment, and Natural Resources'),
(2, 'CAS', 'College of Arts and Sciences'),
(3, 'CCJ', 'College of Criminal Justice'),
(4, 'CEd', 'College of Education'),
(5, 'CEIT', 'College of Engineering and Information Technology'),
(6, 'CEMDS', 'College of Economics, Management, and Development Studies'),
(7, 'CON', 'College of Nursing'),
(8, 'CTHM', 'College of Tourism and Hospitality Management'),
(9, 'CSPEAR', 'College of Sports, Physical Education, and Recreation'),
(10, 'CVMBS', 'College of Veterinary Medicine and Biomedical Sciences'),
(11, 'OSAS', 'Office of Student Affairs and Services');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` int NOT NULL,
  `created_by` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `event_banner` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `venue` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visibility` enum('public','organization_only','restricted','private') COLLATE utf8mb4_unicode_ci DEFAULT 'public',
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `capacity` int DEFAULT NULL,
  `ticket_price` decimal(10,2) DEFAULT '0.00',
  `require_approval` tinyint(1) DEFAULT '0',
  `status` enum('draft','published','cancelled','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `organization_id` (`organization_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `organization_id`, `created_by`, `title`, `description`, `event_banner`, `cover_photo`, `venue`, `visibility`, `start_datetime`, `end_datetime`, `capacity`, `ticket_price`, `require_approval`, `status`, `created_at`, `updated_at`) VALUES
(3, 1, 1, 'Ayoko na po huhu', 'hala', 'uploads/banner_6a205d3e83158.jpg', 'uploads/cover_6a205d3e836df.png', 'CvSU Main Campus - Gymnasium, Indang, Cavite', 'public', '2026-06-04 07:00:00', '2026-06-08 17:00:00', 50, 0.00, 1, 'published', '2026-06-03 16:58:38', '2026-06-03 17:41:23'),
(4, 1, 1, 'Trial Event 2', 'hala okie p', 'uploads/banner_6a205f87652f6.jpg', 'uploads/cover_6a205f8765804.jpg', 'CvSU Main Campus - CEIT Building, Indang, Cavite', 'public', '2027-08-02 06:00:00', '2027-08-04 01:00:00', 50, 0.00, 1, 'published', '2026-06-03 17:08:23', '2026-06-03 17:43:19'),
(5, 1, 1, '', NULL, 'uploads/banner_6a206a4136e84.jpg', 'uploads/cover_6a206a41370c4.jpg', NULL, 'public', '0000-00-00 00:00:00', '0000-00-00 00:00:00', NULL, 0.00, NULL, 'published', '2026-06-03 17:54:09', '2026-06-04 07:47:02'),
(7, 1, 1, 'Let\'s Try To Test A New Event', 'sasascsdlkjhgfds', NULL, NULL, 'Central Student Government Office, Indang, Cavite', 'public', '2026-06-11 02:30:00', '2026-06-09 01:30:00', 1000, 0.00, 1, 'published', '2026-06-04 08:48:46', '2026-06-04 08:48:46'),
(8, 6, 5, 'Try Sinagtala naman', 'dsdsdsdsdsds', 'uploads/banner_6a2147c3c027f.jpg', 'uploads/cover_6a2147c3c044f.png', 'CvSU Main Campus - University Library, Indang, Cavite', 'public', '2026-06-16 02:00:00', '2026-06-16 02:00:00', NULL, 0.00, 1, 'published', '2026-06-04 09:39:15', '2026-06-04 09:39:15'),
(9, 2, 4, 'Serverless Workshop Event', 'sasasasasadadadadasas', 'uploads/banner_6a21e26be0035.jpg', 'uploads/cover_6a22276a87f01.jpg', 'CvSU Main Campus - CEIT Building, Indang, Cavite', 'public', '2027-08-06 07:00:00', '2026-07-08 01:30:00', NULL, 0.00, 1, 'published', '2026-06-04 20:39:07', '2026-06-05 01:33:30');

-- --------------------------------------------------------

--
-- Table structure for table `event_departments`
--

DROP TABLE IF EXISTS `event_departments`;
CREATE TABLE IF NOT EXISTS `event_departments` (
  `event_id` int NOT NULL,
  `department_id` int NOT NULL,
  PRIMARY KEY (`event_id`,`department_id`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_departments`
--

INSERT INTO `event_departments` (`event_id`, `department_id`) VALUES
(4, 1),
(4, 3),
(4, 11);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` int DEFAULT NULL,
  `main_admin_id` int NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  KEY `main_admin_id` (`main_admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `name`, `department_id`, `main_admin_id`, `logo`) VALUES
(1, 'Central Student Government', 11, 1, 'images/org-logos/org_1_1780562213.png'),
(2, 'AWS Cloud Club', 5, 4, 'images/org-logos/org_2_1780480809.png'),
(5, 'CEIT Student Council', 5, 7, 'images/org-logos/org_5_1780565235.png'),
(6, 'SInagtala Multimedia Arts', NULL, 5, 'images/org-logos/org_6_1780565899.png'),
(7, 'Musikeros', NULL, 8, 'images/org-logos/org_7_1780565145.png');

-- --------------------------------------------------------

--
-- Table structure for table `organization_admins`
--

DROP TABLE IF EXISTS `organization_admins`;
CREATE TABLE IF NOT EXISTS `organization_admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `organization_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('admin','moderator') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moderator',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_id` (`organization_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `organization_admins`
--

INSERT INTO `organization_admins` (`id`, `organization_id`, `user_id`, `role`, `created_at`) VALUES
(10, 2, 10, 'moderator', '2026-06-05 07:10:31');

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('pending','approved','rejected','waitlisted') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `attendance_confirmation` enum('unconfirmed','going','not_going') COLLATE utf8mb4_unicode_ci DEFAULT 'unconfirmed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `event_id`, `user_id`, `status`, `attendance_confirmation`, `created_at`, `updated_at`) VALUES
(3, 5, 9, 'approved', 'unconfirmed', '2026-06-04 12:58:19', '2026-06-04 12:58:19'),
(5, 3, 9, 'approved', 'unconfirmed', '2026-06-04 13:00:51', '2026-06-04 13:02:52'),
(6, 7, 9, '', 'unconfirmed', '2026-06-04 13:04:22', '2026-06-04 13:05:31'),
(8, 8, 4, 'pending', 'unconfirmed', '2026-06-04 21:58:10', '2026-06-04 21:58:10'),
(9, 9, 4, 'pending', 'unconfirmed', '2026-06-05 07:09:43', '2026-06-05 07:09:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_type` enum('cvsu','guest') COLLATE utf8mb4_unicode_ci NOT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `department_id` int DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `department_id` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `account_type`, `bio`, `department_id`, `profile_picture`, `created_at`) VALUES
(1, 'Sem Pablo', 'Mateo', 'sem@cvsu.edu.ph', '$2y$10$irJyEZ/u6h7d3NSoDGSfhOvOJWCE9MGrrI6jkkJNN.bQPDx.bbTO6', 'cvsu', NULL, 11, 'uploads/profile_pictures/profile_1_1780578113.png', '2026-06-03 00:46:56'),
(4, 'Xeed Love', 'Magtira', 'xeed@cvsu.edu.ph', '$2y$10$F8uUQ2TBeyOhsAbb0cf.6OBpDSroS5s75G22EONcsb4Tn/SIIFaJa', 'cvsu', NULL, 5, 'uploads/profile_pictures/profile_4_1780566450.png', '2026-06-03 02:32:03'),
(5, 'Kraig Friel', 'Gonzales', 'kraig@cvsu.edu.ph', '$2y$10$n/6Ml8XmuwcH7ShQyn7ciekvgdoEUQWN9IvQt9p8qwe3/GLKuAG1S', 'cvsu', NULL, 5, NULL, '2026-06-03 09:38:01'),
(7, 'CEIT Council', 'Account', 'ceit@cvsu.edu.ph', '$2y$10$qAhJpxNj0w6oZl9.Z832YuVFYUuk1EiKd0ARQfMH3T2bmB6NCjBhW', 'cvsu', NULL, 5, NULL, '2026-06-04 08:58:48'),
(8, 'Musikeros', 'Mock Acc', 'musikeros@cvsu.edu.ph', '$2y$10$YKct3DbnkZ2WbIyyETgilucxF429Lh1PhUpBeMYinQUeFZNEpEgVi', 'cvsu', NULL, 2, NULL, '2026-06-04 09:09:45'),
(9, 'Vince', 'Garcia', 'vince@cvsu.edu.ph', '$2y$10$E12unHVheYc3PVlDuqNyLe4Gh8r18CZwNlfANrtoUIWX1iLT3siIW', 'cvsu', NULL, 5, 'uploads/profile_pictures/profile_9_1780566135.png', '2026-06-04 09:41:34'),
(10, 'Lanz Joe Mari', 'Hilario', 'lanz@cvsu.edu.ph', '$2y$10$dXVkRrtDwETvyBphpDwRJ.7EVqAynAUVgDv/wGhYEGmmBgdFY3d.K', 'cvsu', NULL, 5, 'uploads/profile_pictures/profile_10_1780579847.png', '2026-06-04 13:28:18'),
(11, 'Gino', 'Nolo', 'gino@cvsu.edu.ph', '$2y$10$yIZKHO7L25/iM4.Jk8UenOH47tg2SrtH8/NY6q8mnNFjegC8tT.zC', 'cvsu', NULL, 3, 'uploads/profile_pictures/profile_11_1780579988.jpg', '2026-06-04 13:32:54'),
(12, 'Joanna', 'Jonson', 'joanna@cvsu.edu.ph', '$2y$10$8bHu07169OQEfBgsf4IAGOVu60FhW39if1TItbSlYaSNJ4ODsrVxC', 'cvsu', NULL, 5, 'uploads/profile_pictures/profile_12_1780623350.png', '2026-06-05 01:35:29'),
(13, 'Guest', 'Account', 'guest@gmail.com', '$2y$10$skEYKPgiywsABgB5mxVs4.cNJGGEhr7OHbXEkR3BSZrrphyf3OzyW', 'guest', NULL, NULL, NULL, '2026-06-05 06:57:51');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`);

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `event_departments`
--
ALTER TABLE `event_departments`
  ADD CONSTRAINT `event_departments_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_departments_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `organizations`
--
ALTER TABLE `organizations`
  ADD CONSTRAINT `organizations_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `organizations_ibfk_2` FOREIGN KEY (`main_admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `organization_admins`
--
ALTER TABLE `organization_admins`
  ADD CONSTRAINT `organization_admins_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `organization_admins_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

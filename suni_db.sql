-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 30, 2026 at 11:44 AM
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
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `college` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('cvsu','guest') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cvsu',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `college`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Try', 'Acc', 'tryacc@cvsu.edu.ph', 'CAFENR', '$2y$10$jYJmnAYBgAEh8fJLUbFJyejujPJjLuJdgRY65oeQRJr6adcS8XgdC', 'cvsu', '2026-05-28 11:00:17'),
(2, 'guest', 'account', 'guest@gmail.com', NULL, '$2y$10$aMcvWd5422mV72zLzOZ.0uKgULs3eU.EV.mH2w.wyoZwEjgUCuViu', 'guest', '2026-05-28 11:03:10'),
(3, 'student', 'account', 'student@gmail.com', NULL, '$2y$10$GFN4WTWyAWj9LUnoVRmEsOH2dPPHFweHbiuuqq12V3zLXdNpm8Tdu', 'guest', '2026-05-28 22:54:25'),
(4, 'howard', 'kahitano', 'howard@cvsu.edu.ph', 'CAFENR', '$2y$10$JvY5.0Fdb8BpoWqIk9/a.OZKkuWaF/7wS/yFQuwn.9NWRZw9h/HOS', 'cvsu', '2026-05-29 09:20:45');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

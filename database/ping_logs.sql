-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 15, 2025 at 09:20 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bci_monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `ping_logs`
--

DROP TABLE IF EXISTS `ping_logs`;
CREATE TABLE IF NOT EXISTS `ping_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_id` int NOT NULL,
  `latency` int NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip_id` (`ip_id`)
) ENGINE=MyISAM AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ping_logs`
--

INSERT INTO `ping_logs` (`id`, `ip_id`, `latency`, `status`, `created_at`) VALUES
(1, 1, 18, 'online', '2025-04-15 08:58:25'),
(2, 1, 13, 'online', '2025-04-15 08:58:48'),
(3, 1, 12, 'online', '2025-04-15 08:59:25'),
(4, 2, 5, 'online', '2025-04-15 08:59:29'),
(5, 1, 11, 'online', '2025-04-15 09:02:51'),
(6, 2, 4, 'online', '2025-04-15 09:02:55'),
(7, 3, 8, 'online', '2025-04-15 09:02:59'),
(8, 4, 10, 'online', '2025-04-15 09:03:04'),
(9, 5, 5, 'online', '2025-04-15 09:03:08'),
(10, 6, 6, 'online', '2025-04-15 09:03:12'),
(11, 1, 13, 'online', '2025-04-15 09:03:16'),
(12, 2, 12, 'online', '2025-04-15 09:03:24'),
(13, 3, 13, 'online', '2025-04-15 09:03:28'),
(14, 4, 9, 'online', '2025-04-15 09:03:32'),
(15, 5, 5, 'online', '2025-04-15 09:03:36'),
(16, 6, 6, 'online', '2025-04-15 09:03:40'),
(17, 1, 12, 'online', '2025-04-15 09:05:57'),
(18, 2, 6, 'online', '2025-04-15 09:06:01'),
(19, 3, 12, 'online', '2025-04-15 09:06:06'),
(20, 4, 10, 'online', '2025-04-15 09:06:10'),
(21, 5, 5, 'online', '2025-04-15 09:06:14'),
(22, 6, 6, 'online', '2025-04-15 09:06:18'),
(23, 7, 1, 'online', '2025-04-15 09:06:22'),
(24, 1, 13, 'online', '2025-04-15 09:06:36'),
(25, 2, 6, 'online', '2025-04-15 09:06:40'),
(26, 3, 10, 'online', '2025-04-15 09:06:48'),
(27, 4, 10, 'online', '2025-04-15 09:06:52'),
(28, 5, 7, 'online', '2025-04-15 09:07:00'),
(29, 6, 9, 'online', '2025-04-15 09:07:04'),
(30, 7, 1, 'online', '2025-04-15 09:07:08'),
(31, 1, 12, 'online', '2025-04-15 09:07:12'),
(32, 2, 6, 'online', '2025-04-15 09:07:16'),
(33, 3, 9, 'online', '2025-04-15 09:07:20'),
(34, 4, 9, 'online', '2025-04-15 09:07:28'),
(35, 5, 5, 'online', '2025-04-15 09:07:32'),
(36, 6, 5, 'online', '2025-04-15 09:07:37'),
(37, 7, 1, 'online', '2025-04-15 09:07:41'),
(38, 1, 0, 'offline', '2025-04-15 09:08:08'),
(39, 2, 0, 'offline', '2025-04-15 09:08:16'),
(40, 3, 0, 'offline', '2025-04-15 09:08:28'),
(41, 4, 0, 'offline', '2025-04-15 09:08:45'),
(42, 5, 0, 'offline', '2025-04-15 09:09:05'),
(43, 6, 0, 'offline', '2025-04-15 09:09:20'),
(44, 7, 0, 'offline', '2025-04-15 09:09:35'),
(45, 1, 0, 'offline', '2025-04-15 09:09:53'),
(46, 2, 0, 'offline', '2025-04-15 09:10:08'),
(47, 3, 0, 'offline', '2025-04-15 09:10:23'),
(48, 4, 0, 'offline', '2025-04-15 09:10:38'),
(49, 5, 0, 'offline', '2025-04-15 09:10:53'),
(50, 6, 0, 'offline', '2025-04-15 09:11:08'),
(51, 7, 0, 'offline', '2025-04-15 09:11:25'),
(52, 1, 0, 'offline', '2025-04-15 09:11:42'),
(53, 2, 0, 'offline', '2025-04-15 09:11:57'),
(54, 3, 0, 'offline', '2025-04-15 09:12:12'),
(55, 4, 0, 'offline', '2025-04-15 09:12:27'),
(56, 5, 0, 'offline', '2025-04-15 09:12:42'),
(57, 6, 0, 'offline', '2025-04-15 09:12:57'),
(58, 7, 0, 'offline', '2025-04-15 09:13:13'),
(59, 1, 0, 'offline', '2025-04-15 09:16:30'),
(60, 2, 0, 'offline', '2025-04-15 09:16:54'),
(61, 3, 0, 'offline', '2025-04-15 09:16:58'),
(62, 4, 0, 'offline', '2025-04-15 09:17:14'),
(63, 5, 0, 'offline', '2025-04-15 09:17:38'),
(64, 6, 73, 'online', '2025-04-15 09:17:42'),
(65, 7, 0, 'offline', '2025-04-15 09:18:06');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 29, 2025 at 07:57 AM
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
-- Table structure for table `add_ip`
--

DROP TABLE IF EXISTS `add_ip`;
CREATE TABLE IF NOT EXISTS `add_ip` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL DEFAULT (curdate()),
  `ip_address` varchar(45) NOT NULL,
  `description` text NOT NULL,
  `latency` int NOT NULL DEFAULT '0',
  `status` varchar(20) NOT NULL DEFAULT 'unknown',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `location` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
CREATE TABLE IF NOT EXISTS `locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `location` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ping_logs`
--

INSERT INTO `ping_logs` (`id`, `ip_id`, `latency`, `status`, `created_at`) VALUES
(1, 41, 31, 'online', '2025-04-29 06:40:15'),
(2, 42, 3, 'online', '2025-04-29 06:40:15'),
(3, 45, 0, 'offline', '2025-04-29 06:40:15'),
(4, 43, 31, 'online', '2025-04-29 06:40:15'),
(5, 44, 3, 'online', '2025-04-29 06:40:15'),
(6, 41, 31, 'online', '2025-04-29 06:40:52'),
(7, 42, 3, 'online', '2025-04-29 06:40:52'),
(8, 45, 0, 'offline', '2025-04-29 06:40:52'),
(9, 43, 32, 'online', '2025-04-29 06:40:52'),
(10, 44, 4, 'online', '2025-04-29 06:40:52'),
(11, 41, 32, 'online', '2025-04-29 06:55:57'),
(12, 42, 3, 'online', '2025-04-29 06:55:57'),
(13, 45, 0, 'offline', '2025-04-29 06:55:57'),
(14, 43, 31, 'online', '2025-04-29 06:55:57'),
(15, 44, 3, 'online', '2025-04-29 06:55:57'),
(16, 41, 35, 'online', '2025-04-29 07:11:11'),
(17, 42, 5, 'online', '2025-04-29 07:11:11'),
(18, 45, 0, 'offline', '2025-04-29 07:11:11'),
(19, 43, 36, 'online', '2025-04-29 07:11:11'),
(20, 44, 8, 'online', '2025-04-29 07:11:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(2, 'mark', 'mark@gmail.com', '$2y$10$FYK/fQI/lpxupZfCj1pREOe8QkbuqEvxwW3.UphxZOAAU7bzE9tSO', '2025-04-22 00:14:44');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

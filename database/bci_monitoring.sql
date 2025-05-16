-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 25, 2025 at 08:53 AM
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
  `date` date NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `description` text NOT NULL,
  `latency` int NOT NULL DEFAULT '0',
  `status` varchar(20) NOT NULL DEFAULT 'unknown',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `location` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `add_ip`
--

INSERT INTO `add_ip` (`id`, `date`, `ip_address`, `description`, `latency`, `status`, `created_at`, `location`, `category`, `last_updated`) VALUES
(35, '2025-04-25', '10.10.10.56', 'mikotek', 0, 'offline', '2025-04-25 06:39:31', 'Akle Gabihan', 'Internet', '2025-04-25 14:39:31');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category`) VALUES
(2, 'Internet'),
(3, 'Server'),
(4, 'CCTV'),
(5, 'LAN'),
(13, 'Internet');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
CREATE TABLE IF NOT EXISTS `locations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `location` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `location`) VALUES
(1, 'Ortigas'),
(2, 'Edsa'),
(4, 'Akle'),
(5, 'Akle Gabihan');

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
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ping_logs`
--

INSERT INTO `ping_logs` (`id`, `ip_id`, `latency`, `status`, `created_at`) VALUES
(1, 34, 14, 'online', '2025-04-25 06:04:08'),
(2, 34, 11, 'online', '2025-04-25 06:04:15'),
(3, 34, 4, 'online', '2025-04-25 06:12:56'),
(4, 34, 5, 'online', '2025-04-25 06:13:14'),
(5, 34, 5, 'online', '2025-04-25 06:13:55'),
(6, 35, 0, 'offline', '2025-04-25 06:40:51'),
(7, 35, 0, 'offline', '2025-04-25 07:15:25'),
(8, 35, 0, 'offline', '2025-04-25 07:31:40'),
(9, 35, 0, 'offline', '2025-04-25 07:56:35'),
(10, 35, 0, 'offline', '2025-04-25 08:13:36'),
(11, 35, 0, 'offline', '2025-04-25 08:14:05'),
(12, 35, 0, 'offline', '2025-04-25 08:14:34'),
(13, 35, 0, 'offline', '2025-04-25 08:15:03'),
(14, 35, 0, 'offline', '2025-04-25 08:15:32'),
(15, 35, 0, 'offline', '2025-04-25 08:16:01'),
(16, 35, 0, 'offline', '2025-04-25 08:16:30'),
(17, 35, 0, 'offline', '2025-04-25 08:16:59'),
(18, 35, 0, 'offline', '2025-04-25 08:17:28'),
(19, 35, 0, 'offline', '2025-04-25 08:22:41');

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

-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 07, 2026 at 01:28 PM
-- Server version: 8.0.45
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `umu_events`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 0xF09F8E89,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon`) VALUES
(1, 'Academic', '🎓'),
(2, 'Sports', '⚽'),
(3, 'Cultural', '🎭'),
(4, 'Music', '🎵'),
(5, 'Religious', '✝️'),
(6, 'Health', '🏥'),
(7, 'Technology', '💻'),
(8, 'Social', '🤝');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `event_id`, `user_id`, `body`, `created_at`, `updated_at`) VALUES
(5, 6, 5, 'cannot miss out this', '2026-05-05 09:12:36', '2026-05-05 09:12:36'),
(6, 7, 1, 'COME IN PLENTY ITS THE LAST MASS', '2026-05-05 11:45:26', '2026-05-05 11:45:26'),
(7, 7, 7, 'come one come all', '2026-05-06 14:55:47', '2026-05-06 14:55:47');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `creator_id` int NOT NULL,
  `category_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_date` datetime NOT NULL,
  `is_free` tinyint(1) NOT NULL DEFAULT '1',
  `price` decimal(10,2) DEFAULT '0.00',
  `capacity` int DEFAULT NULL,
  `poster` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `creator_id`, `category_id`, `title`, `description`, `location`, `event_date`, `is_free`, `price`, `capacity`, `poster`, `status`, `rejection_reason`, `created_at`) VALUES
(6, 1, 8, 'MR&MRS UMU', 'An event for students to show case there talent and to get the school brand ambasseder', 'Masaka social center mainhall', '2026-05-16 11:30:00', 0, 19501.00, 250, 'poster_69f9b3e5a94791.94443091.jpg', 'approved', NULL, '2026-05-05 09:09:57'),
(7, 1, 5, 'CLOSING MASS', 'LAST MASS OF THE SEMISTER', 'CONFRENCE  ROOM 2', '2026-05-08 05:30:00', 1, 0.00, NULL, 'poster_69f9d820a30360.20266024.jpg', 'approved', NULL, '2026-05-05 11:44:32'),
(8, 1, 3, 'culture day', 'Iam my culture,my culture, my pride.', 'masaka campus grounds', '2026-06-27 11:00:00', 1, 0.00, NULL, 'poster_69fb5a37687363.12583818.jpg', 'approved', NULL, '2026-05-06 15:11:51'),
(9, 7, 2, 'intercampus competitions', 'an event to showcase our talents as students of different campuses', 'MASAKA CAMPUS', '2026-06-25 09:30:00', 1, 0.00, NULL, 'poster_69fb5bf9996749.51399396.jpg', 'approved', NULL, '2026-05-06 15:19:21');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('reminder','approval','rejection','comment','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `event_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `body`, `type`, `is_read`, `event_id`, `created_at`) VALUES
(4, 5, '✅ RSVP Confirmed', 'You\'ve successfully RSVPd for \"MR&MRS UMU\".', 'system', 1, 6, '2026-05-05 09:11:47'),
(5, 5, '⭐ Account Verified!', 'Your account has been verified. You can now create and submit events for approval.', 'system', 1, NULL, '2026-05-05 09:21:14'),
(6, 7, '✅ RSVP Confirmed', 'You\'ve successfully RSVPd for \"CLOSING MASS\".', 'system', 1, 7, '2026-05-06 14:53:49'),
(7, 7, '📅 Reminder: CLOSING MASS', 'This event you RSVPd for is coming up soon — 08 May 2026, 5:30 AM at CONFRENCE  ROOM 2.', 'reminder', 1, 7, '2026-05-06 14:58:16'),
(8, 7, '⭐ Account Verified!', 'Your account has been verified. You can now create and submit events for approval.', 'system', 1, NULL, '2026-05-06 15:12:40'),
(9, 7, '✅ RSVP Confirmed', 'You\'ve successfully RSVPd for \"culture day\".', 'system', 1, 8, '2026-05-06 15:13:20'),
(10, 1, '🆕 New Event Pending Approval', '\"intercampus competitions\" by AHABWE MELLON needs your review.', 'system', 0, 9, '2026-05-06 15:19:21'),
(11, 7, '✅ Event Approved!', '\"intercampus competitions\" has been approved and is now live on the platform.', 'approval', 1, 9, '2026-05-06 15:20:03'),
(12, 7, '📅 Reminder: CLOSING MASS', 'This event you RSVPd for is coming up soon — 08 May 2026, 5:30 AM at CONFRENCE  ROOM 2.', 'reminder', 0, 7, '2026-05-07 10:23:15'),
(13, 6, '✅ RSVP Confirmed', 'You\'ve successfully RSVPd for \"CLOSING MASS\".', 'system', 1, 7, '2026-05-07 10:24:17'),
(14, 6, '✅ RSVP Confirmed', 'You\'ve successfully RSVPd for \"culture day\".', 'system', 1, 8, '2026-05-07 10:24:22'),
(15, 6, '✅ RSVP Confirmed', 'You\'ve successfully RSVPd for \"MR&MRS UMU\".', 'system', 1, 6, '2026-05-07 10:24:34'),
(16, 6, '✅ RSVP Confirmed', 'You\'ve successfully RSVPd for \"intercampus competitions\".', 'system', 1, 9, '2026-05-07 10:24:40'),
(17, 6, '📅 Reminder: CLOSING MASS', 'This event you RSVPd for is coming up soon — 08 May 2026, 5:30 AM at CONFRENCE  ROOM 2.', 'reminder', 0, 7, '2026-05-07 10:35:36'),
(18, 8, '✅ RSVP Confirmed', 'You\'ve successfully RSVPd for \"CLOSING MASS\".', 'system', 0, 7, '2026-05-07 11:15:35'),
(19, 8, '✅ RSVP Confirmed', 'You\'ve successfully RSVPd for \"culture day\".', 'system', 0, 8, '2026-05-07 11:17:08'),
(20, 8, '✅ RSVP Confirmed', 'You\'ve successfully RSVPd for \"MR&MRS UMU\".', 'system', 0, 6, '2026-05-07 11:17:17'),
(21, 8, '📅 Reminder: CLOSING MASS', 'This event you RSVPd for is coming up soon — 08 May 2026, 5:30 AM at CONFRENCE  ROOM 2.', 'reminder', 0, 7, '2026-05-07 11:25:35');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rsvps`
--

DROP TABLE IF EXISTS `rsvps`;
CREATE TABLE IF NOT EXISTS `rsvps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `event_id` int NOT NULL,
  `rsvp_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rsvp` (`user_id`,`event_id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rsvps`
--

INSERT INTO `rsvps` (`id`, `user_id`, `event_id`, `rsvp_at`) VALUES
(5, 5, 6, '2026-05-05 09:11:47'),
(6, 7, 7, '2026-05-06 14:53:49'),
(7, 7, 8, '2026-05-06 15:13:20'),
(8, 6, 7, '2026-05-07 10:24:16'),
(9, 6, 8, '2026-05-07 10:24:22'),
(10, 6, 6, '2026-05-07 10:24:34'),
(11, 6, 9, '2026-05-07 10:24:40'),
(12, 8, 7, '2026-05-07 11:15:35'),
(13, 8, 8, '2026-05-07 11:17:08'),
(14, 8, 6, '2026-05-07 11:17:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reg_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','verified','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reg_number` (`reg_number`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `reg_number`, `full_name`, `email`, `password`, `role`, `profile_photo`, `is_active`, `created_at`) VALUES
(1, 'ADMIN001', 'System Administrator', 'admin@umu.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 1, '2026-05-05 08:45:47'),
(5, '2024-B072-33329', 'MAGEZI IVAN REGAN', 'magezi.reagan@stud.umu.ac', '$2y$10$PnZXJGyenDcTcFs3MFJnee5wiaJZiHrOwFdJ3DR3Yr6StajFoLtBa', 'verified', NULL, 1, '2026-05-05 08:59:02'),
(6, '2024-B072-33330', 'MUTAGUBYA DAVID OSCAR', 'mutagubyadavid.oscar@stud.umu.ac.ug', '$2y$10$zbzQNZPVWt2Hn1ZmN9scLeuHrDPsAyf7XndezYi8osinIE40XdhXG', 'student', NULL, 1, '2026-05-05 09:16:25'),
(7, '2024-B201-31156', 'AHABWE MELLON', 'ahabwe.mellon@stud.umu.ac.ug', '$2y$10$6nEfOqaPB1FM5OcKlM59A.5qeRCfD4gYoFd.4ANqw2SQVXznm6k7G', 'verified', NULL, 1, '2026-05-06 14:52:10'),
(8, '2024-B221-31814', 'Namuleme Florence', 'namuleme.florence@stud.umu.ac.ug', '$2y$10$gxgRC4n66VTv6fe77Hv.V.s6hYDzfUTy.cRvSoGtdTCcNcPpA/D.G', 'student', NULL, 1, '2026-05-07 11:09:43');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rsvps`
--
ALTER TABLE `rsvps`
  ADD CONSTRAINT `rsvps_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rsvps_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

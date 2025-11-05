-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 05, 2025 at 05:12 AM
-- Server version: 10.6.23-MariaDB
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `danidevc_ecom`
--
CREATE DATABASE IF NOT EXISTS `danidevc_ecom` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `danidevc_ecom`;

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `holiday_date`, `description`) VALUES
(6, '2025-10-15', 'DEEPAVALI'),
(7, '2025-10-20', 'Deepavali');

-- --------------------------------------------------------

--
-- Table structure for table `parcels`
--

CREATE TABLE `parcels` (
  `id` int(11) NOT NULL,
  `tracking_number` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `courier` varchar(255) NOT NULL,
  `weight` varchar(50) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `status` enum('Registered','In Transit','Ready for Collection','Collected','Late Collection') NOT NULL DEFAULT 'Registered',
  `pickup_otp` varchar(255) DEFAULT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `collected_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `late_fee` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `parcels`
--

INSERT INTO `parcels` (`id`, `tracking_number`, `user_id`, `recipient_name`, `courier`, `weight`, `dimensions`, `destination`, `status`, `pickup_otp`, `registered_at`, `collected_at`, `late_fee`) VALUES
(7, '12345', 5, 'Dani', 'JNT', NULL, NULL, NULL, 'Collected', NULL, '2025-09-27 19:46:33', '2025-09-27 20:03:45', 0.00),
(9, '34456', 1, 'Ariff Haiqal', 'JNT', NULL, NULL, NULL, 'Collected', NULL, '2025-09-29 06:42:22', '2025-09-29 08:02:54', 0.00),
(10, '34567', 6, 'Ahmad Hakimi', 'SPX', NULL, NULL, NULL, 'Collected', NULL, '2025-09-29 07:05:32', '2025-09-29 07:07:50', 0.00),
(11, '43576', 6, 'Ahmad Hakimi', 'POSLAJU', NULL, NULL, NULL, 'Collected', NULL, '2025-09-29 07:21:44', '2025-09-29 07:25:28', 0.00),
(12, '34833', 2, 'Aiman Hakimi', 'SPX', NULL, NULL, NULL, 'Collected', NULL, '2025-09-29 07:26:55', '2025-09-29 14:06:40', 0.00),
(13, '45374', 5, 'Dani', 'DHL', NULL, NULL, NULL, 'Collected', NULL, '2025-09-29 17:12:14', '2025-10-13 15:48:13', 2.00),
(14, '0123456789', 5, 'Dani', 'SPX', NULL, NULL, NULL, 'Collected', NULL, '2025-09-30 11:01:34', '2025-09-30 11:06:31', 1.00),
(15, '23456789', 3, 'IZZUL ISLAM BIN NOR FADZLI', 'JNT', '1', '2', 'KOPERASI', 'Collected', NULL, '2025-10-13 15:45:01', '2025-10-13 15:47:27', 1.00),
(16, '123', 7, 'AHMAD SHAMIL HAKIMI BIN NORAJIMI', 'JNT', NULL, NULL, NULL, 'Collected', NULL, '2025-10-13 15:57:18', '2025-10-13 16:05:52', 1.00),
(21, '456', 3, 'IZZUL ISLAM BIN NOR FADZLI', 'JNT', NULL, NULL, 'KOOP', 'Collected', NULL, '2025-10-13 16:32:46', '2025-10-13 17:06:00', 1.00),
(22, '234', 3, 'IZZUL ISLAM BIN NOR FADZLI', 'JNT', NULL, NULL, 'KOOP', 'Collected', NULL, '2025-10-14 02:25:33', '2025-10-14 02:29:59', 1.00),
(23, '23', 7, 'AHMAD SHAMIL HAKIMI BIN NORAJIMI', 'JNT', NULL, NULL, 'KOOP', 'Collected', NULL, '2025-10-14 02:48:56', '2025-10-14 02:49:55', 1.00),
(26, 'PG1-BTW-a-Z1', 3, 'IZZUL ISLAM (B3-2-2)', 'JNT', NULL, NULL, 'koop', 'Collected', NULL, '2025-10-14 03:35:04', '2025-10-14 03:37:26', 1.00),
(27, '25101310RAMKKA', 3, 'IZZUL ISLAM (B3-2-2)', 'JNT', NULL, NULL, 'Koop', 'Collected', NULL, '2025-10-15 05:45:56', '2025-10-15 05:47:46', 1.00),
(31, '0102409596', 10, 'DANIEL HAKEEM', 'SPX', NULL, NULL, NULL, 'Collected', NULL, '2025-10-15 17:15:37', '2025-10-15 17:28:09', 1.00),
(32, '01112417449', 10, 'DANIEL HAKEEM', 'JNT', NULL, NULL, NULL, 'Collected', NULL, '2025-10-15 17:18:21', '2025-10-16 02:13:05', 1.00),
(33, '0987654321', 10, 'DANIEL HAKEEM', 'POSLAJU', NULL, NULL, NULL, 'Collected', NULL, '2025-10-15 17:29:32', '2025-10-16 02:13:09', 1.00),
(34, 'mympa218995143', 11, 'KHAIRI RAHIMI (B4-1-2)', 'jnt', NULL, NULL, 'koop', 'Collected', NULL, '2025-10-16 02:15:58', '2025-10-16 02:17:17', 1.00),
(36, 'SPXMY05940886714A', 12, 'FARRIS AZWAR (B6-3-13)', 'JNT', NULL, NULL, 'KOOP', 'Collected', NULL, '2025-10-16 02:28:54', '2025-10-16 02:38:46', 1.00),
(37, 'D', 12, 'FARRIS AZWAR (B6-3-13)', 'D', NULL, NULL, 'D', 'Collected', NULL, '2025-10-16 03:04:23', '2025-10-16 03:04:43', 1.00),
(38, '678', 12, 'FARRIS AZWAR (B6-3-13)', 'SPX', NULL, NULL, 'KOOP', 'Collected', NULL, '2025-10-16 03:05:52', '2025-10-16 03:06:23', 1.00);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('email_body', '<h2>Parcel Arrival Notification</h2><p>Dear {recipient_name},</p><p>A parcel with tracking number <strong>{tracking_number}</strong> has been registered for you at the university co-op shop.</p><p>Please log in to your KoPickUp account to collect.</p>'),
('email_subject', 'Your Parcel Has Arrived at KoPickUp!'),
('late_fee_initial_day_fee', '1.00'),
('late_fee_recurring_amount', '0.50'),
('late_fee_recurring_days', '3');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','manager','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `student_id`, `password`, `role`, `created_at`) VALUES
(1, 'Ariff Haiqal', 'haiqal150305@gmail.com', NULL, '$2y$10$miofV3kOmurM0zGFll51u.0crS/2dryxTSLM6M2r91bug4xvgfcDW', 'user', '2025-09-24 17:08:56'),
(2, 'AIMAN HAKIMI', 'Kimi20@gmail.com', '', '$2y$10$JaGDyObLNAmisWfo2JFlG.PZBdajQn.NolYcMFbgoUHA1p77/3tLW', 'manager', '2025-09-24 17:13:49'),
(3, 'IZZUL ISLAM (B3-2-2)', 'izzul7005@gmail.com', '10DLS23F1039', '$2y$10$QvpkWZ4dfgWuxyDSNVwUiOc3Nyh1eb5cgH3MVIISTJ/1HpVs4kHK6', 'user', '2025-09-25 06:25:53'),
(5, 'dani', 'dani@gmail.com', NULL, '$2y$10$IX8.ZpF6LBBjMgtWntQ7neW3SIywEJ.ZVYII.lKN6NrYoZODxZdEC', 'admin', '2025-09-26 17:22:54'),
(6, 'Ahmad Hakimi', 'ahmad@gmail.com', NULL, '$2y$10$q0xYo0B0lD0WbAfPEEDiq.bTSyg9NCrr3L//tUTkTmdkjG5K437zS', 'user', '2025-09-29 07:01:20'),
(7, 'AHMAD SHAMIL HAKIMI BIN NORAJIMI', 'shamil.kimi.99@gmail.com', NULL, '$2y$10$yxncAr9OGBAfMA7AypnHJOcA/sNTu4tOh8h9Zwsn0.onsw/gzNUX2', 'user', '2025-09-30 13:26:23'),
(8, 'AHMAD ADAM (B3-1-2)', 'izzulmoxleu@gmail.com', '1010100101', '$2y$10$aapZ02ewFOu7cQ6C0EpONewzqySBGLxhsdalZ/6U1X5Th.hbWplVG', 'user', '2025-10-14 02:41:41'),
(9, 'AHMAD ADAM', 'izzulfadz@gmail.com', NULL, '$2y$10$CAHpqtJZC.ipnnSMjKAI4e/cQWO3BrAxzDx.UFvOg9uVlGMAYwBHG', 'user', '2025-10-14 02:43:50'),
(10, 'DANIEL HAKEEM', 'danhakeem140@gmail.com', NULL, '$2y$10$me1MYP.OhaM7k2I1n0XHPO7kcFZwuxBkFy3a0dVeQWXPnoe3zmWB.', 'user', '2025-10-15 17:16:43'),
(11, 'KHAIRI RAHIMI (B4-1-2)', 'nuraiman2212@gmail.com', NULL, '$2y$10$nVkZOYFUufrEH4RCRdp00uOnOG7xwBz3s3M4Sa8c4oub.rDe8g.16', 'user', '2025-10-16 02:10:58'),
(12, 'FARRIS AZWAR (B6-3-13)', '10dls23f1020@gmail.com', NULL, '$2y$10$6SCHrVaAokNnysH2HAGK9OokIKwswbvRtS7SGNrfcfftew0ANzwpS', 'user', '2025-10-16 02:20:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`holiday_date`);

--
-- Indexes for table `parcels`
--
ALTER TABLE `parcels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `parcels`
--
ALTER TABLE `parcels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `parcels`
--
ALTER TABLE `parcels`
  ADD CONSTRAINT `parcels_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

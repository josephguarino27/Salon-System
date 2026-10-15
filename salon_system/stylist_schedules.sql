-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 30, 2025 at 12:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `salon_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `stylist_schedules`
--

CREATE TABLE `stylist_schedules` (
  `id` int(11) NOT NULL,
  `stylist_id` int(11) NOT NULL,
  `day_of_week` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stylist_schedules`
--

INSERT INTO `stylist_schedules` (`id`, `stylist_id`, `day_of_week`, `start_time`, `end_time`, `is_available`) VALUES
(1, 1, 1, '09:00:00', '18:00:00', 1),
(2, 1, 2, '09:00:00', '18:00:00', 1),
(3, 1, 3, '09:00:00', '18:00:00', 1),
(4, 1, 4, '09:00:00', '18:00:00', 1),
(5, 1, 5, '09:00:00', '18:00:00', 1),
(6, 1, 6, '09:00:00', '18:00:00', 1),
(7, 2, 1, '09:00:00', '18:00:00', 1),
(8, 2, 2, '09:00:00', '18:00:00', 1),
(9, 2, 3, '09:00:00', '18:00:00', 1),
(10, 2, 4, '09:00:00', '18:00:00', 1),
(11, 2, 5, '09:00:00', '18:00:00', 1),
(12, 2, 6, '09:00:00', '18:00:00', 1),
(13, 3, 1, '09:00:00', '18:00:00', 1),
(14, 3, 2, '09:00:00', '18:00:00', 1),
(15, 3, 3, '09:00:00', '18:00:00', 1),
(16, 3, 4, '09:00:00', '18:00:00', 1),
(17, 3, 5, '09:00:00', '18:00:00', 1),
(18, 3, 6, '09:00:00', '18:00:00', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `stylist_schedules`
--
ALTER TABLE `stylist_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stylist_id` (`stylist_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `stylist_schedules`
--
ALTER TABLE `stylist_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `stylist_schedules`
--
ALTER TABLE `stylist_schedules`
  ADD CONSTRAINT `stylist_schedules_ibfk_1` FOREIGN KEY (`stylist_id`) REFERENCES `stylists` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

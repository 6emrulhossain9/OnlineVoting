-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 11, 2025 at 04:13 PM
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
-- Database: `online_voting`
--

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `position` varchar(100) NOT NULL,
  `event_id` int(11) NOT NULL,
  `votes` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`id`, `name`, `position`, `event_id`, `votes`) VALUES
(5, 'Alhaz', 'CR', 1, 0),
(6, 'Siam', 'GS', 3, 0),
(8, 'Rakib', 'CR', 1, 0),
(13, 'Emrul', 'President', 2, 0),
(14, 'Minhaz', 'President', 2, 0),
(15, 'Sifat', 'GS', 3, 0),
(17, 'Mmm', '', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `start_time`, `end_time`) VALUES
(1, 'CR Vote', '2025-09-10 10:00:00', '2025-09-10 22:00:00'),
(2, 'President Vote', '2025-09-08 16:17:00', '2025-09-08 16:20:00'),
(3, 'GS Vote', '2025-09-08 19:48:00', '2025-09-08 20:48:00'),
(4, 'Default Voting Event', '2023-01-01 00:00:00', '2024-12-31 23:59:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `department` varchar(100) NOT NULL,
  `section` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('voter','admin','candidate') NOT NULL DEFAULT 'voter'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `student_id`, `phone_number`, `department`, `section`, `password`, `role`) VALUES
(4, 'admin', '', '', '', '', '', '$2y$10$idw4VkfJY5vVZzwxHxeWDuoEG1ZabIg6aCTmcqpbkfPwULCOMOB4C', 'admin'),
(7, 'nothing', 'emrul2', '412303016099', '01753990690', 'CSE', '6F', '$2y$10$LjOhY4vv261UtIWPCajVJ.DoW6D.RYjk3C5YhIBH.M16RSlTw7eaW', 'voter'),
(8, 'rakib110', 'Rakib', '12', '01234567891', 'CSE', 'F', '$2y$10$KNIZJBMeXJGw9CvC4DJn0OSer5jIIMAeUt0spv3/lN4gjlfFs7AOe', 'voter'),
(9, 'minu', 'minhazul islam', '41230301611', '01712345678', 'CSE', '6F', '$2y$10$.wiVqKrayrs6/S7BUSeR5O/XebwRHxKBqQAGkyA7/dBsPi71rhFKe', 'voter'),
(11, 'no', '', '', '', '', '', '$2y$10$Y.PMyETTqhhztVLTMmMQp.85i08FxLK8L5NE8YY2DC1hVuBgBk5sG', 'voter'),
(12, 'hello', '', '', '', '', '', '$2y$10$moPtQOz/LxHASw8tGL50feL9b2zCFmb3zfLXM4LXXVZe9M5l8sgZC', 'voter'),
(13, 'MINHAZ', '', '', '', '', '', '$2y$10$Yvoco7vMWi//VKwVTIdoh.yh6aSM6s3uTsbY77eNjLsdl1RyKyn2.', 'voter');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `vote_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id_event_id` (`user_id`,`event_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2025 at 10:53 PM
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
-- Database: `mvs`
--

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `candidate_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `candidate_name` varchar(255) NOT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `votes_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`candidate_id`, `position_id`, `class_id`, `candidate_name`, `slogan`, `votes_count`, `created_at`) VALUES
(12, 18, 5, 'ABASI HASSAN', 'ejden hj ef e', 3, '2025-10-14 20:17:01'),
(13, 18, 8, 'YOHANE MPOLE', 'ejdneje je fferfrf e', 3, '2025-10-14 20:17:23'),
(14, 19, 3, 'ROWLAND ANDREW', 'jndj ej jcrfd', 1, '2025-10-14 20:17:53'),
(15, 19, 3, 'JOHN KAPESA', 'jnjdej eeeeeefj', 5, '2025-10-14 20:18:24'),
(16, 20, 7, 'JOSHUA MBILINYI', 'jnjdcj jrdcjnc', 6, '2025-10-14 20:18:53'),
(17, 20, 8, 'ANTHONY MWAKITEBE', 'jnjenjfenfejf', 0, '2025-10-14 20:19:25');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `class_name`, `created_at`) VALUES
(1, 'FORM ONE', '2025-10-14 20:00:51'),
(2, 'FORM TWO', '2025-10-14 20:00:51'),
(3, 'FORM THREE', '2025-10-14 20:00:51'),
(4, 'FORM FOUR', '2025-10-14 20:00:51'),
(5, 'FORM FIVE HGL', '2025-10-14 20:00:51'),
(6, 'FORM FIVE PMC', '2025-10-14 20:00:51'),
(7, 'FORM FIVE PCM', '2025-10-14 20:00:51'),
(8, 'FORM FIVE PCB', '2025-10-14 20:00:51'),
(9, 'FORM SIX HGL', '2025-10-14 20:00:51'),
(10, 'FORM SIX PMC', '2025-10-14 20:00:51'),
(11, 'FORM SIX PCM', '2025-10-14 20:00:51'),
(12, 'FORM SIX PCB', '2025-10-14 20:00:51');

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `position_id` int(11) NOT NULL,
  `position_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`position_id`, `position_name`, `created_at`) VALUES
(18, 'HEAD PREFECT', '2025-10-14 20:14:58'),
(19, 'VICE HEAD PREFECT', '2025-10-14 20:15:06'),
(20, 'ACADEMIC PREFECT', '2025-10-14 20:15:16');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `student_id` varchar(20) NOT NULL,
  `pin_code` varchar(255) NOT NULL,
  `role` enum('voter','admin') NOT NULL DEFAULT 'voter',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `class_id`, `student_id`, `pin_code`, `role`, `created_at`) VALUES
(14, 5, '1234', '$2y$10$A53TkEndlPfLcPlwwMbwSeL.qp/R06twro3PJd.6lHYoGXGqsUBVq', 'admin', '2025-10-14 20:04:08'),
(15, 4, '2345', '$2y$10$XnbdP/2m6qQq/szrdBO8Jux3tLO/gouDbzXk/0Z9qKKCShddtsxja', 'voter', '2025-10-14 20:04:24'),
(16, 1, '3456', '$2y$10$APCWMmBXeipIB4M1.W0XKeIwN5D.RxogReHxZIfksyTt/vLGTN7dS', 'voter', '2025-10-14 20:04:58'),
(17, 12, '4567', '$2y$10$9M.wbm5lQrgMogaf9/fAm.Qz/2x5EbflvxXUZTG7n70YHnp93ERpO', 'voter', '2025-10-14 20:05:11'),
(18, 3, '5678', '$2y$10$2BOg4T9An68VPjgSd1uFIOoMsl/aEo95UPneDoPZkCw0TIwTf/N96', 'voter', '2025-10-14 20:05:34'),
(19, 10, '6789', '$2y$10$bczrDmj0iEhnWAZiIBsx/OMJQ9h.bGX6/gvKxbt5f06EFB0.Lo.JK', 'voter', '2025-10-14 20:06:03'),
(20, 3, '7890', '$2y$10$AxNdZERf88/lNQRSnqyPEelffxHX7yIe2/YPadZBU8aPx/X4rtO6e', 'voter', '2025-10-14 20:06:19');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `vote_id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `vote_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `votes`
--

INSERT INTO `votes` (`vote_id`, `voter_id`, `candidate_id`, `position_id`, `vote_time`) VALUES
(10, 15, 16, 20, '2025-10-14 20:28:21'),
(11, 15, 12, 18, '2025-10-14 20:28:21'),
(12, 15, 15, 19, '2025-10-14 20:28:21'),
(13, 16, 16, 20, '2025-10-14 20:30:25'),
(14, 16, 13, 18, '2025-10-14 20:30:25'),
(15, 16, 15, 19, '2025-10-14 20:30:25'),
(16, 17, 16, 20, '2025-10-14 20:30:46'),
(17, 17, 12, 18, '2025-10-14 20:30:46'),
(18, 17, 15, 19, '2025-10-14 20:30:46'),
(19, 18, 16, 20, '2025-10-14 20:31:02'),
(20, 18, 13, 18, '2025-10-14 20:31:02'),
(21, 18, 15, 19, '2025-10-14 20:31:02'),
(22, 19, 16, 20, '2025-10-14 20:31:21'),
(23, 19, 13, 18, '2025-10-14 20:31:21'),
(24, 19, 15, 19, '2025-10-14 20:31:21'),
(25, 20, 16, 20, '2025-10-14 20:31:38'),
(26, 20, 12, 18, '2025-10-14 20:31:38'),
(27, 20, 14, 19, '2025-10-14 20:31:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`candidate_id`),
  ADD KEY `position_id` (`position_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `name` (`class_name`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`position_id`),
  ADD UNIQUE KEY `name` (`position_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`vote_id`),
  ADD UNIQUE KEY `unique_vote` (`voter_id`,`position_id`),
  ADD KEY `voter_id` (`voter_id`),
  ADD KEY `candidate_id` (`candidate_id`),
  ADD KEY `position_id` (`position_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `candidate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL;

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`voter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `votes_ibfk_3` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `votes_ibfk_4` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2026 at 09:27 PM
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
-- Database: `manomiestas`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `entity` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `entity`, `entity_id`, `action`, `actor_id`, `created_at`) VALUES
(1, 'issue', 1, 'ISSUE_CREATE', 2, '2025-12-19 19:41:00'),
(2, 'issue', 1, 'ISSUE_UPDATE', 2, '2025-12-19 19:41:44'),
(3, 'issue', 1, 'COMMENT_ADD', 2, '2025-12-19 19:43:01'),
(4, 'issue', 1, 'ISSUE_UPDATE', 2, '2025-12-19 19:49:29'),
(5, 'issue', 1, 'ISSUE_UPDATE', 2, '2025-12-19 19:49:50'),
(6, 'issue', 1, 'COMMENT_ADD', 2, '2025-12-19 19:50:54'),
(7, 'issue', 1, 'ISSUE_UPDATE', 2, '2025-12-19 20:19:55'),
(8, 'issue', 1, 'ISSUE_UPDATE', 2, '2025-12-19 20:20:11'),
(9, 'issue', 2, 'ISSUE_CREATE', 1, '2025-12-19 20:26:32'),
(10, 'issue', 3, 'ISSUE_CREATE', 1, '2025-12-19 20:29:11'),
(11, 'issue', 1, 'COMMENT_ADD', 1, '2025-12-19 20:31:49'),
(12, 'issue', 3, 'STATUS_CHANGE', 1, '2025-12-19 20:35:52'),
(13, 'issue', 1, 'STATUS_CHANGE', 1, '2025-12-19 20:36:17'),
(14, 'issue', 3, 'ISSUE_UPDATE', 1, '2025-12-19 20:50:18'),
(15, 'issue', 3, 'STATUS_CHANGE', 1, '2025-12-19 22:46:09'),
(16, 'issue', 4, 'ISSUE_CREATE', 2, '2025-12-19 23:32:16'),
(17, 'issue', 4, 'COMMENT_ADD', 2, '2025-12-19 23:32:33'),
(18, 'issue', 4, 'COMMENT_DELETE', 2, '2025-12-19 23:32:37'),
(19, 'issue', 4, 'ISSUE_DELETE', 1, '2025-12-20 13:50:06'),
(20, 'issue', 4, 'ISSUE_DELETE', 1, '2025-12-20 13:50:16'),
(21, 'issue', 2, 'ISSUE_DELETE', 1, '2025-12-20 13:50:53'),
(22, 'issue', 2, 'ISSUE_DELETE', 1, '2025-12-20 13:50:58'),
(23, 'issue', 1, 'ISSUE_DELETE', 1, '2025-12-20 13:51:02'),
(24, 'issue', 3, 'ISSUE_UPDATE', 1, '2025-12-20 13:51:11'),
(25, 'issue', 5, 'ISSUE_CREATE', 2, '2025-12-20 13:53:44'),
(26, 'issue', 6, 'ISSUE_CREATE', 2, '2025-12-20 13:55:39'),
(27, 'issue', 7, 'ISSUE_CREATE', 2, '2025-12-20 13:57:05'),
(28, 'issue', 7, 'COMMENT_ADD', 2, '2025-12-20 13:57:58'),
(29, 'issue', 6, 'COMMENT_ADD', 2, '2025-12-20 13:58:30'),
(30, 'issue', 7, 'STATUS_CHANGE', 1, '2025-12-20 13:59:21'),
(31, 'issue', 7, 'COMMENT_ADD', 1, '2025-12-20 13:59:48'),
(32, 'issue', 8, 'ISSUE_CREATE', 3, '2025-12-21 09:12:39'),
(33, 'issue', 8, 'COMMENT_ADD', 3, '2025-12-21 09:12:53'),
(34, 'issue', 8, 'ISSUE_UPDATE', 3, '2025-12-21 09:13:08'),
(35, 'issue', 9, 'ISSUE_CREATE', 3, '2026-04-05 10:07:05'),
(36, 'issue', 9, 'COMMENT_ADD', 3, '2026-04-05 10:20:14'),
(37, 'issue', 9, 'COMMENT_DELETE', 3, '2026-04-05 10:20:19'),
(38, 'issue', 9, 'ISSUE_UPDATE', 3, '2026-04-05 10:22:04'),
(39, 'issue', 9, 'STATUS_CHANGE', 1, '2026-04-05 10:34:13'),
(40, 'issue', 9, 'ISSUE_DELETE', 1, '2026-04-05 10:36:32'),
(41, 'issue', 8, 'COMMENT_ADD', 3, '2026-04-05 10:44:45'),
(42, 'issue', 8, 'COMMENT_DELETE', 3, '2026-04-05 10:44:50'),
(43, 'issue', 10, 'ISSUE_CREATE', 3, '2026-04-05 11:08:20'),
(44, 'issue', 11, 'ISSUE_CREATE', 3, '2026-04-05 11:19:09'),
(45, 'issue', 11, 'ISSUE_DELETE', 1, '2026-04-05 11:19:33'),
(46, 'issue', 10, 'ISSUE_DELETE', 1, '2026-04-05 11:19:44'),
(47, 'issue', 12, 'ISSUE_CREATE', 1, '2026-05-21 16:22:26'),
(48, 'issue', 13, 'ISSUE_CREATE', 1, '2026-05-21 16:24:34'),
(50, 'issue', 13, 'COMMENT_ADD', 1, '2026-06-02 21:05:24');

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

CREATE TABLE `issues` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(191) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `lat` decimal(9,6) NOT NULL,
  `lng` decimal(9,6) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` enum('NEW','IN_PROGRESS','RESOLVED','REJECTED') NOT NULL DEFAULT 'NEW',
  `assignee_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `user_id`, `title`, `description`, `category`, `lat`, `lng`, `address`, `status`, `assignee_id`, `created_at`, `updated_at`) VALUES
(3, 1, 'Pastatai', 'bbb', 'Pastatai', 54.877474, 23.865223, '14, Slėnio g., Kazliškiai, Ringaudų seniūnija, Kauno rajono savivaldybė, Kauno apskritis, 46398, Lietuva', 'NEW', NULL, '2025-12-19 20:29:11', '2025-12-20 13:51:11'),
(5, 2, 'Remontas', 'Remontas kelyje, darykit kazka', 'Remontas', 54.901216, 23.891219, 'Humana, 3, Jonavos g., Senamiestis, Centro seniūnija, Kaunas, Kauno miesto savivaldybė, Kauno apskritis, 44269, Lietuva', 'NEW', NULL, '2025-12-20 13:53:44', '2025-12-20 13:53:44'),
(6, 2, 'Sezoninė', 'Del buvusios audros nukrito medziai, atsargiai', 'Sezoninė', 54.897391, 23.930583, 'Parodos g., Karmelitai, Centro seniūnija, Kaunas, Kauno miesto savivaldybė, Kauno apskritis, 44214, Lietuva', 'NEW', NULL, '2025-12-20 13:55:39', '2025-12-20 13:55:39'),
(7, 2, 'Gyvūnai', 'Briedis upeje :/', 'Gyvūnai', 54.893134, 23.912001, 'Nemuno krantinės dviračių takas, Naujamiestis, Centro seniūnija, Kaunas, Kauno miesto savivaldybė, Kauno apskritis, 44299, Lietuva', 'IN_PROGRESS', NULL, '2025-12-20 13:57:05', '2025-12-20 13:59:21'),
(8, 3, 'Gyvūnai', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus consequat feugiat vulputate. Aliquam sodales tincidunt nisl, ac pharetra mi efficitur a. Integer consectetur tristique fringilla. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Duis auctor, quam vel sagittis dapibus, quam libero ornare risus, vel condimentum justo massa ut augue. Nulla id massa elit. Nulla facilisi. Phasellus eget accumsan dui. Donec nec massa et massa blandit vehicula. Pellentesque ac ante augue. In neque risus, imperdiet at purus sed, elementum viverra enim. Curabitur mauris massa, eleifend ut venenatis sed, pellentesque ac mauris. Aenean lorem felis, tristique vel sapien ut, pulvinar aliquam augue.', 'Gyvūnai', 54.893893, 23.952609, 'Vileikos g., Aukštieji Šančiai, Šančių seniūnija, Kaunas, Kauno miesto savivaldybė, Kauno apskritis, 44402, Lietuva', 'NEW', NULL, '2025-12-21 09:12:39', '2025-12-21 09:13:08'),
(12, 1, 'Gyvūnai', 'Cat on the field', 'Gyvūnai', 54.897422, 23.937342, 'S. Dariaus ir S. Girėno stadionas, 5, Perkūno al., Žaliakalnis, Žaliakalnio seniūnija, Kaunas, Kauno miesto savivaldybė, Kauno apskritis, 44225, Lietuva', 'NEW', NULL, '2026-05-21 16:22:26', '2026-05-21 16:22:26'),
(13, 1, 'Pažeidimai', 'Netvarka', 'Pažeidimai', 54.919105, 23.951038, 'S. Žukausko g., Kalniečiai, Eigulių seniūnija, Kaunas, Kauno miesto savivaldybė, Kauno apskritis, 50118, Lietuva', 'NEW', NULL, '2026-05-21 16:24:34', '2026-05-21 16:24:34');

-- --------------------------------------------------------

--
-- Table structure for table `issue_comments`
--

CREATE TABLE `issue_comments` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `issue_comments`
--

INSERT INTO `issue_comments` (`id`, `issue_id`, `user_id`, `text`, `created_at`) VALUES
(5, 7, 2, 'Jau nezinau kiek laiko jis cia', '2025-12-20 13:57:58'),
(6, 6, 2, 'nu jo daug zalos', '2025-12-20 13:58:30'),
(7, 7, 1, 'Busena atnaujinta, informacija perduota', '2025-12-20 13:59:48'),
(8, 8, 3, 'problema', '2025-12-21 09:12:53'),
(12, 13, 1, 'Siuksles surinktos', '2026-06-02 21:05:24');

-- --------------------------------------------------------

--
-- Table structure for table `issue_photos`
--

CREATE TABLE `issue_photos` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `issue_photos`
--

INSERT INTO `issue_photos` (`id`, `issue_id`, `file_path`, `uploaded_at`) VALUES
(10, 3, 'uploads/2025/12/issue_69459977e65859.81551637.jpg', '2025-12-19 20:29:11'),
(16, 5, 'uploads/2025/12/issue_69471ae8c6b6c3.22297929.png', '2025-12-20 13:53:44'),
(17, 6, 'uploads/2025/12/issue_69471b5b2ac8c5.74867451.png', '2025-12-20 13:55:39'),
(18, 6, 'uploads/2025/12/issue_69471b5b2b1c16.86816211.png', '2025-12-20 13:55:39'),
(19, 6, 'uploads/2025/12/issue_69471b5b2b6bc1.87854678.png', '2025-12-20 13:55:39'),
(20, 7, 'uploads/2025/12/issue_69471bb1173a07.22197544.png', '2025-12-20 13:57:05'),
(21, 8, 'uploads/2025/12/issue_69482a878171b9.07292684.png', '2025-12-21 09:12:39'),
(22, 8, 'uploads/2025/12/issue_69482a8781dbe1.26048612.png', '2025-12-21 09:12:39'),
(28, 12, 'uploads/2026/05/issue_6a11ba6fe343c1.97515070.jpg', '2026-05-21 16:22:26'),
(29, 13, 'uploads/2026/05/issue_6a11ba7888e359.01540000.png', '2026-05-21 16:24:34');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'ADMIN'),
(2, 'USER');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(191) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `city` varchar(64) DEFAULT NULL,
  `status` enum('ACTIVE','BLOCKED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `full_name`, `phone`, `city`, `status`, `created_at`) VALUES
(1, 'admin@example.com', '$2y$10$hgFR/H/FKkn0vI7RjT8ZZud4i3MqrqB1z9YWymAHyr43wXJ3Xv2sm', NULL, NULL, 'Kaunas', 'ACTIVE', '2025-12-17 16:18:59'),
(2, 'test@gmail.com', '$2y$10$4APjBLtPvEmD6TmA7VBCYeM4Hqn0gewIy2.BouN4e6mvGDX3C1Xs.', NULL, NULL, 'Kaunas', 'ACTIVE', '2025-12-19 19:40:23'),
(3, 'test2@example.com', '$2y$10$R6vBe9r3Kdmeu13gCubjyOUfDRANIDoRLCFtjNXDS1tXXSkFjtXwW', NULL, '+370 7777777', 'Kaunas', 'ACTIVE', '2025-12-21 09:12:02');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1, 1),
(2, 2),
(3, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_entity_entity_id` (`entity`,`entity_id`),
  ADD KEY `idx_audit_actor` (`actor_id`),
  ADD KEY `idx_audit_created_at` (`created_at`);

--
-- Indexes for table `issues`
--
ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_issues_assignee` (`assignee_id`),
  ADD KEY `idx_issues_status_created_at` (`status`,`created_at`),
  ADD KEY `idx_issues_category` (`category`),
  ADD KEY `idx_issues_user` (`user_id`);

--
-- Indexes for table `issue_comments`
--
ALTER TABLE `issue_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_issue_comments_user` (`user_id`),
  ADD KEY `idx_issue_comments_issue_created_at` (`issue_id`,`created_at`);

--
-- Indexes for table `issue_photos`
--
ALTER TABLE `issue_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_issue_photos_issue` (`issue_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `fk_user_roles_role` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `issue_comments`
--
ALTER TABLE `issue_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `issue_photos`
--
ALTER TABLE `issue_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_audit_log_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `issues`
--
ALTER TABLE `issues`
  ADD CONSTRAINT `fk_issues_assignee` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_issues_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `issue_comments`
--
ALTER TABLE `issue_comments`
  ADD CONSTRAINT `fk_issue_comments_issue` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `issue_photos`
--
ALTER TABLE `issue_photos`
  ADD CONSTRAINT `fk_issue_photos_issue` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

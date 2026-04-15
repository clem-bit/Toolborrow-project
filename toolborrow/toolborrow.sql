-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 15, 2026 at 09:58 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toolborrow`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon`) VALUES
(1, 'Power Tools', '⚡'),
(2, 'Hand Tools', '🔨'),
(3, 'Measuring', '📐'),
(4, 'Gardening', '🌱'),
(5, 'Safety', '🦺'),
(6, 'Electrical', '💡'),
(7, 'Plumbing', '🚿'),
(8, 'Woodworking', '🪵');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `borrowed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_date` date NOT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','returned','overdue') DEFAULT 'active',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `tool_id`, `borrowed_at`, `due_date`, `returned_at`, `status`, `notes`) VALUES
(1, 2, 5, '2026-03-26 14:39:13', '2026-03-27', '2026-04-03 14:22:05', 'returned', NULL),
(2, 2, 5, '2026-03-26 14:42:42', '2026-03-28', '2026-04-03 14:21:21', 'returned', 'grind'),
(3, 3, 22, '2026-04-11 14:41:51', '2026-04-18', '2026-04-11 14:43:08', 'returned', NULL),
(4, 3, 21, '2026-04-11 14:42:15', '2026-04-18', '2026-04-11 14:43:05', 'returned', NULL),
(5, 3, 2, '2026-04-11 14:42:26', '2026-04-23', '2026-04-11 14:43:11', 'returned', NULL),
(6, 3, 2, '2026-04-11 14:44:44', '2026-04-12', '2026-04-15 14:13:35', 'returned', 'work'),
(7, 2, 7, '2026-04-11 14:46:19', '2026-04-13', NULL, 'overdue', 'work'),
(8, 5, 3, '2026-04-11 14:51:46', '2026-04-18', NULL, 'active', NULL),
(9, 5, 22, '2026-04-11 14:52:31', '2026-04-13', NULL, 'overdue', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_log`
--

CREATE TABLE `maintenance_log` (
  `id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `performed_by` int(11) DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('due_reminder','overdue','reservation','maintenance') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `reserved_from` date NOT NULL,
  `reserved_to` date NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tools`
--

CREATE TABLE `tools` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `quantity_available` int(11) NOT NULL DEFAULT 1,
  `status` enum('available','borrowed','maintenance') NOT NULL DEFAULT 'available',
  `condition_rating` enum('excellent','good','fair','poor') DEFAULT 'good',
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tools`
--

INSERT INTO `tools` (`id`, `name`, `category_id`, `serial_number`, `description`, `quantity`, `quantity_available`, `status`, `condition_rating`, `image_url`, `created_at`) VALUES
(1, 'Cordless Drill', 1, 'SN-001', '18V, includes full bit set', 3, 3, 'available', 'excellent', NULL, '2026-03-20 01:50:48'),
(2, 'Circular Saw', 1, 'SN-002', '7.25 inch blade, corded', 2, 2, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(3, 'Orbital Sander', 1, 'SN-003', '5-inch random orbit, variable speed', 2, 1, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(4, 'Jigsaw', 1, 'SN-004', 'Variable speed, orbital action', 1, 1, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(5, 'Angle Grinder', 1, 'SN-005', '4.5-inch disc, 11000 RPM', 2, 2, 'available', 'fair', NULL, '2026-03-20 01:50:48'),
(6, 'Hammer', 2, 'SN-006', '16oz claw hammer', 5, 5, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(7, 'Wrench Set', 2, 'SN-007', 'SAE and metric, 22-piece combo', 2, 1, 'available', 'excellent', NULL, '2026-03-20 01:50:48'),
(8, 'Pliers Set', 2, 'SN-008', 'Needle-nose, slip-joint and locking', 3, 3, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(9, 'Screwdriver Set', 2, 'SN-009', 'Phillips and flathead, 12-piece', 4, 4, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(10, 'Tape Measure', 3, 'SN-010', '25ft, auto-locking', 4, 4, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(11, 'Spirit Level', 3, 'SN-011', '48-inch aluminium, 3 vials', 2, 2, 'available', 'excellent', NULL, '2026-03-20 01:50:48'),
(12, 'Laser Measure', 3, 'SN-012', 'Digital, 100ft range, ±1/16 inch accuracy', 1, 1, 'available', 'excellent', NULL, '2026-03-20 01:50:48'),
(13, 'Leaf Blower', 4, 'SN-013', 'Battery powered, 500 CFM', 2, 2, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(14, 'Pruning Shears', 4, 'SN-014', 'Bypass style, hardened steel', 3, 3, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(15, 'Garden Spade', 4, 'SN-015', 'Long handle, carbon steel blade', 3, 3, 'available', 'fair', NULL, '2026-03-20 01:50:48'),
(16, 'Safety Goggles', 5, 'SN-016', 'Anti-fog, splash-proof', 6, 6, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(17, 'Hard Hat', 5, 'SN-017', 'ANSI Type I, Class E rated', 4, 4, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(18, 'Heat Gun', 6, 'SN-018', 'Dual temp 300C / 500C settings', 1, 1, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(19, 'Wire Stripper', 6, 'SN-019', 'Auto-adjusting, 10-24 AWG', 2, 2, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(20, 'Pipe Wrench', 7, 'SN-020', '14-inch aluminium, adjustable jaw', 2, 2, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(21, 'Pipe Cutter', 7, 'SN-021', 'Cuts copper and PVC up to 2 inch', 1, 1, 'available', 'excellent', NULL, '2026-03-20 01:50:48'),
(22, 'Chisel Set', 8, 'SN-022', '6-piece bevel-edge, hardened steel with mallet', 2, 1, 'available', 'good', NULL, '2026-03-20 01:50:48'),
(23, 'Router', 8, 'SN-023', '2.25HP fixed base, 1/4 and 1/2 inch collets', 1, 1, 'available', 'good', NULL, '2026-03-20 01:50:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Admin1', 'admin@toolborrow.com', '$2y$12$Sc2A3m8BpzR3kL6wbBV6TO1p0l.CQ4/1SKvzvCe1O86zR3mbXdZm.', 'admin', '2026-03-20 01:50:48'),
(2, 'Clement Alake', 'alakeclementakinbolarinwa@gmail.com', '$2y$12$6p5tVL37P/ROJEi.s2BgCu9d03TEKcqnsM7KEl9Vf6l4J2YE8cpQq', 'user', '2026-03-26 13:20:28'),
(3, 'John James', 'johndoe@outlook.com', '$2y$12$6FnKtCfcx3eu5wy2daV6QO/DZBNAixp.QWufYAnxTJMXPvezvSACi', 'user', '2026-04-11 14:41:26'),
(4, 'Julia James', 'admin2@toolborrow.com', '$2y$12$l5lY0AAacMh400jiKD703eEZVqkZY4U2W8hI7xD.KQMVhs7TwsxZu', 'admin', '2026-04-11 14:48:01'),
(5, 'Prag Hype', 'pg@email.com', '$2y$12$kSiOoig3Yabl2AtgY7xdAO80HwqmMyEd0oSe/NyFHiUrayzLA5uZu', 'user', '2026-04-11 14:50:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loans_user` (`user_id`),
  ADD KEY `idx_loans_tool` (`tool_id`),
  ADD KEY `idx_loans_status` (`status`);

--
-- Indexes for table `maintenance_log`
--
ALTER TABLE `maintenance_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `performed_by` (`performed_by`),
  ADD KEY `idx_maint_tool` (`tool_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_res_tool` (`tool_id`);

--
-- Indexes for table `tools`
--
ALTER TABLE `tools`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

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
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `maintenance_log`
--
ALTER TABLE `maintenance_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tools`
--
ALTER TABLE `tools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_log`
--
ALTER TABLE `maintenance_log`
  ADD CONSTRAINT `maintenance_log_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_log_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tools`
--
ALTER TABLE `tools`
  ADD CONSTRAINT `tools_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

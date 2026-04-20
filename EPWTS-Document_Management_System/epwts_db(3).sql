-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2026 at 04:59 AM
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
-- Database: `epwts_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `backup_logs`
--

CREATE TABLE `backup_logs` (
  `id` int(11) NOT NULL,
  `backup_type` enum('database','files','document_deletion') NOT NULL,
  `created_by` varchar(50) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `file_count` int(11) DEFAULT 0,
  `status` enum('success','failed','partial') DEFAULT 'success',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `backup_logs`
--

INSERT INTO `backup_logs` (`id`, `backup_type`, `created_by`, `file_path`, `file_size`, `file_count`, `status`, `notes`, `created_at`) VALUES
(1, 'database', 'admin', '../../backups/epwts_db_2026-04-11_03-07-32.sql', '8699', 0, 'success', NULL, '2026-04-11 01:07:32'),
(2, 'files', 'admin', '../../backups/files/backup_2026-04-11_03-11-21/', NULL, 5, 'success', NULL, '2026-04-11 01:11:21'),
(3, 'database', 'admin', '../../backups/epwts_db_2026-04-11_03-11-43.sql', '9290', 0, 'success', NULL, '2026-04-11 01:11:43');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_documents`
--

CREATE TABLE `deleted_documents` (
  `id` int(11) NOT NULL,
  `doc_id` varchar(20) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `doc_type` varchar(50) DEFAULT NULL,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `upload_date` datetime DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `deleted_by` varchar(50) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `doc_id` varchar(20) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `doc_type` enum('Contracts','Drawings','Award Letters','Profiles','Others') DEFAULT NULL,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `upload_date` datetime DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `doc_id`, `file_name`, `file_path`, `section`, `doc_type`, `uploaded_by`, `upload_date`, `expiry_date`, `description`) VALUES
(1, 'AB260504001', 'page 4.png', '../uploads/page 4.png', 'AB', NULL, NULL, '2026-04-05 10:13:07', NULL, NULL),
(3, 'CW260504002', 'page 8.png', '../uploads/page 8.png', 'CW', NULL, NULL, '2026-04-05 10:23:08', NULL, NULL),
(4, 'AB260504004', ' ABbbcc7732', '../uploads/Infectious Disease Test 1.png', 'Arch_Building', NULL, NULL, '2026-04-05 11:57:14', NULL, NULL),
(5, 'CW260604001', 'Civil Works First File Upload', '../uploads/Serology 1.png', 'Civil_Works', NULL, NULL, '2026-04-06 23:05:04', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `doc_id` varchar(20) DEFAULT NULL,
  `requested_by` varchar(50) DEFAULT NULL,
  `request_type` enum('View','Delete') DEFAULT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `doc_id`, `requested_by`, `request_type`, `status`, `reason`) VALUES
(1, '4', 'ab', 'View', '', 'I wanted to view this document for the archietechturel project'),
(2, '4', 'ab', '', 'Approved', 'There is a mistake so I need to edit this document'),
(3, '4', 'ab', 'View', '', 'i need to view'),
(4, '4', 'ab', 'View', '', 'I need to view this'),
(5, '4', 'ab', '', 'Approved', 'I need to edit this'),
(6, '4', 'ab', 'Delete', 'Declined', 'Duplicate file'),
(7, '4', 'ab', '', 'Declined', 'Let me edit this'),
(8, '4', 'ab', '', 'Declined', 'Let me edit this'),
(9, '4', 'ab', '', 'Declined', 'Let me edit this'),
(10, '5', 'cw', 'View', 'Approved', 'Test View'),
(11, '4', 'ab', 'View', 'Approved', 'Test 4');

-- --------------------------------------------------------

--
-- Table structure for table `restore_logs`
--

CREATE TABLE `restore_logs` (
  `id` int(11) NOT NULL,
  `restore_type` enum('database','files','document') NOT NULL,
  `restored_from` varchar(255) DEFAULT NULL,
  `restored_by` varchar(50) NOT NULL,
  `status` enum('success','failed','partial') DEFAULT 'success',
  `notes` text DEFAULT NULL,
  `restored_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Arch_Building','Civil_Works','Director','Admin','Energy','Super_Admin','Doc_Controller') NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`) VALUES
(1, 'admin', '$2y$10$nplubDaL7Hgyc6gLY2fMJue83LE6jW1fzhlNoiIlqVAnK7IC2BYqe', 'Super_Admin', 'Brian Willie'),
(2, 'doc', '$2y$10$ktkN8KPNyCx2S3U/ZA/rZuURfK7aJ86sxRFRrHLRTUzEj9sFl5pbq', 'Doc_Controller', 'Document Controller - K James'),
(3, 'ckb', '$2y$10$hz.jISfdii2jjeHI8BWslOP5rdkA3Ev5oQ08mqYYyflXj4Z57GEp6', 'Director', 'Charles K. Bannah'),
(6, 'admin123', '$2y$10$kltJdIn5IX54rEYlhUxxrui20iY/2cltplPKQTUs.K8/bmTVYmMc.', 'Admin', 'Kinelda Admin'),
(7, 'cw', '$2y$10$0tNhBy.AIDaO0l8399VS3esspH1Y1mXo18ntGq8IlwyS9RLqjk046', 'Civil_Works', 'Civil Works'),
(8, 'ab', '$2y$10$s4kSkvbZaONK6/zs2pxDkOlUpaak/ta9vJQ.fQMZqH8ksrmuoR9eK', 'Arch_Building', 'Archie & Building'),
(9, 'energy', '$2y$10$xjra5bpyGfv22qpU7Ha16ua5MRZHFllKMnpKpFca9jcRKvcoJWbwG', 'Energy', 'Energy Test'),
(10, 'test', '$2y$10$Puq/e5JJ38XeWb.o/7BM6uuIrbljq1KZCemfQ.xbwChOSoaDobcxq', 'Super_Admin', 'Test');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_type` enum('view','edit','delete','approve') NOT NULL,
  `granted_by` varchar(50) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backup_logs`
--
ALTER TABLE `backup_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_backup_type` (`backup_type`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `deleted_documents`
--
ALTER TABLE `deleted_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doc_id` (`doc_id`),
  ADD KEY `idx_section` (`section`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_deleted_by` (`deleted_by`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doc_id` (`doc_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restore_logs`
--
ALTER TABLE `restore_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_restore_type` (`restore_type`),
  ADD KEY `idx_restored_by` (`restored_by`),
  ADD KEY `idx_restored_at` (`restored_at`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permission` (`user_id`,`permission_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `backup_logs`
--
ALTER TABLE `backup_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `deleted_documents`
--
ALTER TABLE `deleted_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `restore_logs`
--
ALTER TABLE `restore_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

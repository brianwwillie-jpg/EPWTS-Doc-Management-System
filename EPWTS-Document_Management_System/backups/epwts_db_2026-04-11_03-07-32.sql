-- EPWTS Database Backup
-- Generated: 2026-04-11 03:07:32
-- Backed up by: admin
-- Database: epwts_db
-- ============================================

DROP TABLE IF EXISTS `backup_logs`;
CREATE TABLE `backup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_type` enum('database','files','document_deletion') NOT NULL,
  `created_by` varchar(50) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `file_count` int(11) DEFAULT 0,
  `status` enum('success','failed','partial') DEFAULT 'success',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_backup_type` (`backup_type`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `deleted_documents`;
CREATE TABLE `deleted_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_doc_id` (`doc_id`),
  KEY `idx_section` (`section`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_deleted_by` (`deleted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_id` varchar(20) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `doc_type` enum('Contracts','Drawings','Award Letters','Profiles','Others') DEFAULT NULL,
  `uploaded_by` varchar(50) DEFAULT NULL,
  `upload_date` datetime DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doc_id` (`doc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `documents` (`id`, `doc_id`, `file_name`, `file_path`, `section`, `doc_type`, `uploaded_by`, `upload_date`, `expiry_date`, `description`) VALUES ('1', 'AB260504001', 'page 4.png', '../uploads/page 4.png', 'AB', '', '', '2026-04-05 10:13:07', '', '');
INSERT INTO `documents` (`id`, `doc_id`, `file_name`, `file_path`, `section`, `doc_type`, `uploaded_by`, `upload_date`, `expiry_date`, `description`) VALUES ('3', 'CW260504002', 'page 8.png', '../uploads/page 8.png', 'CW', '', '', '2026-04-05 10:23:08', '', '');
INSERT INTO `documents` (`id`, `doc_id`, `file_name`, `file_path`, `section`, `doc_type`, `uploaded_by`, `upload_date`, `expiry_date`, `description`) VALUES ('4', 'AB260504004', ' ABbbcc7732', '../uploads/Infectious Disease Test 1.png', 'Arch_Building', '', '', '2026-04-05 11:57:14', '', '');
INSERT INTO `documents` (`id`, `doc_id`, `file_name`, `file_path`, `section`, `doc_type`, `uploaded_by`, `upload_date`, `expiry_date`, `description`) VALUES ('5', 'CW260604001', 'Civil Works First File Upload', '../uploads/Serology 1.png', 'Civil_Works', '', '', '2026-04-06 23:05:04', '', '');

DROP TABLE IF EXISTS `requests`;
CREATE TABLE `requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_id` varchar(20) DEFAULT NULL,
  `requested_by` varchar(50) DEFAULT NULL,
  `request_type` enum('View','Delete') DEFAULT NULL,
  `status` enum('Pending','Approved','Declined') DEFAULT 'Pending',
  `reason` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `requests` (`id`, `doc_id`, `requested_by`, `request_type`, `status`, `reason`) VALUES ('1', '4', 'ab', 'View', '', 'I wanted to view this document for the archietechturel project');
INSERT INTO `requests` (`id`, `doc_id`, `requested_by`, `request_type`, `status`, `reason`) VALUES ('2', '4', 'ab', '', 'Approved', 'There is a mistake so I need to edit this document');
INSERT INTO `requests` (`id`, `doc_id`, `requested_by`, `request_type`, `status`, `reason`) VALUES ('3', '4', 'ab', 'View', '', 'i need to view');
INSERT INTO `requests` (`id`, `doc_id`, `requested_by`, `request_type`, `status`, `reason`) VALUES ('4', '4', 'ab', 'View', '', 'I need to view this');
INSERT INTO `requests` (`id`, `doc_id`, `requested_by`, `request_type`, `status`, `reason`) VALUES ('5', '4', 'ab', '', 'Approved', 'I need to edit this');
INSERT INTO `requests` (`id`, `doc_id`, `requested_by`, `request_type`, `status`, `reason`) VALUES ('6', '4', 'ab', 'Delete', 'Declined', 'Duplicate file');
INSERT INTO `requests` (`id`, `doc_id`, `requested_by`, `request_type`, `status`, `reason`) VALUES ('7', '4', 'ab', '', 'Declined', 'Let me edit this');
INSERT INTO `requests` (`id`, `doc_id`, `requested_by`, `request_type`, `status`, `reason`) VALUES ('8', '4', 'ab', '', 'Declined', 'Let me edit this');
INSERT INTO `requests` (`id`, `doc_id`, `requested_by`, `request_type`, `status`, `reason`) VALUES ('9', '4', 'ab', '', 'Declined', 'Let me edit this');
INSERT INTO `requests` (`id`, `doc_id`, `requested_by`, `request_type`, `status`, `reason`) VALUES ('10', '5', 'cw', 'View', 'Approved', 'Test View');

DROP TABLE IF EXISTS `restore_logs`;
CREATE TABLE `restore_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restore_type` enum('database','files','document') NOT NULL,
  `restored_from` varchar(255) DEFAULT NULL,
  `restored_by` varchar(50) NOT NULL,
  `status` enum('success','failed','partial') DEFAULT 'success',
  `notes` text DEFAULT NULL,
  `restored_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_restore_type` (`restore_type`),
  KEY `idx_restored_by` (`restored_by`),
  KEY `idx_restored_at` (`restored_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `user_permissions`;
CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `permission_type` enum('view','edit','delete','approve') NOT NULL,
  `granted_by` varchar(50) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission` (`user_id`,`permission_type`),
  CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Arch_Building','Civil_Works','Director','Admin','Energy','Super_Admin','Doc_Controller') NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`) VALUES ('1', 'admin', '$2y$10$nplubDaL7Hgyc6gLY2fMJue83LE6jW1fzhlNoiIlqVAnK7IC2BYqe', 'Super_Admin', 'Brian Willie');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`) VALUES ('2', 'doc', '$2y$10$ktkN8KPNyCx2S3U/ZA/rZuURfK7aJ86sxRFRrHLRTUzEj9sFl5pbq', 'Doc_Controller', 'Document Controller - K James');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`) VALUES ('3', 'ckb', '$2y$10$hz.jISfdii2jjeHI8BWslOP5rdkA3Ev5oQ08mqYYyflXj4Z57GEp6', 'Director', 'Charles K. Bannah');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`) VALUES ('6', 'admin123', '$2y$10$kltJdIn5IX54rEYlhUxxrui20iY/2cltplPKQTUs.K8/bmTVYmMc.', 'Admin', 'Kinelda Admin');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`) VALUES ('7', 'cw', '$2y$10$0tNhBy.AIDaO0l8399VS3esspH1Y1mXo18ntGq8IlwyS9RLqjk046', 'Civil_Works', 'Civil Works');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`) VALUES ('8', 'ab', '$2y$10$s4kSkvbZaONK6/zs2pxDkOlUpaak/ta9vJQ.fQMZqH8ksrmuoR9eK', 'Arch_Building', 'Archie & Building');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`) VALUES ('9', 'energy', '$2y$10$xjra5bpyGfv22qpU7Ha16ua5MRZHFllKMnpKpFca9jcRKvcoJWbwG', 'Energy', 'Energy Test');
INSERT INTO `users` (`id`, `username`, `password`, `role`, `name`) VALUES ('10', 'test', '$2y$10$Puq/e5JJ38XeWb.o/7BM6uuIrbljq1KZCemfQ.xbwChOSoaDobcxq', 'Super_Admin', 'Test');


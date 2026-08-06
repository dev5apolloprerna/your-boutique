-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 09, 2026 at 11:34 AM
-- Server version: 8.4.7
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `payal`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `gst_rate` decimal(18,2) NOT NULL DEFAULT '0.00',
  `is_split` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `sort_order`, `is_active`, `created_at`, `gst_rate`, `is_split`) VALUES
(7, 'SHIRTS', 1, 1, '2026-07-06 09:40:20', 5.00, 0),
(8, 'kurti', 2, 1, '2026-07-06 10:41:27', 5.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `credit_notes`
--

DROP TABLE IF EXISTS `credit_notes`;
CREATE TABLE IF NOT EXISTS `credit_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `credit_note_no` varchar(50) NOT NULL,
  `credit_date` date NOT NULL,
  `invoice_id` int NOT NULL,
  `party_id` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cgst_amount` decimal(10,2) DEFAULT '0.00',
  `sgst_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `refund_mode` enum('Cash','Card','UPI','Exchange') DEFAULT 'Cash',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_note_no` (`credit_note_no`),
  KEY `party_id` (`party_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_credit_no` (`credit_note_no`),
  KEY `idx_date` (`credit_date`),
  KEY `idx_invoice` (`invoice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credit_note_items`
--

DROP TABLE IF EXISTS `credit_note_items`;
CREATE TABLE IF NOT EXISTS `credit_note_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `credit_note_id` int NOT NULL,
  `invoice_item_id` int NOT NULL,
  `product_id` int NOT NULL,
  `size_id` int NOT NULL,
  `quantity` int NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `gst_rate` decimal(5,2) NOT NULL,
  `base_amount` decimal(10,2) NOT NULL,
  `cgst_amount` decimal(10,2) NOT NULL,
  `sgst_amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_item_id` (`invoice_item_id`),
  KEY `product_id` (`product_id`),
  KEY `size_id` (`size_id`),
  KEY `idx_credit_note` (`credit_note_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `party_id` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `cgst_amount` decimal(10,2) DEFAULT '0.00',
  `sgst_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_mode` enum('Cash','Card','UPI') DEFAULT 'Cash',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `created_by` (`created_by`),
  KEY `idx_invoice_no` (`invoice_no`),
  KEY `idx_date` (`invoice_date`),
  KEY `idx_party` (`party_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `invoice_date`, `party_id`, `subtotal`, `discount_amount`, `cgst_amount`, `sgst_amount`, `total_amount`, `payment_mode`, `notes`, `created_by`, `created_at`) VALUES
(24, 'INV000001', '2026-07-06', 16, 1500.00, 0.00, 35.71, 35.71, 1500.00, 'Cash', '', 1, '2026-07-06 10:44:59');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int NOT NULL,
  `product_id` int NOT NULL,
  `size_id` int NOT NULL,
  `quantity` int NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `gst_rate` decimal(5,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `base_amount` decimal(10,2) NOT NULL COMMENT 'Amount before GST',
  `cgst_amount` decimal(10,2) NOT NULL,
  `sgst_amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `size_id` (`size_id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `size_id`, `quantity`, `mrp`, `gst_rate`, `discount_amount`, `base_amount`, `cgst_amount`, `sgst_amount`, `total_amount`) VALUES
(37, 24, 15, 2, 1, 1500.00, 5.00, 0.00, 1428.57, 35.71, 35.71, 1500.00);

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

DROP TABLE IF EXISTS `parties`;
CREATE TABLE IF NOT EXISTS `parties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mobile` varchar(15) NOT NULL,
  `party_name` varchar(100) NOT NULL,
  `address` text COMMENT 'Legacy field - not used',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`),
  KEY `idx_mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`id`, `mobile`, `party_name`, `address`, `notes`, `created_at`, `updated_at`) VALUES
(16, '9426055450', 'xyz', '', '', '2026-07-06 10:44:59', '2026-07-06 10:44:59');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `category_id` int DEFAULT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `gst_rate` decimal(5,2) NOT NULL COMMENT 'GST rate: 5 or 18',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `product_number` int DEFAULT NULL,
  `prefix_code` varchar(10) DEFAULT '5001',
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  UNIQUE KEY `product_number` (`product_number`),
  KEY `idx_product_code` (`product_code`),
  KEY `idx_active` (`is_active`),
  KEY `idx_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `product_name`, `category_id`, `mrp`, `gst_rate`, `is_active`, `created_at`, `updated_at`, `product_number`, `prefix_code`) VALUES
(14, '3001003001', 'BAGARU', 7, 1590.00, 5.00, 1, '2026-07-06 09:42:15', '2026-07-06 09:42:15', 2147483647, NULL),
(15, '03001', 'handloom', 8, 1500.00, 5.00, 1, '2026-07-06 10:43:35', '2026-07-06 10:43:35', 3001, NULL),
(16, '00005', 'Cotton Kurti', 8, 4500.00, 5.00, 1, '2026-07-09 10:54:48', '2026-07-09 11:07:16', 5, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_stock`
--

DROP TABLE IF EXISTS `product_stock`;
CREATE TABLE IF NOT EXISTS `product_stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `size_id` int NOT NULL,
  `quantity` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_size` (`product_id`,`size_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_size` (`size_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_stock`
--

INSERT INTO `product_stock` (`id`, `product_id`, `size_id`, `quantity`, `created_at`, `updated_at`) VALUES
(1, 14, 1, 0, '2026-07-06 09:42:15', '2026-07-06 09:42:15'),
(2, 14, 2, 1, '2026-07-06 09:42:15', '2026-07-06 09:42:15'),
(3, 14, 3, 1, '2026-07-06 09:42:15', '2026-07-06 09:42:15'),
(4, 14, 4, 1, '2026-07-06 09:42:15', '2026-07-06 09:42:15'),
(5, 14, 5, 1, '2026-07-06 09:42:15', '2026-07-06 09:42:15'),
(6, 14, 6, 1, '2026-07-06 09:42:15', '2026-07-06 09:42:15'),
(7, 14, 7, 0, '2026-07-06 09:42:15', '2026-07-06 09:42:15'),
(8, 15, 1, 0, '2026-07-06 10:43:35', '2026-07-06 10:43:35'),
(9, 15, 2, 0, '2026-07-06 10:43:35', '2026-07-06 10:44:59'),
(10, 15, 3, 1, '2026-07-06 10:43:35', '2026-07-06 10:43:35'),
(11, 15, 4, 1, '2026-07-06 10:43:35', '2026-07-06 10:43:35'),
(12, 15, 5, 1, '2026-07-06 10:43:35', '2026-07-06 10:43:35'),
(13, 15, 6, 1, '2026-07-06 10:43:35', '2026-07-06 10:43:35'),
(14, 15, 7, 0, '2026-07-06 10:43:35', '2026-07-06 10:43:35'),
(15, 16, 1, 1, '2026-07-09 10:54:48', '2026-07-09 11:29:37'),
(16, 16, 2, 1, '2026-07-09 10:54:48', '2026-07-09 10:54:48'),
(17, 16, 3, 1, '2026-07-09 10:54:48', '2026-07-09 10:54:48'),
(18, 16, 4, 1, '2026-07-09 10:54:48', '2026-07-09 10:54:48'),
(19, 16, 5, 1, '2026-07-09 10:54:48', '2026-07-09 10:54:48'),
(20, 16, 6, 1, '2026-07-09 10:54:48', '2026-07-09 10:54:48'),
(21, 16, 7, 1, '2026-07-09 10:54:48', '2026-07-09 10:54:48');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'company_name', 'Payal Arban Stichis', '2026-05-13 04:49:13'),
(2, 'company_mobile', '', '2026-05-13 04:49:13'),
(3, 'company_address', '', '2026-05-13 04:49:13'),
(4, 'company_gstin', '', '2026-05-13 04:49:13'),
(5, 'invoice_prefix', 'INV', '2026-05-13 04:49:13'),
(6, 'credit_note_prefix', 'CN', '2026-05-13 04:49:13');

-- --------------------------------------------------------

--
-- Table structure for table `sizes`
--

DROP TABLE IF EXISTS `sizes`;
CREATE TABLE IF NOT EXISTS `sizes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `size_name` varchar(20) NOT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `size_code` char(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `size_name` (`size_name`),
  UNIQUE KEY `size_code` (`size_code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sizes`
--

INSERT INTO `sizes` (`id`, `size_name`, `sort_order`, `is_active`, `created_at`, `size_code`) VALUES
(1, 'XS', 1, 1, '2026-05-13 04:48:54', 'A'),
(2, 'S', 2, 1, '2026-05-13 04:48:54', 'B'),
(3, 'M', 3, 1, '2026-05-13 04:48:54', 'C'),
(4, 'L', 4, 1, '2026-05-13 04:48:54', 'D'),
(5, 'XL', 5, 1, '2026-05-13 04:48:54', 'E'),
(6, 'XXL', 6, 1, '2026-05-13 04:48:54', 'F'),
(7, 'XXXL', 7, 1, '2026-05-13 04:48:54', 'G');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE IF NOT EXISTS `stock_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `size_id` int NOT NULL,
  `quantity` int NOT NULL,
  `transaction_type` enum('IN','OUT','RETURN') NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'PURCHASE, SALE, RETURN, ADJUSTMENT',
  `reference_id` int DEFAULT NULL COMMENT 'Invoice ID or Credit Note ID',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `size_id` (`size_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_product` (`product_id`),
  KEY `idx_type` (`transaction_type`),
  KEY `idx_date` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_transactions`
--

INSERT INTO `stock_transactions` (`id`, `product_id`, `size_id`, `quantity`, `transaction_type`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(1, 14, 2, 1, 'IN', 'OPENING', 14, 'Opening Stock', 1, '2026-07-06 09:42:15'),
(2, 14, 3, 1, 'IN', 'OPENING', 14, 'Opening Stock', 1, '2026-07-06 09:42:15'),
(3, 14, 4, 1, 'IN', 'OPENING', 14, 'Opening Stock', 1, '2026-07-06 09:42:15'),
(4, 14, 5, 1, 'IN', 'OPENING', 14, 'Opening Stock', 1, '2026-07-06 09:42:15'),
(5, 14, 6, 1, 'IN', 'OPENING', 14, 'Opening Stock', 1, '2026-07-06 09:42:15'),
(6, 15, 2, 1, 'IN', 'OPENING', 15, 'Opening Stock', 1, '2026-07-06 10:43:35'),
(7, 15, 3, 1, 'IN', 'OPENING', 15, 'Opening Stock', 1, '2026-07-06 10:43:35'),
(8, 15, 4, 1, 'IN', 'OPENING', 15, 'Opening Stock', 1, '2026-07-06 10:43:35'),
(9, 15, 5, 1, 'IN', 'OPENING', 15, 'Opening Stock', 1, '2026-07-06 10:43:35'),
(10, 15, 6, 1, 'IN', 'OPENING', 15, 'Opening Stock', 1, '2026-07-06 10:43:35'),
(11, 15, 2, 1, 'OUT', 'SALE', 24, 'Invoice: INV000001', 1, '2026-07-06 10:44:59'),
(12, 16, 1, 1, 'IN', 'OPENING', 16, 'Opening Stock', 1, '2026-07-09 10:54:48'),
(13, 16, 2, 1, 'IN', 'OPENING', 16, 'Opening Stock', 1, '2026-07-09 10:54:48'),
(14, 16, 3, 1, 'IN', 'OPENING', 16, 'Opening Stock', 1, '2026-07-09 10:54:48'),
(15, 16, 4, 1, 'IN', 'OPENING', 16, 'Opening Stock', 1, '2026-07-09 10:54:48'),
(16, 16, 5, 1, 'IN', 'OPENING', 16, 'Opening Stock', 1, '2026-07-09 10:54:48'),
(17, 16, 6, 1, 'IN', 'OPENING', 16, 'Opening Stock', 1, '2026-07-09 10:54:48'),
(18, 16, 7, 1, 'IN', 'OPENING', 16, 'Opening Stock', 1, '2026-07-09 10:54:48'),
(19, 16, 1, 1, 'OUT', 'SALE', 25, 'Invoice: INV000002', 1, '2026-07-09 11:11:06'),
(20, 16, 1, 1, 'IN', 'RETURN', 25, 'Invoice Deleted: INV000002', 1, '2026-07-09 11:29:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `mobile`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$7ktXvEfTgrF3vmbAKGvqwuVmxSJUi3t/cW72b5VcwzgIoIZWiBC2a', 'Administrator', '9999999999', '2026-05-13 04:48:43', '2026-05-13 05:33:37');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `credit_notes`
--
ALTER TABLE `credit_notes`
  ADD CONSTRAINT `credit_notes_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `credit_notes_ibfk_2` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`),
  ADD CONSTRAINT `credit_notes_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `credit_note_items`
--
ALTER TABLE `credit_note_items`
  ADD CONSTRAINT `credit_note_items_ibfk_1` FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_note_items_ibfk_2` FOREIGN KEY (`invoice_item_id`) REFERENCES `invoice_items` (`id`),
  ADD CONSTRAINT `credit_note_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `credit_note_items_ibfk_4` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `invoice_items_ibfk_3` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_stock`
--
ALTER TABLE `product_stock`
  ADD CONSTRAINT `product_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_stock_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`),
  ADD CONSTRAINT `stock_transactions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

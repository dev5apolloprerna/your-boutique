-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 06, 2026 at 02:19 PM
-- Server version: 5.7.23-23
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `getdemo_payal_arban_stichies`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Dress1', 1, 1, '2026-05-13 10:02:09'),
(2, 'Kurti', 2, 1, '2026-05-13 10:02:09'),
(3, 'Dupatta', 3, 1, '2026-05-13 10:02:09'),
(4, 'Coatset', 4, 1, '2026-05-13 10:02:09'),
(5, 'Jackets1', 5, 1, '2026-05-13 10:02:09');

-- --------------------------------------------------------

--
-- Table structure for table `credit_notes`
--

CREATE TABLE `credit_notes` (
  `id` int(11) NOT NULL,
  `credit_note_no` varchar(50) NOT NULL,
  `credit_date` date NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `party_id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cgst_amount` decimal(10,2) DEFAULT '0.00',
  `sgst_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `refund_mode` enum('Cash','Card','UPI','Exchange') DEFAULT 'Cash',
  `notes` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `credit_note_items`
--

CREATE TABLE `credit_note_items` (
  `id` int(11) NOT NULL,
  `credit_note_id` int(11) NOT NULL,
  `invoice_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `gst_rate` decimal(5,2) NOT NULL,
  `base_amount` decimal(10,2) NOT NULL,
  `cgst_amount` decimal(10,2) NOT NULL,
  `sgst_amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `party_id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `cgst_amount` decimal(10,2) DEFAULT '0.00',
  `sgst_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_mode` enum('Cash','Card','UPI') DEFAULT 'Cash',
  `notes` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `invoice_date`, `party_id`, `subtotal`, `discount_amount`, `cgst_amount`, `sgst_amount`, `total_amount`, `payment_mode`, `notes`, `created_by`, `created_at`) VALUES
(19, 'INV000001', '2026-07-04', 9, 2958.00, 0.00, 70.43, 70.43, 2958.00, 'Cash', 'test', 1, '2026-07-04 17:50:49'),
(20, 'INV000002', '2026-07-04', 9, 1470.00, 0.00, 35.00, 35.00, 1470.00, 'Cash', 'test', 1, '2026-07-04 17:55:09'),
(21, 'INV000003', '2026-07-06', 14, 3400.00, 0.00, 80.95, 80.95, 3400.00, 'Cash', '', 1, '2026-07-06 07:02:50'),
(22, 'INV000004', '2026-07-06', 10, 6050.00, 0.00, 144.05, 144.05, 6050.00, 'Cash', '', 1, '2026-07-06 07:06:23'),
(23, 'INV000005', '2026-07-06', 15, 5150.00, 0.00, 122.62, 122.62, 5150.00, 'Cash', '', 1, '2026-07-06 07:10:34');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `gst_rate` decimal(5,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `base_amount` decimal(10,2) NOT NULL COMMENT 'Amount before GST',
  `cgst_amount` decimal(10,2) NOT NULL,
  `sgst_amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `size_id`, `quantity`, `mrp`, `gst_rate`, `discount_amount`, `base_amount`, `cgst_amount`, `sgst_amount`, `total_amount`) VALUES
(26, 19, 8, 3, 1, 1479.00, 5.00, NULL, 1408.57, 35.21, 35.21, 1479.00),
(27, 19, 8, 5, 1, 1479.00, 5.00, NULL, 1408.57, 35.21, 35.21, 1479.00),
(28, 20, 9, 5, 1, 1470.00, 5.00, NULL, 1400.00, 35.00, 35.00, 1470.00),
(29, 21, 10, 3, 1, 1550.00, 5.00, NULL, 1476.19, 36.90, 36.90, 1550.00),
(30, 21, 12, 4, 1, 1850.00, 5.00, NULL, 1761.90, 44.05, 44.05, 1850.00),
(31, 22, 11, 5, 1, 2450.00, 5.00, NULL, 2333.33, 58.33, 58.33, 2450.00),
(32, 22, 12, 7, 1, 1850.00, 5.00, NULL, 1761.90, 44.05, 44.05, 1850.00),
(33, 22, 13, 4, 1, 1750.00, 5.00, NULL, 1666.67, 41.67, 41.67, 1750.00),
(34, 23, 10, 5, 1, 1550.00, 5.00, NULL, 1476.19, 36.90, 36.90, 1550.00),
(35, 23, 12, 6, 1, 1850.00, 5.00, NULL, 1761.90, 44.05, 44.05, 1850.00),
(36, 23, 13, 6, 1, 1750.00, 5.00, NULL, 1666.67, 41.67, 41.67, 1750.00);

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

CREATE TABLE `parties` (
  `id` int(11) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `party_name` varchar(100) NOT NULL,
  `address` text COMMENT 'Legacy field - not used',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`id`, `mobile`, `party_name`, `address`, `notes`, `created_at`, `updated_at`) VALUES
(3, '9409202530', 'Kashyap', NULL, 'test', '2026-05-13 10:27:27', '2026-05-13 10:27:27'),
(9, '9824773136', 'Krunal Shah', NULL, 'test', '2026-05-13 11:55:44', '2026-05-13 11:55:44'),
(10, '9427534693', 'Krupali Shah', NULL, '', '2026-07-06 06:59:01', '2026-07-06 06:59:01'),
(11, '7878533666', 'Sahil', NULL, '', '2026-07-06 06:59:14', '2026-07-06 06:59:14'),
(12, '9824613136', 'Apollo Office', NULL, '', '2026-07-06 06:59:30', '2026-07-06 06:59:30'),
(13, '9737613136', 'Apollo office2', NULL, '', '2026-07-06 06:59:41', '2026-07-06 06:59:41'),
(14, '9825786923', 'Samir', NULL, '', '2026-07-06 06:59:55', '2026-07-06 06:59:55'),
(15, '9825848657', 'Gitaben Shah', NULL, '', '2026-07-06 07:10:34', '2026-07-06 07:10:34');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `gst_rate` decimal(5,2) NOT NULL COMMENT 'GST rate: 5 or 18',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `product_number` int(11) DEFAULT NULL,
  `prefix_code` varchar(10) DEFAULT '5001'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `product_name`, `category_id`, `mrp`, `gst_rate`, `is_active`, `created_at`, `updated_at`, `product_number`, `prefix_code`) VALUES
(5, '00002', 'ABC', 1, 1450.00, 5.00, 1, '2026-06-26 11:10:21', '2026-06-30 13:32:12', 2, '5001'),
(6, '00003', 'gsfsjeh', 4, 1450.00, 5.00, 1, '2026-06-26 11:46:01', '2026-06-30 13:32:17', 3, '5001'),
(7, '00004', 'test', 2, 1000.00, 5.00, 1, '2026-06-30 13:17:39', '2026-06-30 13:32:22', 4, '5001'),
(8, '00005', 'Kurti005', 2, 1479.00, 5.00, 1, '2026-07-04 17:48:16', '2026-07-04 17:48:16', 5, NULL),
(9, '00006', 'DesiKJ', 2, 1470.00, 5.00, 1, '2026-07-04 17:54:30', '2026-07-04 17:54:30', 6, NULL),
(10, '00007', 'Cotton handloom kurti', 2, 1550.00, 5.00, 1, '2026-07-06 06:56:57', '2026-07-06 06:56:57', 7, NULL),
(11, '00008', 'Cotton mal kurta plazo pair', 1, 2450.00, 5.00, 1, '2026-07-06 06:57:39', '2026-07-06 06:57:39', 8, NULL),
(12, '00009', 'Cotton midi dress', 1, 1850.00, 5.00, 1, '2026-07-06 06:58:09', '2026-07-06 06:58:09', 9, NULL),
(13, '00010', 'Cotton ajarakh kurta', 2, 1750.00, 5.00, 1, '2026-07-06 06:58:39', '2026-07-06 06:58:39', 10, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_stock`
--

CREATE TABLE `product_stock` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `product_stock`
--

INSERT INTO `product_stock` (`id`, `product_id`, `size_id`, `quantity`, `created_at`, `updated_at`) VALUES
(29, 5, 1, 0, '2026-06-26 11:10:21', '2026-06-26 11:10:21'),
(30, 5, 2, 0, '2026-06-26 11:10:21', '2026-06-26 11:10:21'),
(31, 5, 3, 0, '2026-06-26 11:10:21', '2026-06-26 11:10:21'),
(32, 5, 4, 0, '2026-06-26 11:10:21', '2026-06-26 11:10:21'),
(33, 5, 5, 0, '2026-06-26 11:10:21', '2026-06-26 11:10:21'),
(34, 5, 6, 0, '2026-06-26 11:10:21', '2026-06-26 11:10:21'),
(35, 5, 7, 0, '2026-06-26 11:10:21', '2026-06-26 11:10:21'),
(36, 6, 1, 0, '2026-06-26 11:46:01', '2026-06-26 11:46:01'),
(37, 6, 2, 5, '2026-06-26 11:46:01', '2026-06-26 11:46:01'),
(38, 6, 3, 5, '2026-06-26 11:46:01', '2026-06-26 11:46:01'),
(39, 6, 4, 5, '2026-06-26 11:46:01', '2026-06-26 11:46:01'),
(40, 6, 5, 5, '2026-06-26 11:46:01', '2026-06-26 11:46:01'),
(41, 6, 6, 5, '2026-06-26 11:46:01', '2026-06-26 11:46:01'),
(42, 6, 7, 0, '2026-06-26 11:46:01', '2026-06-26 11:46:01'),
(43, 7, 1, 2, '2026-06-30 13:17:39', '2026-06-30 13:17:39'),
(44, 7, 2, 2, '2026-06-30 13:17:39', '2026-06-30 13:17:39'),
(45, 7, 3, 2, '2026-06-30 13:17:39', '2026-06-30 13:17:39'),
(46, 7, 4, 2, '2026-06-30 13:17:39', '2026-06-30 13:17:39'),
(47, 7, 5, 2, '2026-06-30 13:17:39', '2026-06-30 13:17:39'),
(48, 7, 6, 2, '2026-06-30 13:17:39', '2026-06-30 13:17:39'),
(49, 7, 7, 2, '2026-06-30 13:17:39', '2026-06-30 13:17:39'),
(50, 8, 1, 0, '2026-07-04 17:48:16', '2026-07-04 17:48:16'),
(51, 8, 2, 0, '2026-07-04 17:48:16', '2026-07-04 17:48:16'),
(52, 8, 3, 49, '2026-07-04 17:48:16', '2026-07-04 17:50:49'),
(53, 8, 4, 5, '2026-07-04 17:48:17', '2026-07-04 17:48:17'),
(54, 8, 5, 4, '2026-07-04 17:48:17', '2026-07-04 17:50:49'),
(55, 8, 6, 0, '2026-07-04 17:48:17', '2026-07-04 17:48:17'),
(56, 8, 7, 0, '2026-07-04 17:48:17', '2026-07-04 17:48:17'),
(57, 9, 1, 0, '2026-07-04 17:54:30', '2026-07-04 17:54:30'),
(58, 9, 2, 0, '2026-07-04 17:54:30', '2026-07-04 17:54:30'),
(59, 9, 3, 0, '2026-07-04 17:54:30', '2026-07-04 17:54:30'),
(60, 9, 4, 0, '2026-07-04 17:54:30', '2026-07-04 17:54:30'),
(61, 9, 5, 0, '2026-07-04 17:54:30', '2026-07-04 17:55:09'),
(62, 9, 6, 0, '2026-07-04 17:54:30', '2026-07-04 17:54:30'),
(63, 9, 7, 0, '2026-07-04 17:54:30', '2026-07-04 17:54:30'),
(64, 10, 1, 0, '2026-07-06 06:56:57', '2026-07-06 06:56:57'),
(65, 10, 2, 0, '2026-07-06 06:56:57', '2026-07-06 06:56:57'),
(66, 10, 3, 9, '2026-07-06 06:56:57', '2026-07-06 07:02:50'),
(67, 10, 4, 10, '2026-07-06 06:56:57', '2026-07-06 06:56:57'),
(68, 10, 5, 9, '2026-07-06 06:56:57', '2026-07-06 07:10:34'),
(69, 10, 6, 0, '2026-07-06 06:56:57', '2026-07-06 06:56:57'),
(70, 10, 7, 0, '2026-07-06 06:56:57', '2026-07-06 06:56:57'),
(71, 11, 1, 0, '2026-07-06 06:57:39', '2026-07-06 06:57:39'),
(72, 11, 2, 2, '2026-07-06 06:57:39', '2026-07-06 06:57:39'),
(73, 11, 3, 2, '2026-07-06 06:57:39', '2026-07-06 06:57:39'),
(74, 11, 4, 2, '2026-07-06 06:57:39', '2026-07-06 06:57:39'),
(75, 11, 5, 1, '2026-07-06 06:57:39', '2026-07-06 07:06:23'),
(76, 11, 6, 0, '2026-07-06 06:57:39', '2026-07-06 06:57:39'),
(77, 11, 7, 0, '2026-07-06 06:57:39', '2026-07-06 06:57:39'),
(78, 12, 1, 0, '2026-07-06 06:58:09', '2026-07-06 06:58:09'),
(79, 12, 2, 3, '2026-07-06 06:58:09', '2026-07-06 06:58:09'),
(80, 12, 3, 3, '2026-07-06 06:58:09', '2026-07-06 06:58:09'),
(81, 12, 4, 2, '2026-07-06 06:58:09', '2026-07-06 07:02:50'),
(82, 12, 5, 3, '2026-07-06 06:58:09', '2026-07-06 06:58:09'),
(83, 12, 6, 2, '2026-07-06 06:58:09', '2026-07-06 07:10:34'),
(84, 12, 7, 2, '2026-07-06 06:58:09', '2026-07-06 07:06:23'),
(85, 13, 1, 0, '2026-07-06 06:58:39', '2026-07-06 06:58:39'),
(86, 13, 2, 0, '2026-07-06 06:58:39', '2026-07-06 06:58:39'),
(87, 13, 3, 0, '2026-07-06 06:58:39', '2026-07-06 06:58:39'),
(88, 13, 4, 5, '2026-07-06 06:58:39', '2026-07-06 07:06:23'),
(89, 13, 5, 6, '2026-07-06 06:58:39', '2026-07-06 06:58:39'),
(90, 13, 6, 5, '2026-07-06 06:58:39', '2026-07-06 07:10:34'),
(91, 13, 7, 0, '2026-07-06 06:58:39', '2026-07-06 06:58:39');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE `sizes` (
  `id` int(11) NOT NULL,
  `size_name` varchar(20) NOT NULL,
  `sort_order` int(11) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `size_code` char(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE `stock_transactions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `transaction_type` enum('IN','OUT','RETURN') NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'PURCHASE, SALE, RETURN, ADJUSTMENT',
  `reference_id` int(11) DEFAULT NULL COMMENT 'Invoice ID or Credit Note ID',
  `notes` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `stock_transactions`
--

INSERT INTO `stock_transactions` (`id`, `product_id`, `size_id`, `quantity`, `transaction_type`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(51, 6, 2, 5, 'IN', 'OPENING', 6, 'Opening Stock', 1, '2026-06-26 11:46:01'),
(52, 6, 3, 5, 'IN', 'OPENING', 6, 'Opening Stock', 1, '2026-06-26 11:46:01'),
(53, 6, 4, 5, 'IN', 'OPENING', 6, 'Opening Stock', 1, '2026-06-26 11:46:01'),
(54, 6, 5, 5, 'IN', 'OPENING', 6, 'Opening Stock', 1, '2026-06-26 11:46:01'),
(55, 6, 6, 5, 'IN', 'OPENING', 6, 'Opening Stock', 1, '2026-06-26 11:46:01'),
(56, 7, 1, 2, 'IN', 'OPENING', 7, 'Opening Stock', 1, '2026-06-30 13:17:39'),
(57, 7, 2, 2, 'IN', 'OPENING', 7, 'Opening Stock', 1, '2026-06-30 13:17:39'),
(58, 7, 3, 2, 'IN', 'OPENING', 7, 'Opening Stock', 1, '2026-06-30 13:17:39'),
(59, 7, 4, 2, 'IN', 'OPENING', 7, 'Opening Stock', 1, '2026-06-30 13:17:39'),
(60, 7, 5, 2, 'IN', 'OPENING', 7, 'Opening Stock', 1, '2026-06-30 13:17:39'),
(61, 7, 6, 2, 'IN', 'OPENING', 7, 'Opening Stock', 1, '2026-06-30 13:17:39'),
(62, 7, 7, 2, 'IN', 'OPENING', 7, 'Opening Stock', 1, '2026-06-30 13:17:39'),
(63, 8, 3, 50, 'IN', 'OPENING', 8, 'Opening Stock', 1, '2026-07-04 17:48:16'),
(64, 8, 4, 5, 'IN', 'OPENING', 8, 'Opening Stock', 1, '2026-07-04 17:48:17'),
(65, 8, 5, 5, 'IN', 'OPENING', 8, 'Opening Stock', 1, '2026-07-04 17:48:17'),
(66, 8, 3, 1, 'OUT', 'SALE', 19, 'Invoice: INV000001', 1, '2026-07-04 17:50:49'),
(67, 8, 5, 1, 'OUT', 'SALE', 19, 'Invoice: INV000001', 1, '2026-07-04 17:50:49'),
(68, 9, 5, 1, 'IN', 'OPENING', 9, 'Opening Stock', 1, '2026-07-04 17:54:30'),
(69, 9, 5, 1, 'OUT', 'SALE', 20, 'Invoice: INV000002', 1, '2026-07-04 17:55:09'),
(70, 10, 3, 10, 'IN', 'OPENING', 10, 'Opening Stock', 1, '2026-07-06 06:56:57'),
(71, 10, 4, 10, 'IN', 'OPENING', 10, 'Opening Stock', 1, '2026-07-06 06:56:57'),
(72, 10, 5, 10, 'IN', 'OPENING', 10, 'Opening Stock', 1, '2026-07-06 06:56:57'),
(73, 11, 2, 2, 'IN', 'OPENING', 11, 'Opening Stock', 1, '2026-07-06 06:57:39'),
(74, 11, 3, 2, 'IN', 'OPENING', 11, 'Opening Stock', 1, '2026-07-06 06:57:39'),
(75, 11, 4, 2, 'IN', 'OPENING', 11, 'Opening Stock', 1, '2026-07-06 06:57:39'),
(76, 11, 5, 2, 'IN', 'OPENING', 11, 'Opening Stock', 1, '2026-07-06 06:57:39'),
(77, 12, 2, 3, 'IN', 'OPENING', 12, 'Opening Stock', 1, '2026-07-06 06:58:09'),
(78, 12, 3, 3, 'IN', 'OPENING', 12, 'Opening Stock', 1, '2026-07-06 06:58:09'),
(79, 12, 4, 3, 'IN', 'OPENING', 12, 'Opening Stock', 1, '2026-07-06 06:58:09'),
(80, 12, 5, 3, 'IN', 'OPENING', 12, 'Opening Stock', 1, '2026-07-06 06:58:09'),
(81, 12, 6, 3, 'IN', 'OPENING', 12, 'Opening Stock', 1, '2026-07-06 06:58:09'),
(82, 12, 7, 3, 'IN', 'OPENING', 12, 'Opening Stock', 1, '2026-07-06 06:58:09'),
(83, 13, 4, 6, 'IN', 'OPENING', 13, 'Opening Stock', 1, '2026-07-06 06:58:39'),
(84, 13, 5, 6, 'IN', 'OPENING', 13, 'Opening Stock', 1, '2026-07-06 06:58:39'),
(85, 13, 6, 6, 'IN', 'OPENING', 13, 'Opening Stock', 1, '2026-07-06 06:58:39'),
(86, 10, 3, 1, 'OUT', 'SALE', 21, 'Invoice: INV000003', 1, '2026-07-06 07:02:50'),
(87, 12, 4, 1, 'OUT', 'SALE', 21, 'Invoice: INV000003', 1, '2026-07-06 07:02:50'),
(88, 11, 5, 1, 'OUT', 'SALE', 22, 'Invoice: INV000004', 1, '2026-07-06 07:06:23'),
(89, 12, 7, 1, 'OUT', 'SALE', 22, 'Invoice: INV000004', 1, '2026-07-06 07:06:23'),
(90, 13, 4, 1, 'OUT', 'SALE', 22, 'Invoice: INV000004', 1, '2026-07-06 07:06:23'),
(91, 10, 5, 1, 'OUT', 'SALE', 23, 'Invoice: INV000005', 1, '2026-07-06 07:10:34'),
(92, 12, 6, 1, 'OUT', 'SALE', 23, 'Invoice: INV000005', 1, '2026-07-06 07:10:34'),
(93, 13, 6, 1, 'OUT', 'SALE', 23, 'Invoice: INV000005', 1, '2026-07-06 07:10:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `mobile`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$7ktXvEfTgrF3vmbAKGvqwuVmxSJUi3t/cW72b5VcwzgIoIZWiBC2a', 'Administrator', '9999999999', '2026-05-13 04:48:43', '2026-05-13 05:33:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `credit_notes`
--
ALTER TABLE `credit_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `credit_note_no` (`credit_note_no`),
  ADD KEY `party_id` (`party_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_credit_no` (`credit_note_no`),
  ADD KEY `idx_date` (`credit_date`),
  ADD KEY `idx_invoice` (`invoice_id`);

--
-- Indexes for table `credit_note_items`
--
ALTER TABLE `credit_note_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_item_id` (`invoice_item_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `size_id` (`size_id`),
  ADD KEY `idx_credit_note` (`credit_note_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_invoice_no` (`invoice_no`),
  ADD KEY `idx_date` (`invoice_date`),
  ADD KEY `idx_party` (`party_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `size_id` (`size_id`),
  ADD KEY `idx_invoice` (`invoice_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `parties`
--
ALTER TABLE `parties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile` (`mobile`),
  ADD KEY `idx_mobile` (`mobile`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD UNIQUE KEY `product_number` (`product_number`),
  ADD KEY `idx_product_code` (`product_code`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indexes for table `product_stock`
--
ALTER TABLE `product_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_size` (`product_id`,`size_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_size` (`size_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `sizes`
--
ALTER TABLE `sizes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `size_name` (`size_name`),
  ADD UNIQUE KEY `size_code` (`size_code`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `size_id` (`size_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_type` (`transaction_type`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `credit_notes`
--
ALTER TABLE `credit_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `credit_note_items`
--
ALTER TABLE `credit_note_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `parties`
--
ALTER TABLE `parties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_stock`
--
ALTER TABLE `product_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sizes`
--
ALTER TABLE `sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 02, 2026 at 11:52 AM
-- Server version: 10.4.28-MariaDB-log
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wvsudb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `action_id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `action_type` enum('ban_user','unban_user','remove_listing','resolve_report','warn_user','verify_user','change_role') NOT NULL,
  `target_entity_id` int(10) UNSIGNED NOT NULL,
  `entity_type` enum('user','listing','report') NOT NULL,
  `notes` text DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `event_type` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `event_type`, `entity_type`, `entity_id`, `metadata`, `logged_at`) VALUES
(1, NULL, 'USER_REGISTERED', 'user', 1, '{\"email\":\"justineluis.dasa@wvsu.edu.ph\",\"full_name\":\"Justine Luis Dasa\"}', '2026-04-21 17:23:29'),
(2, NULL, 'LOGIN_FAILED', 'user', NULL, '{\"email\":\"justineluis.dasa@wvsu.edu.ph\"}', '2026-04-21 17:23:32'),
(3, 1, 'USER_LOGIN', 'user', 1, NULL, '2026-04-21 17:23:38'),
(4, 1, 'LOGIN_FAILED', 'user', NULL, '{\"email\":\"justineluis.dasa@wvsu.edu.ph\"}', '2026-04-21 17:26:29'),
(5, 1, 'USER_LOGIN', 'user', 1, NULL, '2026-04-21 17:26:31'),
(6, 1, 'USER_LOGIN', 'user', 1, NULL, '2026-04-21 17:26:51'),
(7, 1, 'PRODUCT_CREATED', 'listing', 1, '{\"title\":\"Tumbler\",\"price\":250,\"stock\":1,\"owner_id\":1}', '2026-04-21 17:27:59'),
(8, 1, 'USER_LOGOUT', 'user', 1, NULL, '2026-04-21 17:32:22'),
(9, NULL, 'USER_REGISTERED', 'user', 2, '{\"email\":\"elaijah.aman@wvsu.edu.ph\",\"full_name\":\"elaijah aman\"}', '2026-04-21 17:32:38'),
(10, 2, 'USER_LOGIN', 'user', 2, NULL, '2026-04-21 17:32:43'),
(11, 2, 'ORDER_CREATED', 'transaction', 1, '{\"buyer_id\":2,\"listing_id\":1,\"quantity\":1,\"total_price\":250}', '2026-04-21 17:32:49'),
(12, 2, 'USER_LOGOUT', 'user', 2, NULL, '2026-04-21 17:32:52'),
(13, NULL, 'LOGIN_FAILED', 'user', NULL, '{\"email\":\"justineluis.dasa@wvsu.edu.ph\"}', '2026-04-21 17:32:54'),
(14, 1, 'USER_LOGIN', 'user', 1, NULL, '2026-04-21 17:32:57'),
(15, 1, 'USER_LOGOUT', 'user', 1, NULL, '2026-04-21 17:37:48'),
(16, NULL, 'PRODUCT_CREATED', 'product', 2, '{\"listing_id\": 2, \"price\": 1000.00, \"stock\": 12}', '2026-05-01 16:15:51'),
(17, NULL, 'PRODUCT_CREATED', 'product', 3, '{\"listing_id\": 3, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(18, NULL, 'PRODUCT_CREATED', 'product', 4, '{\"listing_id\": 4, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(19, NULL, 'SERVICE_CREATED', 'service', 1, '{\"listing_id\": 5, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(20, NULL, 'PRODUCT_CREATED', 'product', 5, '{\"listing_id\": 6, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(21, NULL, 'PRODUCT_CREATED', 'product', 6, '{\"listing_id\": 7, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(22, NULL, 'SERVICE_CREATED', 'service', 2, '{\"listing_id\": 8, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(23, NULL, 'PRODUCT_CREATED', 'product', 7, '{\"listing_id\": 9, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(24, NULL, 'PRODUCT_CREATED', 'product', 8, '{\"listing_id\": 10, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(25, NULL, 'SERVICE_CREATED', 'service', 3, '{\"listing_id\": 11, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(26, NULL, 'PRODUCT_CREATED', 'product', 9, '{\"listing_id\": 12, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(27, NULL, 'PRODUCT_CREATED', 'product', 10, '{\"listing_id\": 13, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(28, NULL, 'SERVICE_CREATED', 'service', 4, '{\"listing_id\": 14, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(29, NULL, 'PRODUCT_CREATED', 'product', 11, '{\"listing_id\": 15, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(30, NULL, 'PRODUCT_CREATED', 'product', 12, '{\"listing_id\": 16, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(31, NULL, 'SERVICE_CREATED', 'service', 5, '{\"listing_id\": 17, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(32, NULL, 'PRODUCT_CREATED', 'product', 13, '{\"listing_id\": 18, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(33, NULL, 'PRODUCT_CREATED', 'product', 14, '{\"listing_id\": 19, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(34, NULL, 'SERVICE_CREATED', 'service', 6, '{\"listing_id\": 20, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(35, NULL, 'PRODUCT_CREATED', 'product', 15, '{\"listing_id\": 21, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(36, NULL, 'PRODUCT_CREATED', 'product', 16, '{\"listing_id\": 22, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(37, NULL, 'SERVICE_CREATED', 'service', 7, '{\"listing_id\": 23, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(38, NULL, 'PRODUCT_CREATED', 'product', 17, '{\"listing_id\": 24, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(39, NULL, 'PRODUCT_CREATED', 'product', 18, '{\"listing_id\": 25, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(40, NULL, 'SERVICE_CREATED', 'service', 8, '{\"listing_id\": 26, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(41, NULL, 'PRODUCT_CREATED', 'product', 19, '{\"listing_id\": 27, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(42, NULL, 'PRODUCT_CREATED', 'product', 20, '{\"listing_id\": 28, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(43, NULL, 'SERVICE_CREATED', 'service', 9, '{\"listing_id\": 29, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(44, NULL, 'PRODUCT_CREATED', 'product', 21, '{\"listing_id\": 30, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(45, NULL, 'PRODUCT_CREATED', 'product', 22, '{\"listing_id\": 31, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(46, NULL, 'SERVICE_CREATED', 'service', 10, '{\"listing_id\": 32, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(47, NULL, 'PRODUCT_CREATED', 'product', 23, '{\"listing_id\": 33, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(48, NULL, 'PRODUCT_CREATED', 'product', 24, '{\"listing_id\": 34, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(49, NULL, 'SERVICE_CREATED', 'service', 11, '{\"listing_id\": 35, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(50, NULL, 'PRODUCT_CREATED', 'product', 25, '{\"listing_id\": 36, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(51, NULL, 'PRODUCT_CREATED', 'product', 26, '{\"listing_id\": 37, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(52, NULL, 'SERVICE_CREATED', 'service', 12, '{\"listing_id\": 38, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(53, NULL, 'PRODUCT_CREATED', 'product', 27, '{\"listing_id\": 39, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(54, NULL, 'PRODUCT_CREATED', 'product', 28, '{\"listing_id\": 40, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(55, NULL, 'SERVICE_CREATED', 'service', 13, '{\"listing_id\": 41, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(56, NULL, 'PRODUCT_CREATED', 'product', 29, '{\"listing_id\": 42, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(57, NULL, 'PRODUCT_CREATED', 'product', 30, '{\"listing_id\": 43, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(58, NULL, 'SERVICE_CREATED', 'service', 14, '{\"listing_id\": 44, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(59, NULL, 'PRODUCT_CREATED', 'product', 31, '{\"listing_id\": 45, \"price\": 499.00, \"stock\": 8}', '2026-05-01 16:15:53'),
(60, NULL, 'PRODUCT_CREATED', 'product', 32, '{\"listing_id\": 46, \"price\": 350.00, \"stock\": 5}', '2026-05-01 16:15:53'),
(61, NULL, 'SERVICE_CREATED', 'service', 15, '{\"listing_id\": 47, \"rate\": 250.00, \"rate_type\": \"per_hour\"}', '2026-05-01 16:15:53'),
(62, NULL, 'SERVICE_CREATED', 'service', 16, '{\"listing_id\": 48, \"rate\": 0.00, \"rate_type\": \"negotiable\"}', '2026-05-01 17:02:40'),
(63, NULL, 'PRODUCT_UPDATED', 'product', 5, '{\"listing_id\": 6, \"old_price\": 499.00, \"new_price\": 499.00, \"old_stock\": 8, \"new_stock\": 7}', '2026-05-01 18:07:09'),
(64, NULL, 'PRODUCT_UPDATED', 'product', 5, '{\"listing_id\": 6, \"old_price\": 499.00, \"new_price\": 499.00, \"old_stock\": 7, \"new_stock\": 6}', '2026-05-01 18:18:05');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_type` enum('product','service','both') NOT NULL DEFAULT 'both',
  `parent_type` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `category_type`, `parent_type`) VALUES
(1, 'Electronics', 'product', NULL),
(2, 'Books & Notes', 'product', NULL),
(3, 'Food & Drinks', 'product', NULL),
(4, 'Clothing', 'product', NULL),
(5, 'Academic Help', 'service', NULL),
(6, 'Creative Work', 'service', NULL),
(7, 'Tech & IT', 'both', NULL),
(8, 'Others', 'both', NULL),
(9, 'Laptops & Gadgets', 'product', 1),
(10, 'Phone Accessories', 'product', 1),
(11, 'Textbooks', 'product', 2),
(12, 'Handouts & Notes', 'product', 2),
(13, 'Tutoring', 'service', 5),
(14, 'Thesis Editing', 'service', 5),
(15, 'Graphic Design', 'service', 6),
(16, 'Video Editing', 'service', 6),
(17, 'Web Development', 'service', 7);

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `participant_a` int(10) UNSIGNED NOT NULL,
  `participant_b` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_message_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`conversation_id`, `participant_a`, `participant_b`, `created_at`, `last_message_at`) VALUES
(1, 3, 5, '2026-05-01 16:16:35', '2026-05-01 18:10:25'),
(4, 3, 4, '2026-05-01 16:35:50', '2026-05-01 17:20:40');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_listings`
--

CREATE TABLE `conversation_listings` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversation_listings`
--

INSERT INTO `conversation_listings` (`id`, `conversation_id`, `listing_id`, `created_at`) VALUES
(1, 1, 6, '2026-05-01 16:16:35'),
(2, 1, 7, '2026-05-01 16:33:25'),
(3, 1, 7, '2026-05-01 16:33:34'),
(4, 4, 5, '2026-05-01 16:35:50'),
(5, 4, 5, '2026-05-01 16:35:58'),
(6, 4, 5, '2026-05-01 16:36:04'),
(7, 1, 8, '2026-05-01 16:59:43'),
(8, 1, 2, '2026-05-01 18:07:26'),
(9, 1, 2, '2026-05-01 18:10:13'),
(10, 1, 2, '2026-05-01 18:10:25');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_meta`
--

CREATE TABLE `conversation_meta` (
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversation_meta`
--

INSERT INTO `conversation_meta` (`conversation_id`, `is_closed`) VALUES
(1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `item_status`
--

CREATE TABLE `item_status` (
  `status_id` int(10) UNSIGNED NOT NULL,
  `listing_id` int(10) UNSIGNED NOT NULL,
  `old_status` enum('active','inactive','sold_out','banned') DEFAULT NULL,
  `new_status` enum('active','inactive','sold_out','banned') NOT NULL,
  `changed_by` int(10) UNSIGNED DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `listings`
--

CREATE TABLE `listings` (
  `listing_id` int(10) UNSIGNED NOT NULL,
  `owner_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `listing_type` enum('product','service') NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','sold_out','banned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listings`
--

INSERT INTO `listings` (`listing_id`, `owner_id`, `category_id`, `listing_type`, `title`, `description`, `image_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 8, 'product', 'Tumbler', 'sss', NULL, 'active', '2026-04-21 17:27:59', '2026-04-21 17:27:59'),
(2, 3, 8, 'product', 'Coffee', 'Sarap', 'uploads/products/1777652151_512998241_23905707519120209_7540854637217948740_n__1_.jpg', 'active', '2026-05-01 16:15:51', '2026-05-01 16:15:51'),
(3, 4, 9, 'product', 'Wireless Mouse 01', 'Sample seed product #1 from account 01', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(4, 4, 11, 'product', 'Engineering Textbook 01', 'Sample seed product #2 from account 01', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(5, 4, 13, 'service', 'Tutoring Session 01', 'Sample seed tutoring service from account 01', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(6, 5, 9, 'product', 'Wireless Mouse 02', 'Sample seed product #1 from account 02', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(7, 5, 11, 'product', 'Engineering Textbook 02', 'Sample seed product #2 from account 02', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(8, 5, 13, 'service', 'Tutoring Session 02', 'Sample seed tutoring service from account 02', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(9, 6, 9, 'product', 'Wireless Mouse 03', 'Sample seed product #1 from account 03', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(10, 6, 11, 'product', 'Engineering Textbook 03', 'Sample seed product #2 from account 03', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(11, 6, 13, 'service', 'Tutoring Session 03', 'Sample seed tutoring service from account 03', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(12, 7, 9, 'product', 'Wireless Mouse 04', 'Sample seed product #1 from account 04', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(13, 7, 11, 'product', 'Engineering Textbook 04', 'Sample seed product #2 from account 04', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(14, 7, 13, 'service', 'Tutoring Session 04', 'Sample seed tutoring service from account 04', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(15, 8, 9, 'product', 'Wireless Mouse 05', 'Sample seed product #1 from account 05', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(16, 8, 11, 'product', 'Engineering Textbook 05', 'Sample seed product #2 from account 05', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(17, 8, 13, 'service', 'Tutoring Session 05', 'Sample seed tutoring service from account 05', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(18, 9, 9, 'product', 'Wireless Mouse 06', 'Sample seed product #1 from account 06', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(19, 9, 11, 'product', 'Engineering Textbook 06', 'Sample seed product #2 from account 06', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(20, 9, 13, 'service', 'Tutoring Session 06', 'Sample seed tutoring service from account 06', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(21, 10, 9, 'product', 'Wireless Mouse 07', 'Sample seed product #1 from account 07', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(22, 10, 11, 'product', 'Engineering Textbook 07', 'Sample seed product #2 from account 07', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(23, 10, 13, 'service', 'Tutoring Session 07', 'Sample seed tutoring service from account 07', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(24, 11, 9, 'product', 'Wireless Mouse 08', 'Sample seed product #1 from account 08', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(25, 11, 11, 'product', 'Engineering Textbook 08', 'Sample seed product #2 from account 08', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(26, 11, 13, 'service', 'Tutoring Session 08', 'Sample seed tutoring service from account 08', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(27, 12, 9, 'product', 'Wireless Mouse 09', 'Sample seed product #1 from account 09', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(28, 12, 11, 'product', 'Engineering Textbook 09', 'Sample seed product #2 from account 09', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(29, 12, 13, 'service', 'Tutoring Session 09', 'Sample seed tutoring service from account 09', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(30, 13, 9, 'product', 'Wireless Mouse 10', 'Sample seed product #1 from account 10', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(31, 13, 11, 'product', 'Engineering Textbook 10', 'Sample seed product #2 from account 10', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(32, 13, 13, 'service', 'Tutoring Session 10', 'Sample seed tutoring service from account 10', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(33, 14, 9, 'product', 'Wireless Mouse 11', 'Sample seed product #1 from account 11', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(34, 14, 11, 'product', 'Engineering Textbook 11', 'Sample seed product #2 from account 11', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(35, 14, 13, 'service', 'Tutoring Session 11', 'Sample seed tutoring service from account 11', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(36, 15, 9, 'product', 'Wireless Mouse 12', 'Sample seed product #1 from account 12', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(37, 15, 11, 'product', 'Engineering Textbook 12', 'Sample seed product #2 from account 12', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(38, 15, 13, 'service', 'Tutoring Session 12', 'Sample seed tutoring service from account 12', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(39, 16, 9, 'product', 'Wireless Mouse 13', 'Sample seed product #1 from account 13', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(40, 16, 11, 'product', 'Engineering Textbook 13', 'Sample seed product #2 from account 13', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(41, 16, 13, 'service', 'Tutoring Session 13', 'Sample seed tutoring service from account 13', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(42, 17, 9, 'product', 'Wireless Mouse 14', 'Sample seed product #1 from account 14', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(43, 17, 11, 'product', 'Engineering Textbook 14', 'Sample seed product #2 from account 14', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(44, 17, 13, 'service', 'Tutoring Session 14', 'Sample seed tutoring service from account 14', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(45, 18, 9, 'product', 'Wireless Mouse 15', 'Sample seed product #1 from account 15', 'uploads/products/1777291346_wvsucover.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(46, 18, 11, 'product', 'Engineering Textbook 15', 'Sample seed product #2 from account 15', 'uploads/products/1777412303_Screenshot_2026-04-28_232331.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(47, 18, 13, 'service', 'Tutoring Session 15', 'Sample seed tutoring service from account 15', 'uploads/services/service_69edd915e6490.png', 'active', '2026-05-01 16:15:53', '2026-05-01 16:15:53'),
(48, 3, 8, 'service', 'James', 'asdasd', 'uploads/services/1777654960_657549726_1353123040181615_4539217126469166653_n.jpg', 'active', '2026-05-01 17:02:40', '2026-05-01 17:02:40');

--
-- Triggers `listings`
--
DELIMITER $$
CREATE TRIGGER `trg_listing_status_change_log` AFTER UPDATE ON `listings` FOR EACH ROW BEGIN
  IF (OLD.`status` != NEW.`status`) THEN
    INSERT INTO `item_status` (
      `listing_id`,
      `old_status`,
      `new_status`
    ) VALUES (
      NEW.`listing_id`,
      OLD.`status`,
      NEW.`status`
    );
    
    INSERT INTO `audit_logs` (
      `event_type`,
      `entity_type`,
      `entity_id`,
      `metadata`
    ) VALUES (
      'LISTING_STATUS_CHANGED',
      'listing',
      NEW.`listing_id`,
      JSON_OBJECT(
        'old_status', OLD.`status`,
        'new_status', NEW.`status`,
        'title', NEW.`title`
      )
    );
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `message_type` enum('text','image') NOT NULL DEFAULT 'text',
  `image_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `conversation_id`, `sender_id`, `content`, `sent_at`, `is_read`, `message_type`, `image_url`) VALUES
(1, 1, 3, 'Conversation started for listing 6.', '2026-05-01 16:16:35', 1, 'text', NULL),
(2, 1, 3, 'Hello! How much is this', '2026-05-01 16:16:42', 1, 'text', NULL),
(3, 1, 3, 'hello miss u', '2026-05-01 16:26:36', 1, 'text', ''),
(4, 1, 3, 'imissu', '2026-05-01 16:26:49', 1, 'text', ''),
(5, 1, 3, 'a', '2026-05-01 16:27:07', 1, 'text', ''),
(6, 1, 3, 'aa', '2026-05-01 16:30:13', 1, 'text', ''),
(7, 1, 3, 'adaddas', '2026-05-01 16:30:26', 1, 'text', ''),
(8, 1, 3, 'gwapo si james', '2026-05-01 16:33:06', 1, 'text', ''),
(9, 1, 3, 'lalalala', '2026-05-01 16:33:15', 1, 'text', ''),
(10, 1, 3, 'Conversation started for listing 7.', '2026-05-01 16:33:25', 1, 'text', NULL),
(11, 1, 3, 'Conversation started for listing 7.', '2026-05-01 16:33:34', 1, 'text', NULL),
(12, 4, 3, 'Conversation started for listing 5.', '2026-05-01 16:35:50', 0, 'text', NULL),
(13, 4, 3, 'Conversation started for listing 5.', '2026-05-01 16:35:58', 0, 'text', NULL),
(14, 4, 3, 'Conversation started for listing 5.', '2026-05-01 16:36:04', 0, 'text', NULL),
(15, 1, 3, 'hi', '2026-05-01 16:47:46', 1, 'text', ''),
(16, 1, 3, 'This chat is about the service “Tutoring Session 02” — open “View listing” above the thread for photo & details.', '2026-05-01 16:59:43', 1, 'text', NULL),
(17, 4, 3, 'aaa', '2026-05-01 17:20:40', 0, 'text', ''),
(18, 1, 5, 'hello', '2026-05-01 18:07:02', 0, 'text', ''),
(19, 1, 5, 'Seller completed transaction. Quantity deducted: 1.', '2026-05-01 18:07:09', 0, 'text', NULL),
(20, 1, 5, 'This chat is about the product “Coffee” — open “View listing” above the thread for photo & details.', '2026-05-01 18:07:26', 0, 'text', NULL),
(21, 1, 5, 'This chat is about the product “Coffee” — open “View listing” above the thread for photo & details.', '2026-05-01 18:10:13', 0, 'text', NULL),
(22, 1, 5, 'This chat is about the product “Coffee” — open “View listing” above the thread for photo & details.', '2026-05-01 18:10:25', 0, 'text', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `listing_id` int(10) UNSIGNED NOT NULL,
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0),
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `listing_id`, `price`, `stock`) VALUES
(1, 1, 250.00, 0),
(2, 2, 1000.00, 12),
(3, 3, 499.00, 8),
(4, 4, 350.00, 5),
(5, 6, 499.00, 6),
(6, 7, 350.00, 5),
(7, 9, 499.00, 8),
(8, 10, 350.00, 5),
(9, 12, 499.00, 8),
(10, 13, 350.00, 5),
(11, 15, 499.00, 8),
(12, 16, 350.00, 5),
(13, 18, 499.00, 8),
(14, 19, 350.00, 5),
(15, 21, 499.00, 8),
(16, 22, 350.00, 5),
(17, 24, 499.00, 8),
(18, 25, 350.00, 5),
(19, 27, 499.00, 8),
(20, 28, 350.00, 5),
(21, 30, 499.00, 8),
(22, 31, 350.00, 5),
(23, 33, 499.00, 8),
(24, 34, 350.00, 5),
(25, 36, 499.00, 8),
(26, 37, 350.00, 5),
(27, 39, 499.00, 8),
(28, 40, 350.00, 5),
(29, 42, 499.00, 8),
(30, 43, 350.00, 5),
(31, 45, 499.00, 8),
(32, 46, 350.00, 5);

--
-- Triggers `products`
--
DELIMITER $$
CREATE TRIGGER `trg_product_delete_log` BEFORE DELETE ON `products` FOR EACH ROW BEGIN
  INSERT INTO `audit_logs` (
    `event_type`,
    `entity_type`,
    `entity_id`,
    `metadata`
  ) VALUES (
    'PRODUCT_DELETED',
    'product',
    OLD.`product_id`,
    JSON_OBJECT(
      'listing_id', OLD.`listing_id`,
      'price', OLD.`price`,
      'stock', OLD.`stock`
    )
  );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_product_insert_log` AFTER INSERT ON `products` FOR EACH ROW BEGIN
  INSERT INTO `audit_logs` (
    `event_type`,
    `entity_type`,
    `entity_id`,
    `metadata`
  ) VALUES (
    'PRODUCT_CREATED',
    'product',
    NEW.`product_id`,
    JSON_OBJECT(
      'listing_id', NEW.`listing_id`,
      'price', NEW.`price`,
      'stock', NEW.`stock`
    )
  );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_product_update_log` AFTER UPDATE ON `products` FOR EACH ROW BEGIN
  IF (OLD.`price` != NEW.`price` OR OLD.`stock` != NEW.`stock`) THEN
    INSERT INTO `audit_logs` (
      `event_type`,
      `entity_type`,
      `entity_id`,
      `metadata`
    ) VALUES (
      'PRODUCT_UPDATED',
      'product',
      NEW.`product_id`,
      JSON_OBJECT(
        'listing_id', NEW.`listing_id`,
        'old_price', OLD.`price`,
        'new_price', NEW.`price`,
        'old_stock', OLD.`stock`,
        'new_stock', NEW.`stock`
      )
    );
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `product_orders`
--

CREATE TABLE `product_orders` (
  `order_id` int(10) UNSIGNED NOT NULL,
  `transaction_id` int(10) UNSIGNED NOT NULL,
  `delivery_address` text NOT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_orders`
--

INSERT INTO `product_orders` (`order_id`, `transaction_id`, `delivery_address`, `delivered_at`) VALUES
(1, 1, 'TBD', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `replication_live_test`
--

CREATE TABLE `replication_live_test` (
  `id` int(11) NOT NULL,
  `message` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `replication_live_test`
--

INSERT INTO `replication_live_test` (`id`, `message`, `created_at`) VALUES
(1, 'Live test from master', '2026-05-01 13:34:36');

-- --------------------------------------------------------

--
-- Table structure for table `replication_live_test_2`
--

CREATE TABLE `replication_live_test_2` (
  `id` int(11) NOT NULL,
  `message` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `replication_live_test_2`
--

INSERT INTO `replication_live_test_2` (`id`, `message`, `created_at`) VALUES
(1, 'Fresh test from real master', '2026-05-01 13:35:59');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(10) UNSIGNED NOT NULL,
  `reviewer_id` int(10) UNSIGNED NOT NULL,
  `transaction_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' CHECK (json_valid(`permissions`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `name`, `permissions`) VALUES
(1, 'admin', '[\"manage_users\",\"manage_listings\",\"manage_reports\",\"view_logs\"]'),
(2, 'moderator', '[\"manage_listings\",\"manage_reports\"]'),
(3, 'student', '[\"create_listing\",\"place_order\",\"send_message\",\"write_review\"]');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(10) UNSIGNED NOT NULL,
  `listing_id` int(10) UNSIGNED NOT NULL,
  `rate` decimal(10,2) NOT NULL CHECK (`rate` >= 0),
  `rate_type` enum('per_hour','per_task','fixed','negotiable') NOT NULL DEFAULT 'fixed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `listing_id`, `rate`, `rate_type`) VALUES
(1, 5, 250.00, 'per_hour'),
(2, 8, 250.00, 'per_hour'),
(3, 11, 250.00, 'per_hour'),
(4, 14, 250.00, 'per_hour'),
(5, 17, 250.00, 'per_hour'),
(6, 20, 250.00, 'per_hour'),
(7, 23, 250.00, 'per_hour'),
(8, 26, 250.00, 'per_hour'),
(9, 29, 250.00, 'per_hour'),
(10, 32, 250.00, 'per_hour'),
(11, 35, 250.00, 'per_hour'),
(12, 38, 250.00, 'per_hour'),
(13, 41, 250.00, 'per_hour'),
(14, 44, 250.00, 'per_hour'),
(15, 47, 250.00, 'per_hour'),
(16, 48, 0.00, 'negotiable');

--
-- Triggers `services`
--
DELIMITER $$
CREATE TRIGGER `trg_service_delete_log` BEFORE DELETE ON `services` FOR EACH ROW BEGIN
  INSERT INTO `audit_logs` (
    `event_type`,
    `entity_type`,
    `entity_id`,
    `metadata`
  ) VALUES (
    'SERVICE_DELETED',
    'service',
    OLD.`service_id`,
    JSON_OBJECT(
      'listing_id', OLD.`listing_id`,
      'rate', OLD.`rate`,
      'rate_type', OLD.`rate_type`
    )
  );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_service_insert_log` AFTER INSERT ON `services` FOR EACH ROW BEGIN
  INSERT INTO `audit_logs` (
    `event_type`,
    `entity_type`,
    `entity_id`,
    `metadata`
  ) VALUES (
    'SERVICE_CREATED',
    'service',
    NEW.`service_id`,
    JSON_OBJECT(
      'listing_id', NEW.`listing_id`,
      'rate', NEW.`rate`,
      'rate_type', NEW.`rate_type`
    )
  );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_service_update_log` AFTER UPDATE ON `services` FOR EACH ROW BEGIN
  IF (OLD.`rate` != NEW.`rate` OR OLD.`rate_type` != NEW.`rate_type`) THEN
    INSERT INTO `audit_logs` (
      `event_type`,
      `entity_type`,
      `entity_id`,
      `metadata`
    ) VALUES (
      'SERVICE_UPDATED',
      'service',
      NEW.`service_id`,
      JSON_OBJECT(
        'listing_id', NEW.`listing_id`,
        'old_rate', OLD.`rate`,
        'new_rate', NEW.`rate`,
        'old_rate_type', OLD.`rate_type`,
        'new_rate_type', NEW.`rate_type`
      )
    );
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `service_bookings`
--

CREATE TABLE `service_bookings` (
  `booking_id` int(10) UNSIGNED NOT NULL,
  `transaction_id` int(10) UNSIGNED NOT NULL,
  `requirements` text DEFAULT NULL,
  `agreed_price` decimal(10,2) NOT NULL,
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_portfolio_items`
--

CREATE TABLE `service_portfolio_items` (
  `portfolio_id` int(10) UNSIGNED NOT NULL,
  `listing_id` int(10) UNSIGNED NOT NULL,
  `media_type` enum('image','video') NOT NULL DEFAULT 'image',
  `file_path` varchar(500) NOT NULL,
  `grid_span` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_portfolio_items`
--

INSERT INTO `service_portfolio_items` (`portfolio_id`, `listing_id`, `media_type`, `file_path`, `grid_span`, `sort_order`, `created_at`) VALUES
(1, 48, 'image', 'uploads/services/portfolio/pf_48_1777654960_d9893644.jpg', 1, 0, '2026-05-01 17:02:40'),
(2, 48, 'image', 'uploads/services/portfolio/pf_48_1777654960_bed1864b.png', 1, 1, '2026-05-01 17:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `service_pricing_items`
--

CREATE TABLE `service_pricing_items` (
  `price_item_id` int(10) UNSIGNED NOT NULL,
  `listing_id` int(10) UNSIGNED NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(10) UNSIGNED NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `listing_id` int(10) UNSIGNED NOT NULL,
  `transaction_type` enum('product','service') NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled','disputed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `buyer_id`, `listing_id`, `transaction_type`, `quantity`, `total_price`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'product', 1, 250.00, 'pending', '2026-04-21 17:32:49', '2026-04-21 17:32:49');

-- --------------------------------------------------------

--
-- Table structure for table `uploaded_files_test`
--

CREATE TABLE `uploaded_files_test` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uploaded_files_test`
--

INSERT INTO `uploaded_files_test` (`id`, `file_name`, `file_path`, `uploaded_at`) VALUES
(1, 'sample_master_upload.pdf', '/uploads/sample_master_upload.pdf', '2026-05-01 13:30:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `profile_pic_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `bio` text DEFAULT NULL,
  `social_instagram` varchar(500) DEFAULT NULL,
  `social_facebook` varchar(500) DEFAULT NULL,
  `social_x` varchar(500) DEFAULT NULL,
  `social_tiktok` varchar(500) DEFAULT NULL,
  `social_linkedin` varchar(500) DEFAULT NULL,
  `social_website` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `role_id`, `profile_pic_url`, `is_active`, `is_verified`, `created_at`, `updated_at`, `bio`, `social_instagram`, `social_facebook`, `social_x`, `social_tiktok`, `social_linkedin`, `social_website`) VALUES
(1, 'Justine Luis Dasa', 'justineluis.dasa@wvsu.edu.ph', '$2y$10$O0iOb/lf9ELps.b.tdicOeHy.z/Rn4RHQVM8nB/nRMKWJiYynVebq', 3, NULL, 1, 0, '2026-04-21 17:23:29', '2026-04-21 17:23:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'elaijah aman', 'elaijah.aman@wvsu.edu.ph', '$2y$10$PLSxogTntGxtsRqIcdnzYu8Ed8D1mhVxGObqmyTut88MjPNE25RlS', 3, NULL, 1, 0, '2026-04-21 17:32:38', '2026-04-21 17:32:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'James Remegio', 'james.remegio@wvsu.edu.ph', '$2y$10$xieHwTZAQDQV29nhBqdhd.IXOJTBcA7rbZoCLdxQRd1MJpj0ptvXe', 1, 'uploads/profiles/user_3_1777651503.jpg', 1, 0, '2026-05-01 13:49:46', '2026-05-01 16:05:03', '', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Seed User 01', 'seed.user01@wvsu.edu.ph', 'pass123', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 17:29:33', 'I am Seed User 01, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Seed User 02', 'seed.user02@wvsu.edu.ph', '$2y$10$gdgO3F/mM0c2d1SLEtMVAOtzjRLuuuuyCENcjmkQ/lGpmQUfLc9Ne', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 18:06:21', 'I am Seed User 02, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Seed User 03', 'seed.user03@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 03, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Seed User 04', 'seed.user04@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 04, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Seed User 05', 'seed.user05@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 05, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'Seed User 06', 'seed.user06@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 06, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'Seed User 07', 'seed.user07@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 07, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'Seed User 08', 'seed.user08@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 08, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'Seed User 09', 'seed.user09@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 09, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'Seed User 10', 'seed.user10@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 10, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'Seed User 11', 'seed.user11@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 11, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'Seed User 12', 'seed.user12@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 12, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'Seed User 13', 'seed.user13@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 13, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'Seed User 14', 'seed.user14@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 14, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'Seed User 15', 'seed.user15@wvsu.edu.ph', '$2y$10$3Vi3tw.e0IOzs.vsRTIoheXVYmlfXyb/NRSJWr47LdKUOWC9QADfm', 3, NULL, 1, 1, '2026-05-01 16:15:53', '2026-05-01 16:15:53', 'I am Seed User 15, a sample account for testing products and services.', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_reports`
--

CREATE TABLE `user_reports` (
  `report_id` int(10) UNSIGNED NOT NULL,
  `reporter_id` int(10) UNSIGNED NOT NULL,
  `target_user_id` int(10) UNSIGNED NOT NULL,
  `listing_id` int(10) UNSIGNED DEFAULT NULL,
  `conversation_id` int(10) UNSIGNED DEFAULT NULL,
  `reason_type` enum('scam','unwanted_item','harassment','fake_profile','other') NOT NULL DEFAULT 'other',
  `details` text DEFAULT NULL,
  `status` enum('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(10) UNSIGNED DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_reviews`
--

CREATE TABLE `user_reviews` (
  `review_id` int(10) UNSIGNED NOT NULL,
  `reviewer_id` int(10) UNSIGNED NOT NULL,
  `reviewee_id` int(10) UNSIGNED NOT NULL,
  `listing_id` int(10) UNSIGNED DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_reviews`
--

INSERT INTO `user_reviews` (`review_id`, `reviewer_id`, `reviewee_id`, `listing_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(1, 3, 4, NULL, 5, 'gfsdgf', '2026-05-01 17:22:10', '2026-05-01 17:22:10');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_activity` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `fk_admin_actions_admin` (`admin_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_audit_logs_user` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `fk_categories_parent` (`parent_type`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`conversation_id`),
  ADD UNIQUE KEY `uq_conversation` (`participant_a`,`participant_b`),
  ADD KEY `fk_conv_b` (`participant_b`);

--
-- Indexes for table `conversation_listings`
--
ALTER TABLE `conversation_listings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `listing_id` (`listing_id`);

--
-- Indexes for table `conversation_meta`
--
ALTER TABLE `conversation_meta`
  ADD PRIMARY KEY (`conversation_id`);

--
-- Indexes for table `item_status`
--
ALTER TABLE `item_status`
  ADD PRIMARY KEY (`status_id`),
  ADD KEY `idx_item_status_listing` (`listing_id`),
  ADD KEY `idx_item_status_changed_by` (`changed_by`),
  ADD KEY `idx_item_status_changed_at` (`changed_at`);

--
-- Indexes for table `listings`
--
ALTER TABLE `listings`
  ADD PRIMARY KEY (`listing_id`),
  ADD KEY `idx_listings_owner` (`owner_id`),
  ADD KEY `idx_listings_category` (`category_id`),
  ADD KEY `idx_listings_status` (`status`),
  ADD KEY `idx_listings_type` (`listing_type`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_messages_conv` (`conversation_id`),
  ADD KEY `fk_messages_sender` (`sender_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `listing_id` (`listing_id`);

--
-- Indexes for table `product_orders`
--
ALTER TABLE `product_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `replication_live_test`
--
ALTER TABLE `replication_live_test`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `replication_live_test_2`
--
ALTER TABLE `replication_live_test_2`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `fk_reviews_reviewer` (`reviewer_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD UNIQUE KEY `listing_id` (`listing_id`);

--
-- Indexes for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `service_portfolio_items`
--
ALTER TABLE `service_portfolio_items`
  ADD PRIMARY KEY (`portfolio_id`),
  ADD KEY `idx_portfolio_listing` (`listing_id`),
  ADD KEY `idx_portfolio_sort` (`listing_id`,`sort_order`);

--
-- Indexes for table `service_pricing_items`
--
ALTER TABLE `service_pricing_items`
  ADD PRIMARY KEY (`price_item_id`),
  ADD KEY `idx_spi_listing` (`listing_id`),
  ADD KEY `idx_spi_sort` (`listing_id`,`sort_order`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_transactions_buyer` (`buyer_id`),
  ADD KEY `idx_transactions_listing` (`listing_id`),
  ADD KEY `idx_transactions_status` (`status`);

--
-- Indexes for table `uploaded_files_test`
--
ALTER TABLE `uploaded_files_test`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_role` (`role_id`);

--
-- Indexes for table `user_reports`
--
ALTER TABLE `user_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_reports_status` (`status`),
  ADD KEY `idx_reports_target` (`target_user_id`),
  ADD KEY `idx_reports_listing` (`listing_id`),
  ADD KEY `idx_reports_reporter` (`reporter_id`),
  ADD KEY `idx_reports_resolved_by` (`resolved_by`);

--
-- Indexes for table `user_reviews`
--
ALTER TABLE `user_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `idx_reviewer_reviewee_pair` (`reviewer_id`,`reviewee_id`),
  ADD KEY `idx_reviews_reviewee` (`reviewee_id`),
  ADD KEY `idx_reviews_listing` (`listing_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_user_sessions_user` (`user_id`),
  ADD KEY `idx_user_sessions_token` (`session_token`),
  ADD KEY `idx_user_sessions_active` (`is_active`),
  ADD KEY `idx_user_sessions_login_at` (`login_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `action_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `conversation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `conversation_listings`
--
ALTER TABLE `conversation_listings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `item_status`
--
ALTER TABLE `item_status`
  MODIFY `status_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `listings`
--
ALTER TABLE `listings`
  MODIFY `listing_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `product_orders`
--
ALTER TABLE `product_orders`
  MODIFY `order_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `replication_live_test`
--
ALTER TABLE `replication_live_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `replication_live_test_2`
--
ALTER TABLE `replication_live_test_2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `service_bookings`
--
ALTER TABLE `service_bookings`
  MODIFY `booking_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_portfolio_items`
--
ALTER TABLE `service_portfolio_items`
  MODIFY `portfolio_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `service_pricing_items`
--
ALTER TABLE `service_pricing_items`
  MODIFY `price_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `uploaded_files_test`
--
ALTER TABLE `uploaded_files_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_reports`
--
ALTER TABLE `user_reports`
  MODIFY `report_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_reviews`
--
ALTER TABLE `user_reviews`
  MODIFY `review_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `session_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD CONSTRAINT `fk_admin_actions_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_type`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `fk_conv_a` FOREIGN KEY (`participant_a`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_conv_b` FOREIGN KEY (`participant_b`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `item_status`
--
ALTER TABLE `item_status`
  ADD CONSTRAINT `fk_item_status_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_item_status_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;

--
-- Constraints for table `listings`
--
ALTER TABLE `listings`
  ADD CONSTRAINT `fk_listings_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `fk_listings_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_orders`
--
ALTER TABLE `product_orders`
  ADD CONSTRAINT `fk_product_orders_txn` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_reviews_txn` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`);

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_services_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;

--
-- Constraints for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD CONSTRAINT `fk_service_bookings_txn` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON DELETE CASCADE;

--
-- Constraints for table `service_portfolio_items`
--
ALTER TABLE `service_portfolio_items`
  ADD CONSTRAINT `fk_portfolio_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;

--
-- Constraints for table `service_pricing_items`
--
ALTER TABLE `service_pricing_items`
  ADD CONSTRAINT `fk_spi_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_transactions_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `user_reports`
--
ALTER TABLE `user_reports`
  ADD CONSTRAINT `fk_reports_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reports_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reports_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reports_target` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_reviews`
--
ALTER TABLE `user_reviews`
  ADD CONSTRAINT `fk_ur_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ur_reviewee` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ur_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

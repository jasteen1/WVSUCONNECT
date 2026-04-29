-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 25, 2026 at 02:00 PM
-- Server version: 10.4.32-MariaDB-log
-- PHP Version: 8.0.30

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
(15, 1, 'USER_LOGOUT', 'user', 1, NULL, '2026-04-21 17:37:48');

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
(1, 1, 8, 'product', 'Tumbler', 'sss', NULL, 'active', '2026-04-21 17:27:59', '2026-04-21 17:27:59');

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
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 1, 250.00, 0);

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `role_id`, `profile_pic_url`, `is_active`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 'Justine Luis Dasa', 'justineluis.dasa@wvsu.edu.ph', '$2y$10$O0iOb/lf9ELps.b.tdicOeHy.z/Rn4RHQVM8nB/nRMKWJiYynVebq', 3, NULL, 1, 0, '2026-04-21 17:23:29', '2026-04-21 17:23:29'),
(2, 'elaijah aman', 'elaijah.aman@wvsu.edu.ph', '$2y$10$PLSxogTntGxtsRqIcdnzYu8Ed8D1mhVxGObqmyTut88MjPNE25RlS', 3, NULL, 1, 0, '2026-04-21 17:32:38', '2026-04-21 17:32:38');

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
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_transactions_buyer` (`buyer_id`),
  ADD KEY `idx_transactions_listing` (`listing_id`),
  ADD KEY `idx_transactions_status` (`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_role` (`role_id`);

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
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `conversation_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `listings`
--
ALTER TABLE `listings`
  MODIFY `listing_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `product_orders`
--
ALTER TABLE `product_orders`
  MODIFY `order_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `service_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_bookings`
--
ALTER TABLE `service_bookings`
  MODIFY `booking_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

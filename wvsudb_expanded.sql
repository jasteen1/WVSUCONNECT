-- ========================================
-- WVSU MARKETPLACE - EXPANDED SCHEMA
-- Master-Slave Replication Ready
-- Generated: April 25, 2026
-- ========================================

-- NEW TABLES FOR MASTER-SLAVE ARCHITECTURE

-- ========================================
-- Table: item_status
-- Purpose: Track history of product/service status changes
-- ========================================
CREATE TABLE IF NOT EXISTS `item_status` (
  `status_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `listing_id` int(10) UNSIGNED NOT NULL,
  `old_status` enum('active','inactive','sold_out','banned') DEFAULT NULL,
  `new_status` enum('active','inactive','sold_out','banned') NOT NULL,
  `changed_by` int(10) UNSIGNED DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`status_id`),
  KEY `idx_item_status_listing` (`listing_id`),
  KEY `idx_item_status_changed_by` (`changed_by`),
  KEY `idx_item_status_changed_at` (`changed_at`),
  CONSTRAINT `fk_item_status_listing` FOREIGN KEY (`listing_id`) REFERENCES `listings` (`listing_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_status_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- Table: user_sessions
-- Purpose: Track secure user session data for login tracking
-- ========================================
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `session_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_token` varchar(255) NOT NULL UNIQUE,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_activity` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`session_id`),
  KEY `idx_user_sessions_user` (`user_id`),
  KEY `idx_user_sessions_token` (`session_token`),
  KEY `idx_user_sessions_active` (`is_active`),
  KEY `idx_user_sessions_login_at` (`login_at`),
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- MariaDB TRIGGERS FOR AUTOMATIC LOGGING
-- ========================================

-- Trigger: Log product creation
DELIMITER //
CREATE TRIGGER `trg_product_insert_log`
AFTER INSERT ON `products`
FOR EACH ROW
BEGIN
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
END //
DELIMITER ;

-- Trigger: Log product update
DELIMITER //
CREATE TRIGGER `trg_product_update_log`
AFTER UPDATE ON `products`
FOR EACH ROW
BEGIN
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
END //
DELIMITER ;

-- Trigger: Log product deletion
DELIMITER //
CREATE TRIGGER `trg_product_delete_log`
BEFORE DELETE ON `products`
FOR EACH ROW
BEGIN
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
END //
DELIMITER ;

-- Trigger: Log service creation
DELIMITER //
CREATE TRIGGER `trg_service_insert_log`
AFTER INSERT ON `services`
FOR EACH ROW
BEGIN
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
END //
DELIMITER ;

-- Trigger: Log service update
DELIMITER //
CREATE TRIGGER `trg_service_update_log`
AFTER UPDATE ON `services`
FOR EACH ROW
BEGIN
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
END //
DELIMITER ;

-- Trigger: Log service deletion
DELIMITER //
CREATE TRIGGER `trg_service_delete_log`
BEFORE DELETE ON `services`
FOR EACH ROW
BEGIN
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
END //
DELIMITER ;

-- Trigger: Log listing status changes
DELIMITER //
CREATE TRIGGER `trg_listing_status_change_log`
AFTER UPDATE ON `listings`
FOR EACH ROW
BEGIN
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
END //
DELIMITER ;

-- ========================================
-- END OF EXPANDED SCHEMA
-- ========================================

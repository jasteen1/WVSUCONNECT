-- =============================================================================
-- WVSU CONNECT — run this on XAMPP / phpMyAdmin (MySQL or MariaDB)
-- =============================================================================
--
-- BEFORE YOU START:
--   1. Select your app database (usually `wvsudb`) in the left sidebar.
--   2. Open the **SQL** tab and paste this file in sections, OR run the whole file
--      if your client allows (some hosts limit long scripts).
--   3. If a line errors with **Duplicate column**, **Table already exists**, or
--      **Can't DROP** (index missing) — that is OK: it means that part was
--      already applied. Skip that line and continue.
--   4. Keep a backup of your database (Export) before running on production.
--
-- This file rolls up objects the PHP app may create at runtime, plus changes
-- from earlier migration files 001–005, so teammates can align a fresh XAMPP
-- import with the current codebase in one place.
--
-- Demo logins (same password for all): run **`007_test_users_seed.sql`** after this.
-- Sample marketplace rows: run **`008_sample_listings_seed.sql`** after users + schema.
--
-- Optional: set the database name here (uncomment) if your client does not
-- pre-select the DB:
--   USE wvsudb;
--
-- =============================================================================
-- A) CONVERSATION ↔ LISTING MAPPING (db_conn.php, contact / messages)
-- =============================================================================

CREATE TABLE IF NOT EXISTS conversation_listings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  listing_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (conversation_id),
  INDEX (listing_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- B) “CLOSED CHAT” AFTER COMPLETE SALE (messages / complete_transaction)
-- =============================================================================

CREATE TABLE IF NOT EXISTS conversation_meta (
  conversation_id INT UNSIGNED NOT NULL PRIMARY KEY,
  is_closed TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- C) CONVERSATIONS: allow more than one thread per user-pair (db_conn.php)
--    If you see: “Can't DROP 'uq_conversation'” — the index is already gone. OK.
-- =============================================================================

ALTER TABLE conversations DROP INDEX uq_conversation;

-- Newer dumps: ensure new rows get an id (db_conn.php)
-- If this errors (e.g. already AUTO_INCREMENT), ignore.
ALTER TABLE conversations
  MODIFY conversation_id INT UNSIGNED NOT NULL AUTO_INCREMENT;

-- =============================================================================
-- D) MESSAGES: image attachments + read receipts (messaging_schema.inc.php)
--    Ignore “Duplicate column name” if you already added these.
-- =============================================================================

ALTER TABLE messages
  ADD COLUMN message_type ENUM('text', 'image') NOT NULL DEFAULT 'text';

ALTER TABLE messages
  ADD COLUMN image_url VARCHAR(500) NULL DEFAULT NULL;

-- `is_read` is in the base wvsudb.sql; if an old DB is missing it, uncomment:
-- ALTER TABLE messages
--   ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0;

-- =============================================================================
-- E) USERS: profile / socials (profiles_reviews.inc.php)
--    Run one line at a time; skip if column already exists.
-- =============================================================================

ALTER TABLE users ADD COLUMN bio TEXT NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN social_instagram VARCHAR(500) NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN social_facebook VARCHAR(500) NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN social_x VARCHAR(500) NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN social_tiktok VARCHAR(500) NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN social_linkedin VARCHAR(500) NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN social_website VARCHAR(500) NULL DEFAULT NULL;

-- =============================================================================
-- F) SERVICE PORTFOLIO + PRICING (migrations 001, 002)
-- =============================================================================

CREATE TABLE IF NOT EXISTS service_portfolio_items (
  portfolio_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id INT UNSIGNED NOT NULL,
  media_type ENUM('image', 'video') NOT NULL DEFAULT 'image',
  file_path VARCHAR(500) NOT NULL,
  grid_span TINYINT UNSIGNED NOT NULL DEFAULT 1,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (portfolio_id),
  KEY idx_portfolio_listing (listing_id),
  KEY idx_portfolio_sort (listing_id, sort_order),
  CONSTRAINT fk_portfolio_listing FOREIGN KEY (listing_id)
    REFERENCES listings (listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_pricing_items (
  price_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id INT UNSIGNED NOT NULL,
  item_name VARCHAR(150) NOT NULL,
  amount DECIMAL(10, 2) DEFAULT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (price_item_id),
  KEY idx_spi_listing (listing_id),
  KEY idx_spi_sort (listing_id, sort_order),
  CONSTRAINT fk_spi_listing FOREIGN KEY (listing_id)
    REFERENCES listings (listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- G) REPORTS + ADMIN AUDIT (migration 003; moderation.inc.php)
-- =============================================================================

CREATE TABLE IF NOT EXISTS user_reports (
  report_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reporter_id INT UNSIGNED NOT NULL,
  target_user_id INT UNSIGNED NOT NULL,
  listing_id INT UNSIGNED DEFAULT NULL,
  conversation_id INT UNSIGNED DEFAULT NULL,
  reason_type ENUM('scam', 'unwanted_item', 'harassment', 'fake_profile', 'other') NOT NULL DEFAULT 'other',
  details TEXT DEFAULT NULL,
  status ENUM('pending', 'reviewing', 'resolved', 'dismissed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP NULL DEFAULT NULL,
  resolved_by INT UNSIGNED DEFAULT NULL,
  resolution_notes TEXT DEFAULT NULL,
  PRIMARY KEY (report_id),
  KEY idx_reports_status (status),
  KEY idx_reports_target (target_user_id),
  KEY idx_reports_listing (listing_id),
  KEY idx_reports_reporter (reporter_id),
  KEY idx_reports_resolved_by (resolved_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_actions (
  action_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id INT UNSIGNED NOT NULL,
  action_type ENUM('ban_user', 'unban_user', 'remove_listing', 'resolve_report', 'warn_user', 'verify_user', 'change_role') NOT NULL,
  target_entity_id INT UNSIGNED NOT NULL,
  entity_type ENUM('user', 'listing', 'report') NOT NULL,
  notes TEXT DEFAULT NULL,
  performed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (action_id),
  KEY idx_admin_actions_admin (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- H) USER REVIEWS (migration 004 / profiles_reviews.inc.php)
-- =============================================================================

CREATE TABLE IF NOT EXISTS user_reviews (
  review_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reviewer_id INT UNSIGNED NOT NULL,
  reviewee_id INT UNSIGNED NOT NULL,
  listing_id INT UNSIGNED NULL DEFAULT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id),
  KEY idx_reviewer_reviewee_pair (reviewer_id, reviewee_id),
  KEY idx_reviews_reviewee (reviewee_id),
  KEY idx_reviews_listing (listing_id),
  CONSTRAINT fk_ur_reviewer FOREIGN KEY (reviewer_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_ur_reviewee FOREIGN KEY (reviewee_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_ur_listing FOREIGN KEY (listing_id) REFERENCES listings (listing_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- I) OPTIONAL: message media migration file (005_messages_media.sql) duplicate
--     Same as section D — kept for reference only.
-- =============================================================================

-- =============================================================================
-- J) SANITY CHECKS (read-only; should return counts ≥ 1 where tables exist)
-- =============================================================================

-- SELECT COUNT(*) AS conversation_listings_exists FROM information_schema.tables
--   WHERE table_schema = DATABASE() AND table_name = 'conversation_listings';
-- SELECT COUNT(*) AS conversation_meta_exists FROM information_schema.tables
--   WHERE table_schema = DATABASE() AND table_name = 'conversation_meta';
-- SELECT COUNT(*) AS messages_has_message_type FROM information_schema.columns
--   WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = 'message_type';

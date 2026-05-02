-- Optional manual migration: moderation reports table
CREATE TABLE IF NOT EXISTS user_reports (
  report_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  reporter_id INT UNSIGNED NOT NULL,
  target_user_id INT UNSIGNED NOT NULL,
  listing_id INT UNSIGNED DEFAULT NULL,
  conversation_id INT UNSIGNED DEFAULT NULL,
  reason_type ENUM('scam','unwanted_item','harassment','fake_profile','other') NOT NULL DEFAULT 'other',
  details TEXT DEFAULT NULL,
  status ENUM('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending',
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

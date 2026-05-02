-- Optional manual migration — the app auto-applies the same DDL via profiles_reviews.inc.php (db_conn.php).

-- If you maintain schema only in SQL files, run one ALTER per column after checking `information_schema` (older MySQL lacks IF NOT EXISTS on ADD COLUMN).

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
  UNIQUE KEY uq_reviewer_reviewee (reviewer_id, reviewee_id),
  KEY idx_reviews_reviewee (reviewee_id),
  KEY idx_reviews_listing (listing_id),
  CONSTRAINT fk_ur_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_ur_reviewee FOREIGN KEY (reviewee_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_ur_listing FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

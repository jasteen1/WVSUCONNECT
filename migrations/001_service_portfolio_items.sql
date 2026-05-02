-- Optional manual migration (otherwise created automatically via db_conn + service_portfolio.inc.php).
CREATE TABLE IF NOT EXISTS service_portfolio_items (
  portfolio_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id INT UNSIGNED NOT NULL,
  media_type ENUM('image','video') NOT NULL DEFAULT 'image',
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

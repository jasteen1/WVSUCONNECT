-- Optional manual migration for freelancer price list rows.
CREATE TABLE IF NOT EXISTS service_pricing_items (
  price_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id INT UNSIGNED NOT NULL,
  item_name VARCHAR(150) NOT NULL,
  amount DECIMAL(10,2) DEFAULT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (price_item_id),
  KEY idx_spi_listing (listing_id),
  KEY idx_spi_sort (listing_id, sort_order),
  CONSTRAINT fk_spi_listing FOREIGN KEY (listing_id)
    REFERENCES listings (listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

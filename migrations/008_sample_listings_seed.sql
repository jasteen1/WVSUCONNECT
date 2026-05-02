-- =============================================================================
-- WVSU CONNECT — sample products & services (demo / classroom)
-- =============================================================================
--
-- Run AFTER:
--   • `wvsudb.sql` (or full schema) — needs `categories`, `listings`, `products`, `services`, `users`, `roles`
--   • `007_test_users_seed.sql` (recommended) — uses seed.*@wvsu.edu.ph accounts
--
-- Titles start with `[WVSU Demo]` so you can re-run this file: it removes old
-- demo rows first (see note on foreign keys below).
--
-- Categories use IDs from the stock `wvsudb.sql` seed (1–17).
-- =============================================================================

-- USE wvsudb;

SET @seller := (SELECT `user_id` FROM `users` WHERE `email` = 'seed.seller@wvsu.edu.ph' LIMIT 1);
SET @buyer1 := (SELECT `user_id` FROM `users` WHERE `email` = 'seed.user01@wvsu.edu.ph' LIMIT 1);
SET @buyer2 := (SELECT `user_id` FROM `users` WHERE `email` = 'seed.user02@wvsu.edu.ph' LIMIT 1);

-- Fallback if seed accounts were not imported (uses lowest user id — adjust if needed)
SET @fallback := (SELECT `user_id` FROM `users` ORDER BY `user_id` ASC LIMIT 1);
SET @p_owner := IFNULL(@seller, IFNULL(@buyer1, @fallback));
SET @s_owner_a := IFNULL(@buyer1, @p_owner);
SET @s_owner_b := IFNULL(@buyer2, @p_owner);

-- Remove previous demo rows (titles tagged with prefix). Temporarily relax FK
-- checks so listings drop cleanly if nothing else references them.
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `listings` WHERE `title` LIKE '[WVSU Demo]%';
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- PRODUCTS (seed.seller or first available test account)
-- -----------------------------------------------------------------------------

INSERT INTO `listings` (`owner_id`, `category_id`, `listing_type`, `title`, `description`, `image_url`, `status`)
VALUES (
    @p_owner,
    9,
    'product',
    '[WVSU Demo] Scientific calculator (CAS-ready)',
    'Used for engineering calc; battery OK. Meet at WVSU main campus gate or agreed spot.',
    NULL,
    'active'
);
SET @lid := LAST_INSERT_ID();
INSERT INTO `products` (`listing_id`, `price`, `stock`) VALUES (@lid, 899.00, 2);

INSERT INTO `listings` (`owner_id`, `category_id`, `listing_type`, `title`, `description`, `image_url`, `status`)
VALUES (
    @p_owner,
    12,
    'product',
    '[WVSU Demo] Printed notes — Data Structures',
    'Clean spiral-bound printout from last term. Pickup daytime only.',
    NULL,
    'active'
);
SET @lid := LAST_INSERT_ID();
INSERT INTO `products` (`listing_id`, `price`, `stock`) VALUES (@lid, 120.00, 8);

INSERT INTO `listings` (`owner_id`, `category_id`, `listing_type`, `title`, `description`, `image_url`, `status`)
VALUES (
    @p_owner,
    10,
    'product',
    '[WVSU Demo] USB-C cable & phone stand bundle',
    'Generic brand cable + small desk stand. Good for study desk setup.',
    NULL,
    'active'
);
SET @lid := LAST_INSERT_ID();
INSERT INTO `products` (`listing_id`, `price`, `stock`) VALUES (@lid, 185.50, 6);

INSERT INTO `listings` (`owner_id`, `category_id`, `listing_type`, `title`, `description`, `image_url`, `status`)
VALUES (
    @p_owner,
    4,
    'product',
    '[WVSU Demo] Hoodie (Wildcats grey, size L)',
    'Washed, no stains. Try-on friendly — meet in public area on campus.',
    NULL,
    'active'
);
SET @lid := LAST_INSERT_ID();
INSERT INTO `products` (`listing_id`, `price`, `stock`) VALUES (@lid, 420.00, 1);

-- -----------------------------------------------------------------------------
-- SERVICES (split across test accounts when available)
-- -----------------------------------------------------------------------------

INSERT INTO `listings` (`owner_id`, `category_id`, `listing_type`, `title`, `description`, `image_url`, `status`)
VALUES (
    @s_owner_a,
    13,
    'service',
    '[WVSU Demo] Calculus tutoring (1 hour)',
    'Second-year calculus — problem walkthrough and exam tips. Library or agreed quiet spot.',
    NULL,
    'active'
);
SET @lid := LAST_INSERT_ID();
INSERT INTO `services` (`listing_id`, `rate`, `rate_type`) VALUES (@lid, 250.00, 'per_hour');

INSERT INTO `listings` (`owner_id`, `category_id`, `listing_type`, `title`, `description`, `image_url`, `status`)
VALUES (
    @s_owner_b,
    15,
    'service',
    '[WVSU Demo] Poster / club event graphics',
    'Simple poster or IG square layout — you bring copy & logo if any.',
    NULL,
    'active'
);
SET @lid := LAST_INSERT_ID();
INSERT INTO `services` (`listing_id`, `rate`, `rate_type`) VALUES (@lid, 450.00, 'per_task');

INSERT INTO `listings` (`owner_id`, `category_id`, `listing_type`, `title`, `description`, `image_url`, `status`)
VALUES (
    @p_owner,
    17,
    'service',
    '[WVSU Demo] Small static webpage (landing section)',
    'Single-page HTML/CSS help for org or portfolio — scope agreed in chat.',
    NULL,
    'active'
);
SET @lid := LAST_INSERT_ID();
INSERT INTO `services` (`listing_id`, `rate`, `rate_type`) VALUES (@lid, 900.00, 'fixed');

-- -----------------------------------------------------------------------------
-- Verify (read-only)
-- -----------------------------------------------------------------------------

SELECT l.`listing_id`, l.`listing_type`, l.`title`, l.`owner_id`, u.`email`,
       p.`price` AS `product_price`, p.`stock`,
       s.`rate` AS `service_rate`, s.`rate_type`
FROM `listings` l
JOIN `users` u ON u.`user_id` = l.`owner_id`
LEFT JOIN `products` p ON p.`listing_id` = l.`listing_id`
LEFT JOIN `services` s ON s.`listing_id` = l.`listing_id`
WHERE l.`title` LIKE '[WVSU Demo]%'
ORDER BY l.`listing_type`, l.`listing_id`;

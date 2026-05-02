-- =============================================================================
-- WVSU CONNECT — test / demo user accounts (XAMPP & phpMyAdmin)
-- =============================================================================
--
-- Run AFTER `wvsudb.sql` (or your main schema) so `users` and `roles` exist.
-- Select database `wvsudb` (or uncomment USE below).
--
--   PLAIN-TEXT PASSWORD (same for every account in this file):
--
--        WvsuTest123!
--
--   (capital W, lowercase vsu, Test, digits 123, exclamation)
--
-- Emails (all @wvsu.edu.ph for the campus login flow):
--   seed.user01@wvsu.edu.ph  — student (buyer / general test)
--   seed.user02@wvsu.edu.ph  — student (buyer / second account)
--   seed.seller@wvsu.edu.ph  — student (listing seller tests)
--   seed.admin@wvsu.edu.ph  — admin dashboard (role_id = 1)
--
-- If the email already exists, the row is updated (password + active + name).
-- =============================================================================

-- USE wvsudb;

INSERT INTO `users` (`full_name`, `email`, `password`, `role_id`, `is_active`, `is_verified`)
VALUES (
    'Test Student One',
    'seed.user01@wvsu.edu.ph',
    '$2y$10$EOBvRRWKclV.JgaihL7QrObItDT4z.lcYioLNajANe4MgGA13LSEC',
    3,
    1,
    0
)
ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `full_name` = VALUES(`full_name`),
    `role_id` = VALUES(`role_id`),
    `is_active` = 1,
    `updated_at` = CURRENT_TIMESTAMP;

INSERT INTO `users` (`full_name`, `email`, `password`, `role_id`, `is_active`, `is_verified`)
VALUES (
    'Test Student Two',
    'seed.user02@wvsu.edu.ph',
    '$2y$10$fd2MorvjDat.8DfX5nv3SuuRmSf1R2YtrqtivHUBdSpoUWHtAs/6G',
    3,
    1,
    0
)
ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `full_name` = VALUES(`full_name`),
    `role_id` = VALUES(`role_id`),
    `is_active` = 1,
    `updated_at` = CURRENT_TIMESTAMP;

INSERT INTO `users` (`full_name`, `email`, `password`, `role_id`, `is_active`, `is_verified`)
VALUES (
    'Test Seller',
    'seed.seller@wvsu.edu.ph',
    '$2y$10$ESyMw9KnbjskOROLNClKc.5juB2oKFrSnLYcP.7goDHWVrtNcaE1W',
    3,
    1,
    0
)
ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `full_name` = VALUES(`full_name`),
    `role_id` = VALUES(`role_id`),
    `is_active` = 1,
    `updated_at` = CURRENT_TIMESTAMP;

INSERT INTO `users` (`full_name`, `email`, `password`, `role_id`, `is_active`, `is_verified`)
VALUES (
    'Test Admin',
    'seed.admin@wvsu.edu.ph',
    '$2y$10$5sRY.9UFhmuiK5jytdXVo.9UE4fKgcE/3PH2tvsu2xEtfP/hQXEKq',
    1,
    1,
    0
)
ON DUPLICATE KEY UPDATE
    `password` = VALUES(`password`),
    `full_name` = VALUES(`full_name`),
    `role_id` = VALUES(`role_id`),
    `is_active` = 1,
    `updated_at` = CURRENT_TIMESTAMP;

-- Quick verify (read-only)
SELECT `user_id`, `full_name`, `email`, `role_id`, `is_active`, LEFT(`password`, 7) AS `hash_prefix`
FROM `users`
WHERE `email` IN (
    'seed.user01@wvsu.edu.ph',
    'seed.user02@wvsu.edu.ph',
    'seed.seller@wvsu.edu.ph',
    'seed.admin@wvsu.edu.ph'
)
ORDER BY `user_id`;

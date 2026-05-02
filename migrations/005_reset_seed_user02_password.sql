-- =============================================================================
-- Reset password for seed.user02@wvsu.edu.ph
-- Run this in phpMyAdmin (SQL tab) or: mysql -u root -p wvsudb < this_file.sql
-- =============================================================================
--
--   PLAIN TEXT LOGIN PASSWORD (use this in the login form):
--
--        WvsuSeed02!
--
--   (capital W, lowercase vsu, Seed, zero two, exclamation)
--
-- =============================================================================

-- If the user already exists — updates password and forces account active:
UPDATE `users`
SET
    `password` = '$2y$10$gdgO3F/mM0c2d1SLEtMVAOtzjRLuuuuyCENcjmkQ/lGpmQUfLc9Ne',
    `is_active` = 1,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `email` = 'seed.user02@wvsu.edu.ph';

-- If the UPDATE touched 0 rows, this INSERT adds the account (MySQL skips insert if email exists):
INSERT INTO `users` (`full_name`, `email`, `password`, `role_id`, `is_active`, `is_verified`)
SELECT
    'Seed User 02',
    'seed.user02@wvsu.edu.ph',
    '$2y$10$gdgO3F/mM0c2d1SLEtMVAOtzjRLuuuuyCENcjmkQ/lGpmQUfLc9Ne',
    3,
    1,
    0
WHERE NOT EXISTS (
    SELECT 1 FROM `users` WHERE `email` = 'seed.user02@wvsu.edu.ph' LIMIT 1
);

-- Sanity check (you should see one row, is_active = 1):
SELECT `user_id`, `email`, `is_active`, LENGTH(`password`) AS `hash_length`, LEFT(`password`, 7) AS `hash_prefix`
FROM `users`
WHERE `email` = 'seed.user02@wvsu.edu.ph';

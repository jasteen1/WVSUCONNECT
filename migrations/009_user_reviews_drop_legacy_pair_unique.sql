-- Optional manual migration: remove legacy UNIQUE(reviewer_id, reviewee_id) so the same buyer
-- can submit sale feedback for different listings from the same seller.
-- The app also runs equivalent drops via profiles_reviews.inc.php (wvsu_user_reviews_drop_pair_unique_if_present).
--
-- If you see: "Can't DROP 'uq_reviewer_reviewee'; check that column/key exists" — the index is already gone; skip.

ALTER TABLE `user_reviews` DROP INDEX `uq_reviewer_reviewee`;

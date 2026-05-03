<?php
declare(strict_types=1);

/**
 * Extended profile fields on `users` and `user_reviews` for seller/freelancer feedback.
 */

function wvsu_mysql_column_exists(mysqli $conn, string $table, string $column): bool
{
    $sql = 'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res && $res->num_rows > 0;
}

function wvsu_mysql_index_exists(mysqli $conn, string $table, string $indexName): bool
{
    $sql = 'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (! $stmt) {
        return false;
    }
    $stmt->bind_param('ss', $table, $indexName);
    $stmt->execute();
    $res = $stmt->get_result();

    return $res && $res->num_rows > 0;
}

function wvsu_user_profiles_ensure_columns(mysqli $master): void
{
    $cols = [
        'bio' => 'TEXT NULL DEFAULT NULL',
        'social_instagram' => 'VARCHAR(500) NULL DEFAULT NULL',
        'social_facebook' => 'VARCHAR(500) NULL DEFAULT NULL',
        'social_x' => 'VARCHAR(500) NULL DEFAULT NULL',
        'social_tiktok' => 'VARCHAR(500) NULL DEFAULT NULL',
        'social_linkedin' => 'VARCHAR(500) NULL DEFAULT NULL',
        'social_website' => 'VARCHAR(500) NULL DEFAULT NULL',
    ];
    foreach ($cols as $name => $def) {
        if (!wvsu_mysql_column_exists($master, 'users', $name)) {
            $q = 'ALTER TABLE users ADD COLUMN `' . str_replace('`', '', $name) . '` ' . $def;
            if (!$master->query($q)) {
                @file_put_contents(
                    __DIR__ . '/db_conn_debug.log',
                    'profiles_ensure_col_fail ' . $name . ': ' . $master->error . "\n",
                    FILE_APPEND
                );
            }
        }
    }
}

/**
 * Allowed college / institute codes (WVSU). ILS is stored as ILS but shown as “Others (ILS)” on profiles.
 *
 * @return list<string>
 */
function wvsu_college_codes(): array
{
    return ['CICT', 'CBM', 'CAS', 'COE', 'COC', 'COD', 'COM', 'COL', 'CON', 'COP', 'ILS'];
}

function wvsu_sanitize_college_code(mixed $raw): ?string
{
    $v = strtoupper(trim((string) $raw));

    return in_array($v, wvsu_college_codes(), true) ? $v : null;
}

function wvsu_sanitize_year_level(mixed $raw): ?int
{
    $y = (int) $raw;

    return ($y >= 1 && $y <= 4) ? $y : null;
}

/** Profile label; ILS is grouped under “Others”. */
function wvsu_college_display(string $code): string
{
    $c = strtoupper(trim($code));
    if ($c === 'ILS') {
        return 'Others (ILS)';
    }

    return $c;
}

function wvsu_year_level_display(?int $y): string
{
    return match ($y) {
        1 => '1st year',
        2 => '2nd year',
        3 => '3rd year',
        4 => '4th year',
        default => '',
    };
}

/** Free-text program / course (e.g. BS Computer Science). Max 200 chars. */
function wvsu_sanitize_course_text(mixed $raw): string
{
    $s = trim((string) $raw);
    if ($s === '') {
        return '';
    }
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

    return mb_substr($s, 0, 200, 'UTF-8');
}

function wvsu_user_college_year_ensure_columns(mysqli $master): void
{
    if (! function_exists('wvsu_mysql_column_exists')) {
        return;
    }
    if (! wvsu_mysql_column_exists($master, 'users', 'college')) {
        $ok = $master->query(
            "ALTER TABLE users ADD COLUMN college VARCHAR(16) NULL DEFAULT NULL AFTER social_website"
        );
        if (! $ok) {
            $master->query("ALTER TABLE users ADD COLUMN college VARCHAR(16) NULL DEFAULT NULL");
        }
    }
    if (! wvsu_mysql_column_exists($master, 'users', 'year_level')) {
        $ok = $master->query(
            'ALTER TABLE users ADD COLUMN year_level TINYINT UNSIGNED NULL DEFAULT NULL AFTER college'
        );
        if (! $ok) {
            $master->query('ALTER TABLE users ADD COLUMN year_level TINYINT UNSIGNED NULL DEFAULT NULL');
        }
    }
    if (! wvsu_mysql_column_exists($master, 'users', 'course')) {
        $ok = $master->query(
            'ALTER TABLE users ADD COLUMN course VARCHAR(200) NULL DEFAULT NULL AFTER year_level'
        );
        if (! $ok) {
            $master->query('ALTER TABLE users ADD COLUMN course VARCHAR(200) NULL DEFAULT NULL');
        }
    }
}

function wvsu_user_reviews_ensure_table(mysqli $master): void
{
    $sql = "CREATE TABLE IF NOT EXISTS user_reviews (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$master->query($sql)) {
        $fallback = "CREATE TABLE IF NOT EXISTS user_reviews (
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
            KEY idx_reviews_listing (listing_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        if (!$master->query($fallback)) {
            @file_put_contents(
                __DIR__ . '/db_conn_debug.log',
                'user_reviews_create_fail: ' . $master->error . "\n",
                FILE_APPEND
            );
        }
    }
}

/**
 * Adds optional review photo column and drops (reviewer_id, reviewee_id) unique so each sale can add a row.
 */
function wvsu_user_reviews_ensure_photo_and_indexes(mysqli $master): void
{
    if (! function_exists('wvsu_mysql_column_exists')) {
        return;
    }
    if (! wvsu_mysql_column_exists($master, 'user_reviews', 'photo_url')) {
        $master->query('ALTER TABLE user_reviews ADD COLUMN photo_url VARCHAR(512) NULL DEFAULT NULL AFTER comment');
    }
    wvsu_user_reviews_drop_pair_unique_if_present($master);
}

/** Drops legacy unique (reviewer_id, reviewee_id) so multiple reviews per pair (e.g. per listing) can exist. */
function wvsu_user_reviews_drop_pair_unique_if_present(mysqli $master): void
{
    // Blind drop first: some hosts return empty SHOW INDEX until privileges warm up; this is a no-op if the index is already gone.
    @$master->query('ALTER TABLE user_reviews DROP INDEX uq_reviewer_reviewee');
    $dropped = false;
    $res = $master->query("SHOW INDEX FROM user_reviews WHERE Key_name = 'uq_reviewer_reviewee'");
    if ($res && $res->num_rows > 0) {
        $dropped = (bool) $master->query('ALTER TABLE user_reviews DROP INDEX uq_reviewer_reviewee');
    }
    if (! $dropped && function_exists('wvsu_mysql_index_exists') && wvsu_mysql_index_exists($master, 'user_reviews', 'uq_reviewer_reviewee')) {
        $master->query('ALTER TABLE user_reviews DROP INDEX uq_reviewer_reviewee');
    }
}

/** One public seller reply per review (marketplace-style). */
function wvsu_user_reviews_ensure_seller_reply_columns(mysqli $master): void
{
    if (! function_exists('wvsu_mysql_column_exists')) {
        return;
    }
    if (! wvsu_mysql_column_exists($master, 'user_reviews', 'seller_reply')) {
        $ok = $master->query(
            'ALTER TABLE user_reviews ADD COLUMN seller_reply TEXT NULL DEFAULT NULL AFTER photo_url'
        );
        if (! $ok) {
            $master->query('ALTER TABLE user_reviews ADD COLUMN seller_reply TEXT NULL DEFAULT NULL');
        }
    }
    if (! wvsu_mysql_column_exists($master, 'user_reviews', 'seller_replied_at')) {
        $ok = $master->query(
            'ALTER TABLE user_reviews ADD COLUMN seller_replied_at TIMESTAMP NULL DEFAULT NULL AFTER seller_reply'
        );
        if (! $ok) {
            $master->query('ALTER TABLE user_reviews ADD COLUMN seller_replied_at TIMESTAMP NULL DEFAULT NULL');
        }
    }
}

/** Normalize social / website input: http(s) only, max length. Returns null if empty/invalid. */
function wvsu_sanitize_profile_url(mixed $raw, int $maxLen = 500): ?string
{
    if ($raw !== null && ! is_scalar($raw)) {
        return null;
    }
    $s = trim((string) $raw);
    if ($s === '') {
        return null;
    }
    if (strlen($s) > $maxLen) {
        return null;
    }
    if (!preg_match('#^https?://#i', $s)) {
        $s = 'https://' . ltrim($s, '/');
    }
    $p = parse_url($s);
    if (!$p || empty($p['scheme']) || empty($p['host'])) {
        return null;
    }
    $scheme = strtolower((string) $p['scheme']);
    if ($scheme !== 'http' && $scheme !== 'https') {
        return null;
    }
    if (strlen($s) > $maxLen) {
        return null;
    }
    return $s;
}

/** Browser-safe relative path for uploads (avoids root-absolute URLs on subdirectory installs like /WVSUCONNECT/). */
function wvsu_profile_pic_href(?string $stored): string
{
    $s = trim((string) ($stored ?? ''));
    if ($s === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $s)) {
        return $s;
    }
    return ltrim($s, '/');
}

/**
 * Absolute web path from the server root for <img src> (fixes subfolders like /WVSUCONNECT/...).
 */
function wvsu_public_asset_web_path(string $relativeUploadPath): string
{
    $rel = ltrim(str_replace('\\', '/', $relativeUploadPath), '/');
    if ($rel === '') {
        return '';
    }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $base = dirname($script);
    $base = str_replace('\\', '/', $base);
    if ($base === '/' || $base === '.' || $base === '') {
        return '/' . $rel;
    }
    return $base . '/' . $rel;
}

/** Stored listing/portfolio paths (`uploads/…`) or absolute http(s) URLs for &lt;img&gt; / &lt;video src&gt;. */
function wvsu_listing_media_href(string $stored): string
{
    $s = trim(str_replace('\\', '/', $stored));
    if ($s === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $s)) {
        return $s;
    }

    return wvsu_public_asset_web_path($s);
}

/** Use in &lt;img src&gt; for a user avatar. Routed through avatar.php so images load under any app base URL. */
function wvsu_user_avatar_img_src(int $userId, ?string $updatedAt = null): string
{
    $url = 'avatar.php?id=' . $userId;
    if ($updatedAt !== null && $updatedAt !== '') {
        $t = strtotime((string) $updatedAt);
        if ($t !== false) {
            $url .= '&cb=' . $t;
        }
    }
    return $url;
}

/** @deprecated Prefer wvsu_user_avatar_img_src() when user_id is known */
function wvsu_profile_image_src(?string $stored): string
{
    $h = wvsu_profile_pic_href($stored);
    if ($h === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $h)) {
        return $h;
    }
    $web = wvsu_public_asset_web_path($h);
    $disk = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $h);
    if (is_file($disk)) {
        return $web . (str_contains($web, '?') ? '&' : '?') . 'v=' . (int) filemtime($disk);
    }

    return $web;
}

function wvsu_review_average_for_user(int $userId): array
{
    $row = fetch_master(
        'SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS review_count FROM user_reviews WHERE reviewee_id = ?',
        [(string) $userId]
    );
    $avg = $row && $row['avg_rating'] !== null ? (float) $row['avg_rating'] : 0.0;
    $cnt = $row ? (int) $row['review_count'] : 0;
    return ['avg' => $avg, 'count' => $cnt];
}

/** Star icons for a 1–5 rating (UI helper). */
function wvsu_render_stars(float $avg): string
{
    $r = (int) round(max(0, min(5, $avg)));
    $html = '<span class="wvsu-star-row" aria-label="Rating ' . htmlspecialchars((string) $avg) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $cls = $i <= $r ? 'bi bi-star-fill text-warning' : 'bi bi-star text-secondary opacity-25';
        $html .= '<i class="' . $cls . '"></i>';
    }
    $html .= '</span>';

    return $html;
}

/** Average rating and count for reviews tied to a specific listing (e.g. after a completed sale). */
function wvsu_listing_review_stats(int $listingId): array
{
    if ($listingId <= 0) {
        return ['avg' => 0.0, 'count' => 0];
    }
    $row = fetch_master(
        'SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS review_count FROM user_reviews WHERE listing_id = ?',
        [(string) $listingId]
    );
    $avg = $row && $row['avg_rating'] !== null ? (float) $row['avg_rating'] : 0.0;
    $cnt = $row ? (int) $row['review_count'] : 0;

    return ['avg' => $avg, 'count' => $cnt];
}

/** One line: "Product: … · Category: …" for purchase reviews (empty if no listing). */
function wvsu_review_purchase_context_label(?string $listingType, ?string $listingTitle, ?string $categoryName, int $listingId): string
{
    if ($listingId <= 0) {
        return '';
    }
    $type = strtolower(trim((string) $listingType));
    $kind = $type === 'service' ? 'Service' : ($type === 'product' ? 'Product' : 'Listing');
    $title = trim((string) $listingTitle);
    if ($title === '') {
        $title = 'Listing #' . $listingId;
    }
    $cat = trim((string) $categoryName);
    if ($cat === '') {
        $cat = 'Uncategorized';
    }

    return $kind . ': ' . $title . ' · Category: ' . $cat;
}

function wvsu_review_listing_public_url(int $listingId, ?string $listingType): string
{
    if ($listingId <= 0) {
        return '#';
    }
    $type = strtolower(trim((string) $listingType));

    return $type === 'service' ? ('view-service.php?id=' . $listingId) : ('view-product.php?id=' . $listingId);
}

/**
 * @return list<array<string,mixed>>
 */
function wvsu_listing_reviews_for_listing(int $listingId): array
{
    if ($listingId <= 0) {
        return [];
    }

    return fetchAll_master(
        'SELECT r.rating, r.comment, r.created_at, r.photo_url, r.seller_reply, r.seller_replied_at,
                COALESCE(NULLIF(TRIM(u.full_name), \'\'), CONCAT(\'Member #\', r.reviewer_id)) AS reviewer_name,
                r.reviewer_id,
                l.title AS review_listing_title,
                l.listing_type AS review_listing_type,
                c.name AS review_category_name
         FROM user_reviews r
         LEFT JOIN users u ON u.user_id = r.reviewer_id
         LEFT JOIN listings l ON l.listing_id = r.listing_id
         LEFT JOIN categories c ON c.category_id = l.category_id
         WHERE r.listing_id = ?
         ORDER BY r.created_at DESC',
        [(string) $listingId]
    );
}

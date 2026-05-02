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

/** Normalize social / website input: http(s) only, max length. Returns null if empty/invalid. */
function wvsu_sanitize_profile_url(?string $raw, int $maxLen = 500): ?string
{
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
    $row = fetch(
        'SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS review_count FROM user_reviews WHERE reviewee_id = ?',
        [(string) $userId]
    );
    $avg = $row && $row['avg_rating'] !== null ? (float) $row['avg_rating'] : 0.0;
    $cnt = $row ? (int) $row['review_count'] : 0;
    return ['avg' => $avg, 'count' => $cnt];
}

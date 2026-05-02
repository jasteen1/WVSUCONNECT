<?php
/**
 * Service listing portfolio: multiple images/videos with order and grid span.
 */
declare(strict_types=1);

function wvsu_service_portfolio_ensure_table(mysqli $master): void
{
    $sql = "CREATE TABLE IF NOT EXISTS service_portfolio_items (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$master->query($sql)) {
        $err = $master->error;
        $fallback = "CREATE TABLE IF NOT EXISTS service_portfolio_items (
            portfolio_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            listing_id INT UNSIGNED NOT NULL,
            media_type ENUM('image','video') NOT NULL DEFAULT 'image',
            file_path VARCHAR(500) NOT NULL,
            grid_span TINYINT UNSIGNED NOT NULL DEFAULT 1,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (portfolio_id),
            KEY idx_portfolio_listing (listing_id),
            KEY idx_portfolio_sort (listing_id, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        if (!$master->query($fallback)) {
            die('Could not create service_portfolio_items table: ' . htmlspecialchars($err . ' | ' . $master->error));
        }
    }
}

function wvsu_portfolio_media_dir(): string
{
    return __DIR__ . '/uploads/services/portfolio';
}

/**
 * @return 'image'|'video'|false
 */
function wvsu_classify_upload_media(string $tmp, string $originalName)
{
    if (!is_readable($tmp)) {
        return false;
    }
    $mime = '';
    if (class_exists('finfo')) {
        $f = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) @$f->file($tmp);
    }
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $imgExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $vidExt = ['mp4', 'webm', 'mov'];
    $imgMime = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    ];
    $vidMime = [
        'video/mp4', 'video/webm', 'video/quicktime',
    ];

    if (in_array($mime, $vidMime, true) || in_array($ext, $vidExt, true)) {
        return 'video';
    }
    if (in_array($mime, $imgMime, true) || in_array($ext, $imgExt, true)) {
        return 'image';
    }
    return false;
}

/**
 * @param array $spans indexed 0..n-1, values 1 or 2
 * @return array{0: string|null, 1: int} first is relative path for listings.image_url if suitable
 */
function wvsu_save_portfolio_uploads(
    mysqli $master,
    int $listing_id,
    array $filesStruct,
    array $spans,
    int $sortBase = 0
): array {
    $firstImagePath = null;
    $saved = 0;
    if (empty($filesStruct['name']) || !is_array($filesStruct['name'])) {
        return [$firstImagePath, 0];
    }

    $targetDir = wvsu_portfolio_media_dir();
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $n = count($filesStruct['name']);
    for ($i = 0; $i < $n; $i++) {
        if (($filesStruct['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $tmp = $filesStruct['tmp_name'][$i];
        $orig = (string) ($filesStruct['name'][$i] ?? 'file');
        $kind = wvsu_classify_upload_media($tmp, $orig);
        if ($kind === false) {
            continue;
        }
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if ($kind === 'video' && !in_array($ext, ['mp4', 'webm', 'mov'], true)) {
            $ext = 'mp4';
        }
        if ($kind === 'image' && !in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $ext = 'jpg';
        }
        $safe = 'pf_' . $listing_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $targetDir . '/' . $safe;
        if (!move_uploaded_file($tmp, $dest)) {
            continue;
        }
        $rel = 'uploads/services/portfolio/' . $safe;
        $span = isset($spans[$i]) ? max(1, min(2, (int) $spans[$i])) : 1;
        $sort = $sortBase + $saved;

        $mt = $kind;
        $stmt = $master->prepare(
            'INSERT INTO service_portfolio_items (listing_id, media_type, file_path, grid_span, sort_order) VALUES (?, ?, ?, ?, ?)'
        );
        if ($stmt) {
            $stmt->bind_param('issii', $listing_id, $mt, $rel, $span, $sort);
            $stmt->execute();
            $stmt->close();
        }
        $saved++;
        if ($firstImagePath === null && $kind === 'image') {
            $firstImagePath = $rel;
        }
    }

    return [$firstImagePath, $saved];
}

function wvsu_map_service_unit_to_rate_type(string $unit): string
{
    switch (strtolower($unit)) {
        case 'hour':
            return 'per_hour';
        case 'project':
            return 'per_task';
        case 'session':
            return 'fixed';
        default:
            return 'fixed';
    }
}

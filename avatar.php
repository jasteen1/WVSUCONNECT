<?php
declare(strict_types=1);

/**
 * Serves profile photos from disk using a predictable URL (fixes subdirectories, mod_rewrite,
 * and direct /uploads/... access issues).
 */
require_once __DIR__ . '/db_conn.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

$row = fetch_master(
    'SELECT full_name, profile_pic_url FROM users WHERE user_id = ? LIMIT 1',
    [(string) $id]
);

$placeholder = static function (string $nameEncoded): never {
    $url = 'https://ui-avatars.com/api/?name=' . rawurlencode($nameEncoded)
        . '&background=0d4daa&color=fff&size=256';
    header('Location: ' . $url, true, 302);
    exit;
};

if (! $row) {
    $placeholder('User');
}

$pic = trim((string) ($row['profile_pic_url'] ?? ''));
$displayName = (string) ($row['full_name'] ?? 'User');

if ($pic !== '' && preg_match('#^https?://#i', $pic)) {
    header('Location: ' . $pic, true, 302);
    exit;
}

if ($pic !== '') {
    $normalized = str_replace('\\', '/', $pic);
    $normalized = ltrim($normalized, '/');
    if (
        strpos($normalized, '..') !== false
        || ! preg_match('#^uploads/profiles/[A-Za-z0-9._-]+$#', $normalized)
    ) {
        $normalized = '';
    }
    if ($normalized !== '') {
        $separator = DIRECTORY_SEPARATOR;
        $full = realpath(__DIR__ . $separator . str_replace('/', $separator, $normalized));
        $profilesDir = realpath(__DIR__ . $separator . 'uploads' . $separator . 'profiles');
        if (
            $full !== false && $profilesDir !== false && str_starts_with($full, $profilesDir)
            && is_file($full)
            && is_readable($full)
        ) {
            $mime = @mime_content_type($full);
            if (! is_string($mime)) {
                $mime = 'application/octet-stream';
            }
            $ext = strtolower((string) pathinfo($full, PATHINFO_EXTENSION));
            if ($mime === 'application/octet-stream' || $mime === '') {
                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    'gif' => 'image/gif',
                    default => 'image/jpeg',
                };
            }
            header('Content-Type: ' . $mime);
            header('Cache-Control: private, max-age=300');
            readfile($full);
            exit;
        }
    }
}

$placeholder($displayName !== '' ? $displayName : 'User');

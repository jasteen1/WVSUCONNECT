<?php
declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Forbidden');
}

$mid = (int) ($_GET['m'] ?? 0);
if ($mid <= 0) {
    http_response_code(404);
    exit;
}

$row = fetch_master(
    'SELECT m.message_type, m.image_url, c.participant_a, c.participant_b
     FROM messages m
     JOIN conversations c ON c.conversation_id = m.conversation_id
     WHERE m.message_id = ?
     LIMIT 1',
    [(string) $mid]
);

if (! $row || ($row['message_type'] ?? '') !== 'image') {
    http_response_code(404);
    exit;
}

$me = (int) $_SESSION['user_id'];
$pa = (int) ($row['participant_a'] ?? 0);
$pb = (int) ($row['participant_b'] ?? 0);
if ($me !== $pa && $me !== $pb) {
    http_response_code(403);
    exit;
}

$rel = ltrim(str_replace('\\', '/', trim((string) ($row['image_url'] ?? ''))), '/');
if (
    $rel === ''
    || str_contains($rel, '..')
    || ! preg_match('#^uploads/messages/[A-Za-z0-9._-]+$#', $rel)
) {
    http_response_code(404);
    exit;
}

$sep = DIRECTORY_SEPARATOR;
$full = realpath(__DIR__ . $sep . str_replace('/', $sep, $rel));
$base = realpath(__DIR__ . $sep . 'uploads' . $sep . 'messages');
if (
    $full === false
    || $base === false
    || ! str_starts_with($full, $base)
    || ! is_readable($full)
) {
    http_response_code(404);
    exit;
}

$mime = @mime_content_type($full);
if (! is_string($mime) || $mime === '') {
    $mime = 'application/octet-stream';
}
$ext = strtolower((string) pathinfo($full, PATHINFO_EXTENSION));
if ($mime === 'application/octet-stream') {
    $mime = match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        default => $mime,
    };
}

header('Content-Type: ' . $mime);
header('Cache-Control: private, max-age=600');
readfile($full);
exit;

<?php
declare(strict_types=1);

/**
 * Validates a relative in-app redirect after login (?next=… or POST redirect_after).
 * Returns '' if invalid (caller should fall back to index.php).
 */
function wvsu_login_redirect_destination(string $raw): string
{
    static $allowed = [
        'addproduct.php',
        'addservice.php',
        'your_listings.php',
        'messages.php',
        'edit_profile.php',
        'profile.php',
    ];
    $raw = trim(str_replace('\\', '/', $raw));
    if ($raw === '') {
        return '';
    }
    if (str_contains($raw, '..')) {
        return '';
    }

    $p = parse_url($raw);
    if (($p['scheme'] ?? '') !== '' || ($p['host'] ?? '') !== '') {
        return '';
    }

    $path = $p['path'] ?? '';
    if ($path === '') {
        return '';
    }
    $base = strtolower(basename($path));

    if (! in_array($base, $allowed, true)) {
        return '';
    }

    if ($base === 'profile.php') {
        parse_str((string) ($p['query'] ?? ''), $q);
        $id = (int) ($q['id'] ?? 0);
        if ($id <= 0) {
            return '';
        }
        return 'profile.php?id=' . $id;
    }

    return $base;
}

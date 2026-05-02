<?php

declare(strict_types=1);

/**
 * Public-path URL for splash logo (never point at empty / broken whitelogo.png).
 */
function wvsu_splash_logo_href(): string
{
    static $memo = null;
    if ($memo !== null) {
        return $memo;
    }
    $path = __DIR__ . '/assets/whitelogo.png';
    // PNG header alone is dozens of bytes; empty files resolve to fallback.
    if (is_readable($path) && (int) @filesize($path) > 96) {
        $memo = 'assets/whitelogo.png';

        return $memo;
    }
    $memo = 'assets/wvsuconnectlogo.png';

    return $memo;
}

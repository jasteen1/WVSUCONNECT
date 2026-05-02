<?php

declare(strict_types=1);

/** Shared branding: include inside <head> after Bootstrap CSS. */

require_once __DIR__ . '/wvsu_splash_assets.inc.php';

// Bust caches whenever theme/JS changes (fixes “updates not showing”).
$themePath = __DIR__ . '/css/wvsu-connect-theme.css';
$entryLoaderCssPath = __DIR__ . '/css/wvsu-entry-loader.css';
$motionPath = __DIR__ . '/js/wvsu-motion.js';
$entryLoaderPath = __DIR__ . '/js/wvsu-entry-loader.js';
$themeVer = is_readable($themePath) ? (string) filemtime($themePath) : '0';
$entryLoaderCssVer = is_readable($entryLoaderCssPath) ? (string) filemtime($entryLoaderCssPath) : '0';
$motionVer = is_readable($motionPath) ? (string) filemtime($motionPath) : '0';
$entryLoaderVer = is_readable($entryLoaderPath) ? (string) filemtime($entryLoaderPath) : '0';

?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="image" href="<?= htmlspecialchars(wvsu_splash_logo_href(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="css/wvsu-connect-theme.css?v=<?= htmlspecialchars($themeVer, ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="css/wvsu-entry-loader.css?v=<?= htmlspecialchars($entryLoaderCssVer, ENT_QUOTES, 'UTF-8') ?>">
<link rel="icon" type="image/png" href="assets/wvsuconnectlogo.png">
<link rel="apple-touch-icon" href="assets/wvsuconnectlogo.png">
<script src="js/wvsu-entry-loader.js?v=<?= htmlspecialchars($entryLoaderVer, ENT_QUOTES, 'UTF-8') ?>" defer></script>
<script src="js/wvsu-motion.js?v=<?= htmlspecialchars($motionVer, ENT_QUOTES, 'UTF-8') ?>" defer></script>

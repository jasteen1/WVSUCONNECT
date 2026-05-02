<?php
/** Shared branding: include inside <head> after Bootstrap CSS. */

// Bust caches whenever theme/JS changes (fixes “updates not showing”).
$themePath = __DIR__ . '/css/wvsu-connect-theme.css';
$motionPath = __DIR__ . '/js/wvsu-motion.js';
$themeVer = is_readable($themePath) ? (string) filemtime($themePath) : '0';
$motionVer = is_readable($motionPath) ? (string) filemtime($motionPath) : '0';

?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="css/wvsu-connect-theme.css?v=<?= htmlspecialchars($themeVer, ENT_QUOTES, 'UTF-8') ?>">
<link rel="icon" type="image/png" href="assets/wvsuconnectlogo.png">
<link rel="apple-touch-icon" href="assets/wvsuconnectlogo.png">
<script src="js/wvsu-motion.js?v=<?= htmlspecialchars($motionVer, ENT_QUOTES, 'UTF-8') ?>" defer></script>

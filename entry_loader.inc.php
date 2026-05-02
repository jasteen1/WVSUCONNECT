<?php

declare(strict_types=1);

require_once __DIR__ . '/wvsu_splash_assets.inc.php';

$wvsuSplashLogo = wvsu_splash_logo_href();

?>
<div id="wvsu-entry-splash" class="wvsu-entry-splash" role="status" aria-live="polite" aria-busy="true" aria-label="Loading">
    <div class="wvsu-entry-splash__glow" aria-hidden="true"></div>
    <div class="wvsu-entry-splash__vignette" aria-hidden="true"></div>
    <div class="wvsu-entry-splash__noise" aria-hidden="true"></div>
    <div class="wvsu-entry-splash__scanlines" aria-hidden="true"></div>
    <div class="wvsu-entry-splash__rings" aria-hidden="true">
        <span class="wvsu-entry-splash__ring"></span>
        <span class="wvsu-entry-splash__ring"></span>
        <span class="wvsu-entry-splash__ring"></span>
    </div>
    <div class="wvsu-entry-splash__core">
        <div class="wvsu-entry-splash__beam" aria-hidden="true"></div>
        <div class="wvsu-entry-splash__logo-shell">
            <img src="<?= htmlspecialchars($wvsuSplashLogo, ENT_QUOTES, 'UTF-8') ?>" alt="" class="wvsu-entry-splash__logo" width="280" height="280" decoding="async" fetchpriority="high"/>
        </div>
        <div class="wvsu-entry-splash__bar" aria-hidden="true"><span class="wvsu-entry-splash__bar-fill"></span></div>
    </div>
</div>

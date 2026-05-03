<?php

declare(strict_types=1);

/**
 * Validates ?return= on listing detail pages (open-redirect safe: relative .php only).
 *
 * Examples: profile.php?id=5, messages.php?conv=2, products.php
 */
function wvsu_safe_listing_return_url(?string $raw): string
{
    if ($raw === null || $raw === '') {
        return '';
    }
    $raw = trim((string) $raw);
    if (preg_match(
        '#^(?!/)(?!\\\\)(?!https?://)(?!//)[a-z0-9_.-]+\.php(?:\?[a-zA-Z0-9_=&%.-]*)?$#i',
        $raw
    ) !== 1) {
        return '';
    }

    return $raw;
}

/** Adds ?return= or &return= to view-product / view-service URLs. */
function wvsu_append_listing_return(string $listingViewUrl, string $returnPath): string
{
    $returnPath = trim($returnPath);
    if ($returnPath === '') {
        return $listingViewUrl;
    }
    $enc = rawurlencode($returnPath);

    return $listingViewUrl . (str_contains($listingViewUrl, '?') ? '&' : '?') . 'return=' . $enc;
}

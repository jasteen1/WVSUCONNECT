<?php
declare(strict_types=1);

/**
 * Optional freelancer-friendly service price list support.
 */
function wvsu_service_pricing_ensure_table(mysqli $master): void
{
    $sql = "CREATE TABLE IF NOT EXISTS service_pricing_items (
        price_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        listing_id INT UNSIGNED NOT NULL,
        item_name VARCHAR(150) NOT NULL,
        amount DECIMAL(10,2) DEFAULT NULL,
        sort_order INT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (price_item_id),
        KEY idx_spi_listing (listing_id),
        KEY idx_spi_sort (listing_id, sort_order),
        CONSTRAINT fk_spi_listing FOREIGN KEY (listing_id)
            REFERENCES listings (listing_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$master->query($sql)) {
        $fallback = "CREATE TABLE IF NOT EXISTS service_pricing_items (
            price_item_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            listing_id INT UNSIGNED NOT NULL,
            item_name VARCHAR(150) NOT NULL,
            amount DECIMAL(10,2) DEFAULT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (price_item_id),
            KEY idx_spi_listing (listing_id),
            KEY idx_spi_sort (listing_id, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        if (!$master->query($fallback)) {
            die("Could not create service_pricing_items table: " . $master->error);
        }
    }
}

function wvsu_service_mode_to_rate_type(string $mode): string
{
    switch (strtolower($mode)) {
        case 'per_hour':
        case 'hour':
            return 'per_hour';
        case 'per_output':
        case 'project':
        case 'per_task':
            return 'per_task';
        case 'session':
        case 'per_session':
        case 'package':
            return 'fixed';
        case 'negotiable':
            return 'negotiable';
        default:
            return 'fixed';
    }
}

/** Parse user-entered money (strips thousands separators; empty → 0). */
function wvsu_parse_money_string(string $raw): float
{
    $t = trim($raw);
    if ($t === '') {
        return 0.0;
    }
    $nbsp = pack('C*', 0xc2, 0xa0);
    $t = str_replace([',', ' ', $nbsp], '', $t);
    if ($t === '' || ! is_numeric($t)) {
        return 0.0;
    }

    return (float) $t;
}

/**
 * @return array<int, array{name:string,amount:float|null}>
 */
function wvsu_collect_price_items(array $names, array $amounts): array
{
    $items = [];
    $n = max(count($names), count($amounts));
    for ($i = 0; $i < $n; $i++) {
        $name = trim((string) ($names[$i] ?? ''));
        $rawAmount = trim((string) ($amounts[$i] ?? ''));
        if ($name === '') {
            continue;
        }
        $amount = null;
        if ($rawAmount !== '') {
            $parsed = wvsu_parse_money_string($rawAmount);
            if ($parsed > 0) {
                $amount = $parsed;
            }
        }
        $items[] = ['name' => $name, 'amount' => $amount];
    }
    return $items;
}

function wvsu_save_price_items(mysqli $master, int $listing_id, array $items): void
{
    $master->query('DELETE FROM service_pricing_items WHERE listing_id=' . intval($listing_id));
    if (empty($items)) {
        return;
    }
    $stmtWithAmount = $master->prepare(
        'INSERT INTO service_pricing_items (listing_id, item_name, amount, sort_order) VALUES (?, ?, ?, ?)'
    );
    $stmtNoAmount = $master->prepare(
        'INSERT INTO service_pricing_items (listing_id, item_name, amount, sort_order) VALUES (?, ?, NULL, ?)'
    );
    if (!$stmtWithAmount || !$stmtNoAmount) {
        return;
    }
    foreach ($items as $i => $it) {
        $name = (string) ($it['name'] ?? '');
        if ($name === '') {
            continue;
        }
        $amount = $it['amount'];
        $sort = (int) $i;
        if ($amount === null) {
            $stmtNoAmount->bind_param('isi', $listing_id, $name, $sort);
            $stmtNoAmount->execute();
        } else {
            $val = (float) $amount;
            $stmtWithAmount->bind_param('isdi', $listing_id, $name, $val, $sort);
            $stmtWithAmount->execute();
        }
    }
    $stmtWithAmount->close();
    $stmtNoAmount->close();
}

function wvsu_min_price_from_items(array $items): ?float
{
    $min = null;
    foreach ($items as $it) {
        if (!array_key_exists('amount', $it) || $it['amount'] === null) {
            continue;
        }
        $v = (float) $it['amount'];
        if ($min === null || $v < $min) {
            $min = $v;
        }
    }
    return $min;
}

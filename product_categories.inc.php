<?php

declare(strict_types=1);

/**
 * Extra marketplace product categories — idempotent (by name + type).
 */
function wvsu_ensure_extended_product_categories(): void
{
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;

    $extras = [
        ['Uniforms', 'product'],
        ['School Supplies', 'product'],
        ['Fashion & Shoes', 'product'],
        ['Health & Toiletries', 'product'],
        ['Sports & Outdoors', 'product'],
        ['Arts & Crafts', 'product'],
        ['Music & Instruments', 'product'],
        ['Room Essentials', 'product'],
        ['Lab Supplies', 'product'],
        ['Stationery & Tools', 'product'],
        ['Cosmetics', 'product'],
        ['Bike & Mobility', 'product'],
        ['Snacks & Pantry', 'product'],
        ['Collectibles', 'product'],
        ['Board Games', 'product'],
        ['Formal Wear', 'product'],
        ['Printing & Copies', 'product'],
    ];

    foreach ($extras as [$name, $ctype]) {
        $exist = fetch_master(
            'SELECT category_id FROM categories WHERE name = ? AND category_type IN (?, \'both\') LIMIT 1',
            [$name, $ctype]
        );
        if ($exist) {
            continue;
        }
        insert('INSERT INTO categories (name, category_type, parent_type) VALUES (?, ?, NULL)', [$name, $ctype]);
    }
}

/**
 * @return list<array<string,mixed>>
 */
function wvsu_product_category_dropdown_rows(): array
{
    return fetchAll_master(
        'SELECT c.category_id, c.name, c.parent_type, p.name AS parent_name,
                CASE WHEN LOWER(TRIM(c.name)) IN (\'other\',\'others\') THEN 1 ELSE 0 END AS is_others
         FROM categories c
         LEFT JOIN categories p ON p.category_id = c.parent_type
         WHERE c.category_type IN (\'product\',\'both\')
         ORDER BY
            CASE WHEN c.parent_type IS NULL THEN 0 ELSE 1 END,
            COALESCE(p.name, c.name),
            c.name',
        []
    );
}

/** Category IDs marked as Other/Others — require optional description handling in forms. */
function wvsu_product_category_other_ids(array $dropdownRows): array
{
    $ids = [];
    foreach ($dropdownRows as $r) {
        if (((int) ($r['is_others'] ?? 0)) === 1) {
            $ids[] = (int) ($r['category_id'] ?? 0);
        }
    }

    return array_values(array_filter(array_unique($ids)));
}

<?php
require_once 'db_conn.php';
header('Content-Type: application/json');
$id = 7;
$row = fetch("SELECT l.*, p.price, p.stock, s.rate, s.rate_type FROM listings l LEFT JOIN products p ON p.listing_id = l.listing_id LEFT JOIN services s ON s.listing_id = l.listing_id WHERE l.listing_id = ? LIMIT 1", [$id]);
$cats = fetchAll("SELECT category_id, name FROM categories ORDER BY name");
echo json_encode(['listing' => $row, 'categories' => $cats], JSON_PRETTY_PRINT);

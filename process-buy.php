<?php
require_once 'db_conn.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

$me = intval($_SESSION['user_id']);
$listing_id = intval($_POST['listing_id'] ?? 0);
$qty = intval($_POST['quantity'] ?? 1);
if ($listing_id <= 0 || $qty <= 0) die('Invalid request');

// Fetch listing and product
$item = fetch("SELECT l.*, p.price, p.stock FROM listings l JOIN products p ON p.listing_id = l.listing_id WHERE l.listing_id = ? LIMIT 1", [$listing_id]);
if (!$item) die('Listing not found');

if (intval($item['owner_id']) === $me) die('Cannot buy your own listing');

$stock = intval($item['stock']);
if ($stock < $qty) die('Not enough stock');

$total = floatval($item['price']) * $qty;

global $master_conn;
$master_conn->begin_transaction();
// 1) create transaction
$txn_id = insert("INSERT INTO transactions (buyer_id, listing_id, transaction_type, quantity, total_price) VALUES (?, ?, 'product', ?, ?)", [$me, $listing_id, $qty, (string)$total]);

// 2) create product_orders (minimal)
$order_id = insert("INSERT INTO product_orders (transaction_id, delivery_address) VALUES (?, ?)", [$txn_id, 'TBD']);

// 3) decrement stock
insert("UPDATE products SET stock = stock - ? WHERE listing_id = ?", [$qty, $listing_id]);

// 4) mark listing sold_out if stock now 0
$newStockRow = fetch("SELECT stock FROM products WHERE listing_id = ? LIMIT 1", [$listing_id]);
if ($newStockRow && intval($newStockRow['stock']) <= 0) {
    insert("UPDATE listings SET status = 'sold_out' WHERE listing_id = ?", [$listing_id]);
}

$master_conn->commit();

header('Location: view-product.php?id=' . $listing_id . '&success=Purchase+created');
exit;

?>

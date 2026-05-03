<?php
require_once 'db_conn.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = intval($_SESSION['user_id']);
$listing_id = intval($_POST['listing_id'] ?? 0);
$new_stock = intval($_POST['stock'] ?? 0);
if ($listing_id <= 0) { header('Location: your_listings.php'); exit; }

// verify ownership
$owner = fetch_master('SELECT owner_id FROM listings WHERE listing_id = ?', [(string) $listing_id]);
if (!$owner || intval($owner['owner_id']) !== $uid) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

// update stock
$stmt = $master_conn->prepare("UPDATE products SET stock = ? WHERE listing_id = ?");
$stmt->bind_param('ii', $new_stock, $listing_id);
$stmt->execute();

// optionally update listing status
$status = $new_stock <= 0 ? 'sold_out' : 'active';
$stmt2 = $master_conn->prepare("UPDATE listings SET status = ? WHERE listing_id = ?");
$stmt2->bind_param('si', $status, $listing_id);
$stmt2->execute();

header('Location: your_listings.php');
exit;
?>

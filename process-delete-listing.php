<?php
require_once 'db_conn.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = intval($_SESSION['user_id']);
$listing_id = intval($_POST['listing_id'] ?? 0);
if ($listing_id <= 0) { header('Location: your_listings.php'); exit; }

// verify ownership
$owner = fetch_master('SELECT owner_id, listing_type FROM listings WHERE listing_id = ?', [(string) $listing_id]);
if (!$owner || intval($owner['owner_id']) !== $uid) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

// soft-delete: mark inactive
$stmt = $master_conn->prepare("UPDATE listings SET status = 'inactive' WHERE listing_id = ?");
$stmt->bind_param('i', $listing_id);
$stmt->execute();

// if product, set stock to 0
if ($owner['listing_type'] === 'product') {
    $stmt2 = $master_conn->prepare("UPDATE products SET stock = 0 WHERE listing_id = ?");
    $stmt2->bind_param('i', $listing_id);
    $stmt2->execute();
}

header('Location: your_listings.php');
exit;
?>

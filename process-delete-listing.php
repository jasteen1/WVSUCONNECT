<?php
require_once 'db_conn.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$uid = (int) $_SESSION['user_id'];
$listing_id = (int) ($_POST['listing_id'] ?? 0);
if ($listing_id <= 0) {
    header('Location: your_listings.php');
    exit;
}

$owner = fetch_master(
    'SELECT owner_id, listing_type FROM listings WHERE listing_id = ? LIMIT 1',
    [(string) $listing_id]
);
if (! $owner || (int) ($owner['owner_id'] ?? 0) !== $uid) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit;
}

// Soft-delete: inactive. Must succeed (see trg_listing_status_change_log — item_status / audit_logs need AUTO_INCREMENT).
$stmt = $master_conn->prepare(
    'UPDATE listings SET status = \'inactive\' WHERE listing_id = ? AND owner_id = ? AND status IN (\'active\', \'sold_out\')'
);
if (! $stmt) {
    header('Location: your_listings.php?error=delete_failed');
    exit;
}
$stmt->bind_param('ii', $listing_id, $uid);
$ok = $stmt->execute();
$changed = $ok ? $stmt->affected_rows : 0;
$stmt->close();

if (! $ok || $changed < 1) {
    header('Location: your_listings.php?error=delete_failed');
    exit;
}

if (($owner['listing_type'] ?? '') === 'product') {
    $stmt2 = $master_conn->prepare('UPDATE products SET stock = 0 WHERE listing_id = ?');
    if ($stmt2) {
        $stmt2->bind_param('i', $listing_id);
        $stmt2->execute();
        $stmt2->close();
    }
}

header('Location: your_listings.php?removed=1');
exit;

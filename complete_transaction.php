<?php
require_once 'db_conn.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

$me = intval($_SESSION['user_id']);
$conv = intval($_POST['conv_id'] ?? 0);
$qty = max(1, intval($_POST['quantity'] ?? 1));
if ($conv <= 0) die('Invalid conversation');

// Find mapped listing
$map = fetch("SELECT listing_id FROM conversation_listings WHERE conversation_id = ? LIMIT 1", [$conv]);
if (!$map) die('No listing attached to this conversation');
$listing_id = intval($map['listing_id']);

// Fetch listing and product
$item = fetch("SELECT l.*, p.price, p.stock FROM listings l JOIN products p ON p.listing_id = l.listing_id WHERE l.listing_id = ? LIMIT 1", [$listing_id]);
if (!$item) die('Listing not found');

// Verify current user is owner of listing
if (intval($item['owner_id']) !== $me) die('Only the seller can complete the transaction');

// Identify buyer (other participant)
$c = fetch("SELECT participant_a, participant_b FROM conversations WHERE conversation_id = ? LIMIT 1", [$conv]);
if (!$c) die('Conversation not found');
$other = ($c['participant_a'] == $me) ? $c['participant_b'] : $c['participant_a'];
$buyer_id = intval($other);

if ($buyer_id <= 0) die('Buyer not found');

if (intval($item['stock']) < $qty) die('Not enough stock');

$total = floatval($item['price']) * $qty;

global $master_conn;
$master_conn->begin_transaction();
try {
    // decrement stock by seller-provided quantity
    insert("UPDATE products SET stock = stock - ? WHERE listing_id = ?", [$qty, $listing_id]);

    // mark listing sold_out if stock now 0
    $newStockRow = fetch("SELECT stock FROM products WHERE listing_id = ? LIMIT 1", [$listing_id]);
    if ($newStockRow && intval($newStockRow['stock']) <= 0) {
        insert("UPDATE listings SET status = 'sold_out' WHERE listing_id = ?", [$listing_id]);
    }

    // ensure conversation_meta exists and mark closed
    $create_meta = "CREATE TABLE IF NOT EXISTS conversation_meta (
            conversation_id INT UNSIGNED NOT NULL PRIMARY KEY,
            is_closed TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $master_conn->query($create_meta);
    $exists = fetch("SELECT conversation_id FROM conversation_meta WHERE conversation_id = ? LIMIT 1", [$conv]);
    if ($exists) {
        insert("UPDATE conversation_meta SET is_closed = 1 WHERE conversation_id = ?", [$conv]);
    } else {
        insert("INSERT INTO conversation_meta (conversation_id, is_closed) VALUES (?, 1)", [$conv]);
    }

    // insert message announcing completion
    $content = "Seller completed transaction. Quantity deducted: {$qty}.";
    insert("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)", [$conv, $me, $content]);
    insert("UPDATE conversations SET last_message_at = current_timestamp() WHERE conversation_id = ?", [$conv]);

    $master_conn->commit();
} catch (Exception $e) {
    $master_conn->rollback();
    die('Failed to complete transaction: ' . $e->getMessage());
}

header('Location: messages.php?conv=' . $conv . '&success=transaction_completed');
exit;

?>

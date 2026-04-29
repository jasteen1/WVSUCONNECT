<?php
require_once 'db_conn.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$me = intval($_SESSION['user_id']);
$txn_id = intval($_POST['transaction_id'] ?? 0);
if ($txn_id <= 0) die('Invalid transaction');

// Load transaction
$txn = fetch("SELECT * FROM transactions WHERE transaction_id = ? LIMIT 1", [$txn_id]);
if (!$txn) die('Transaction not found');
if ($txn['status'] !== 'pending') die('Transaction not pending');

$listing_id = intval($txn['listing_id']);

// Verify current user is owner of listing
$listing = fetch("SELECT owner_id FROM listings WHERE listing_id = ? LIMIT 1", [$listing_id]);
if (!$listing) die('Listing not found');
if (intval($listing['owner_id']) !== $me) die('Only seller can confirm purchase');

$qty = intval($txn['quantity']);

// Check stock
$prod = fetch("SELECT stock FROM products WHERE listing_id = ? LIMIT 1", [$listing_id]);
if (!$prod) die('Product not found');
if (intval($prod['stock']) < $qty) die('Not enough stock to confirm');

global $master_conn;
$master_conn->begin_transaction();
try {
    // decrement stock
    insert("UPDATE products SET stock = stock - ? WHERE listing_id = ?", [$qty, $listing_id]);

    // mark listing sold_out if needed
    $new = fetch("SELECT stock FROM products WHERE listing_id = ? LIMIT 1", [$listing_id]);
    if ($new && intval($new['stock']) <= 0) {
        insert("UPDATE listings SET status = 'sold_out' WHERE listing_id = ?", [$listing_id]);
    }

    // update transaction status
    insert("UPDATE transactions SET status = 'confirmed', updated_at = current_timestamp() WHERE transaction_id = ?", [$txn_id]);

    // insert message to conversation(s) related to this listing about confirmation
    $map = fetchAll("SELECT conversation_id FROM conversation_listings WHERE listing_id = ?", [$listing_id]);
    foreach ($map as $m) {
        insert("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)", [$m['conversation_id'], $me, "Seller confirmed transaction {$txn_id}. Quantity: {$qty}."]);
        insert("UPDATE conversations SET last_message_at = current_timestamp() WHERE conversation_id = ?", [$m['conversation_id']]);
    }

    $master_conn->commit();
} catch (Exception $e) {
    $master_conn->rollback();
    die('Failed to confirm: ' . $e->getMessage());
}

header('Location: messages.php');
exit;

?>

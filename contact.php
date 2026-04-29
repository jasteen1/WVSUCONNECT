<?php
require_once 'db_conn.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$listing_id = intval($_GET['listing_id'] ?? 0);
if ($listing_id <= 0) die('Invalid listing.');

// Get listing owner
$sql = "SELECT owner_id FROM listings WHERE listing_id = ? LIMIT 1";
$listing = fetch($sql, [$listing_id]);
if (!$listing) die('Listing not found.');

$owner_id = intval($listing['owner_id']);
$me = intval($_SESSION['user_id']);

if ($owner_id === $me) {
    // Can't contact yourself
    header('Location: view-product.php?id=' . $listing_id . '&error=cannot_contact_self');
    exit;
}

// Normalize participant order to match DB unique constraint
if ($me < $owner_id) {
    $a = $me; $b = $owner_id;
} else {
    $a = $owner_id; $b = $me;
}

// Always create a new conversation for this contact action so each listing/contact is separate
// (even if a mapping to this listing already exists). This avoids reusing older conversations.
$conv_id = 0;

// Create new conversation specifically for this listing
// Use a prepared statement so we can gracefully handle duplicate-key (uq_conversation) errors
$stmt = $master_conn->prepare("INSERT INTO conversations (participant_a, participant_b) VALUES (?, ?)");
if ($stmt) {
    $stmt->bind_param('ii', $a, $b);
    if ($stmt->execute()) {
        $conv_id = $master_conn->insert_id;
        @file_put_contents(__DIR__ . '/contact_debug.log', "inserted_conv={$conv_id}\n", FILE_APPEND);
    } else {
        // duplicate key (another process created the same conversation concurrently or uq exists)
        if ($stmt->errno == 1062) {
            $existing = fetch("SELECT conversation_id FROM conversations WHERE participant_a = ? AND participant_b = ? LIMIT 1", [$a, $b]);
            if ($existing && !empty($existing['conversation_id'])) {
                $conv_id = $existing['conversation_id'];
            } else {
                die('Failed creating conversation: duplicate key and could not locate existing conversation');
            }
        } else {
            die('Master Execute Error: ' . $stmt->error);
        }
    }
} else {
    // fallback to helper insert which dies on error
    $conv_id = insert("INSERT INTO conversations (participant_a, participant_b) VALUES (?, ?)", [$a, $b]);
}

// Ensure the general mapping table exists (db_conn also creates it, but be safe)
$create = "CREATE TABLE IF NOT EXISTS conversation_listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    listing_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(conversation_id),
    INDEX(listing_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$master_conn->query($create);

// Insert mapping for this conversation and listing
// Allow multiple mapping rows for same conversation if needed
 $map_id = insert("INSERT INTO conversation_listings (conversation_id, listing_id) VALUES (?, ?)", [$conv_id, $listing_id]);
@file_put_contents(__DIR__ . '/contact_debug.log', "inserted_map_id={$map_id},conv_id={$conv_id},listing={$listing_id}\n", FILE_APPEND);
// Insert an initial message and set last_message_at so the conversation shows up immediately
$init_msg = "Conversation started for listing {$listing_id}.";
insert("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)", [$conv_id, $me, $init_msg]);
insert("UPDATE conversations SET last_message_at = current_timestamp() WHERE conversation_id = ?", [$conv_id]);
// If conv_id wasn't set for some reason, try to find any existing conversation between the participants
if (empty($conv_id) || intval($conv_id) <= 0) {
    $existing = fetch("SELECT conversation_id FROM conversations WHERE (participant_a = ? AND participant_b = ?) OR (participant_a = ? AND participant_b = ?) LIMIT 1", [$a, $b, $b, $a]);
    if ($existing && !empty($existing['conversation_id'])) {
        $conv_id = $existing['conversation_id'];
        // ensure mapping exists
        $exists_map = fetch("SELECT conversation_id FROM conversation_listings WHERE conversation_id = ? AND listing_id = ? LIMIT 1", [$conv_id, $listing_id]);
        if (!$exists_map) {
            insert("INSERT INTO conversation_listings (conversation_id, listing_id) VALUES (?, ?)", [$conv_id, $listing_id]);
        }
    } else {
        // write debug info to a file for investigation and show short message
            $debug = "contact_debug_fail: a={$a},b={$b},listing={$listing_id},master_errno=" . intval($master_conn->errno) . ",master_err=" . $master_conn->error . "\n";
            @file_put_contents(__DIR__ . '/contact_debug.log', $debug, FILE_APPEND);
        die('Failed to create or locate conversation. Debug written to contact_debug.log');
    }
}

header('Location: messages.php?conv=' . intval($conv_id));
exit;

?>

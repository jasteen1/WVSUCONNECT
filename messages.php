<?php
require_once 'db_conn.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$me = intval($_SESSION['user_id']);

// Handle sending a message
// Handle sending a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conv_id'], $_POST['content'])) {
    $conv_id = intval($_POST['conv_id']);
    $content = trim($_POST['content']);
    if ($conv_id > 0 && $content !== '') {
        // ensure conversation_meta exists
        $create_meta = "CREATE TABLE IF NOT EXISTS conversation_meta (
            conversation_id INT UNSIGNED NOT NULL PRIMARY KEY,
            is_closed TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $master_conn->query($create_meta);

        // Use master_conn for an up-to-date check to avoid replica lag
        $stmt = $master_conn->prepare("SELECT is_closed FROM conversation_meta WHERE conversation_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $conv_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $meta = $res->fetch_assoc();
        } else {
            $meta = fetch("SELECT is_closed FROM conversation_meta WHERE conversation_id = ? LIMIT 1", [$conv_id]);
        }

        if ($meta && intval($meta['is_closed']) === 1) {
            // conversation closed — do not accept new messages
            header('Location: messages.php?conv=' . $conv_id . '&error=conversation_closed');
            exit;
        }

        insert("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)", [$conv_id, $me, $content]);
        // Update conversation timestamp
        insert("UPDATE conversations SET last_message_at = current_timestamp() WHERE conversation_id = ?", [$conv_id]);
        // Redirect to avoid repost
        header('Location: messages.php?conv=' . $conv_id);
        exit;
    }
}

// Fetch conversations involving me
$convs = fetchAll("SELECT c.conversation_id, c.participant_a, c.participant_b, c.last_message_at,
    ua.full_name AS a_name, ub.full_name AS b_name
    FROM conversations c
    JOIN users ua ON ua.user_id = c.participant_a
    JOIN users ub ON ub.user_id = c.participant_b
    WHERE c.participant_a = ? OR c.participant_b = ?
    ORDER BY c.last_message_at DESC", [$me, $me]);

$selected_conv = intval($_GET['conv'] ?? 0);
$messages = [];
$other_name = '';
    if ($selected_conv > 0) {
    $messages = fetchAll("SELECT m.*, u.full_name FROM messages m JOIN users u ON u.user_id = m.sender_id WHERE m.conversation_id = ? ORDER BY m.sent_at ASC", [$selected_conv]);
    // find other participant's name
    $c = fetch("SELECT participant_a, participant_b FROM conversations WHERE conversation_id = ? LIMIT 1", [$selected_conv]);
    if ($c) {
        $other = ($c['participant_a'] == $me) ? $c['participant_b'] : $c['participant_a'];
        $userrec = fetch("SELECT full_name FROM users WHERE user_id = ? LIMIT 1", [$other]);
        $other_name = $userrec ? $userrec['full_name'] : '';
    }

    // ensure conversation_meta exists and check closed status
    $create_meta = "CREATE TABLE IF NOT EXISTS conversation_meta (
            conversation_id INT UNSIGNED NOT NULL PRIMARY KEY,
            is_closed TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $master_conn->query($create_meta);
    // prefer master to avoid replica lag when checking closed state
    $is_closed = 0;
    $stmt = $master_conn->prepare("SELECT is_closed FROM conversation_meta WHERE conversation_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $selected_conv);
        $stmt->execute();
        $res = $stmt->get_result();
        $meta = $res->fetch_assoc();
        if ($meta && intval($meta['is_closed']) === 1) $is_closed = 1;
    } else {
        $meta = fetch("SELECT is_closed FROM conversation_meta WHERE conversation_id = ? LIMIT 1", [$selected_conv]);
        $is_closed = ($meta && intval($meta['is_closed']) === 1) ? 1 : 0;
    }

    // Mark messages in this conversation as read (those not sent by me)
    // Always mark as read when viewing the conversation, even if closed — prevents stuck unread counts
    insert("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0", [$selected_conv, $me]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Messages - WVSU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <h5 class="mb-3">Conversations</h5>
            <div class="list-group">
                <?php foreach ($convs as $c):
                    $other = ($c['participant_a'] == $me) ? $c['b_name'] : $c['a_name'];
                    // check if this conversation maps to a listing and fetch title
                    $map = fetch("SELECT cl.listing_id, l.title FROM conversation_listings cl JOIN listings l ON l.listing_id = cl.listing_id WHERE cl.conversation_id = ? LIMIT 1", [intval($c['conversation_id'])]);
                    $label = htmlspecialchars($other);
                    if ($map && !empty($map['title'])) {
                        $label .= ' - ' . htmlspecialchars($map['title']);
                    }
                ?>
                <a href="messages.php?conv=<?= intval($c['conversation_id']) ?>" class="list-group-item list-group-item-action <?= (intval($selected_conv) === intval($c['conversation_id'])) ? 'active' : '' ?>">
                    <?= $label ?>
                    <div class="small text-muted"><?= $c['last_message_at'] ? htmlspecialchars($c['last_message_at']) : '' ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-8">
            <?php if ($selected_conv <= 0): ?>
                <div class="alert alert-info">Select a conversation to view messages.</div>
            <?php else: ?>
                <h5 class="mb-3">Chat with <?= htmlspecialchars($other_name) ?></h5>
                <div class="border rounded p-3 mb-3" style="max-height:60vh; overflow:auto; background:#f8f9fa;">
                    <?php if (empty($messages)): ?>
                        <div class="text-muted">No messages yet. Say hello!</div>
                    <?php else: ?>
                        <?php foreach ($messages as $m): ?>
                            <div class="mb-2">
                                <strong><?= htmlspecialchars($m['full_name']) ?></strong>
                                <div class="small text-muted"><?= htmlspecialchars($m['sent_at']) ?></div>
                                <div class="mt-1"><?= nl2br(htmlspecialchars($m['content'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <form method="POST" action="messages.php">
                    <input type="hidden" name="conv_id" value="<?= intval($selected_conv) ?>">
                    <div class="mb-2">
                        <?php if (!empty($is_closed)): ?>
                            <textarea class="form-control" rows="3" placeholder="Conversation closed — no new messages." disabled></textarea>
                        <?php else: ?>
                            <textarea name="content" class="form-control" rows="3" placeholder="Write a message..." required></textarea>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <?php if (empty($is_closed)): ?>
                            <button class="btn btn-primary">Send</button>
                        <?php else: ?>
                            <div class="text-muted">Conversation closed.</div>
                        <?php endif; ?>
                    </div>
                </form>

                <?php
                // Render seller actions (outside the message form to avoid nested forms)
                $map = fetch("SELECT listing_id FROM conversation_listings WHERE conversation_id = ? LIMIT 1", [$selected_conv]);
                if ($map) {
                    $listing = fetch("SELECT l.listing_id, l.owner_id, l.title, p.stock, p.price FROM listings l JOIN products p ON p.listing_id = l.listing_id WHERE l.listing_id = ? LIMIT 1", [$map['listing_id']]);
                    if ($listing) {
                        // Seller: show 'Complete Transaction' form when conversation not closed
                        if (intval($listing['owner_id']) === $me && empty($is_closed)) {
                            ?>
                            <div class="mt-2">
                                <form method="POST" action="complete_transaction.php" class="d-inline-block">
                                    <input type="hidden" name="conv_id" value="<?= intval($selected_conv) ?>">
                                    <input type="number" name="quantity" value="1" min="1" max="<?= intval($listing['stock']) ?>" class="form-control form-control-sm d-inline-block" style="width:100px; display:inline-block;">
                                    <button class="btn btn-warning btn-sm ms-2" type="submit">Complete Transaction</button>
                                </form>
                            </div>
                            <?php
                        }
                    }
                }
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php

declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/messaging_schema.inc.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

$me = (int) $_SESSION['user_id'];
$conv = (int) ($_POST['conv_id'] ?? 0);

if ($conv <= 0) {
    header('Location: messages.php?error=cancel_sale_bad_conv');
    exit;
}

$c = fetch_master(
    'SELECT participant_a, participant_b FROM conversations WHERE conversation_id = ? LIMIT 1',
    [(string) $conv]
);
if (! $c) {
    header('Location: messages.php?error=cancel_sale_bad_conv');
    exit;
}
$a = (int) $c['participant_a'];
$b = (int) $c['participant_b'];
if ($me !== $a && $me !== $b) {
    header('Location: messages.php?conv=' . $conv . '&error=cancel_sale_forbidden');
    exit;
}

wvsu_conversation_meta_ensure_sale_feedback_columns($master_conn);

$meta = fetch_master(
    'SELECT pending_sale_buyer_id, pending_sale_listing_id, pending_sale_qty FROM conversation_meta WHERE conversation_id = ? LIMIT 1',
    [(string) $conv]
);
$buyerId = (int) ($meta['pending_sale_buyer_id'] ?? 0);
$listingId = (int) ($meta['pending_sale_listing_id'] ?? 0);
$qty = (int) ($meta['pending_sale_qty'] ?? 1);
if ($qty < 1) {
    $qty = 1;
}

if ($buyerId <= 0 || $listingId <= 0) {
    header('Location: messages.php?conv=' . $conv . '&error=cancel_sale_not_pending');
    exit;
}

$list = fetch_master(
    'SELECT listing_id, owner_id, listing_type, title FROM listings WHERE listing_id = ? LIMIT 1',
    [(string) $listingId]
);
if (! $list) {
    header('Location: messages.php?conv=' . $conv . '&error=cancel_sale_bad_listing');
    exit;
}
$sellerId = (int) ($list['owner_id'] ?? 0);
if ($sellerId <= 0) {
    header('Location: messages.php?conv=' . $conv . '&error=cancel_sale_bad_listing');
    exit;
}

if ($me !== $buyerId && $me !== $sellerId) {
    header('Location: messages.php?conv=' . $conv . '&error=cancel_sale_forbidden');
    exit;
}

if (! $master_conn->begin_transaction()) {
    header('Location: messages.php?conv=' . $conv . '&error=cancel_sale_db');
    exit;
}

try {
    $lt = strtolower(trim((string) ($list['listing_type'] ?? 'product')));
    if ($lt === 'product') {
        $prod = fetch_master('SELECT listing_id FROM products WHERE listing_id = ? LIMIT 1', [(string) $listingId]);
        if ($prod) {
            insert(
                'UPDATE products SET stock = stock + ? WHERE listing_id = ?',
                [(string) $qty, (string) $listingId]
            );
            $stockRow = fetch_master('SELECT stock FROM products WHERE listing_id = ? LIMIT 1', [(string) $listingId]);
            if ($stockRow && (int) $stockRow['stock'] > 0) {
                insert(
                    'UPDATE listings SET status = \'active\' WHERE listing_id = ? AND status = \'sold_out\'',
                    [(string) $listingId]
                );
            }
        }
    }

    $clearMeta = $master_conn->prepare(
        'INSERT INTO conversation_meta (conversation_id, is_closed, pending_sale_buyer_id, pending_sale_listing_id, pending_sale_qty)
         VALUES (?, 0, NULL, NULL, 1)
         ON DUPLICATE KEY UPDATE is_closed = 0,
            pending_sale_buyer_id = NULL,
            pending_sale_listing_id = NULL,
            pending_sale_qty = 1'
    );
    if (! $clearMeta) {
        throw new RuntimeException('prepare_clear_meta');
    }
    $convBind = $conv;
    $clearMeta->bind_param('i', $convBind);
    if (! $clearMeta->execute()) {
        $err = $clearMeta->error;
        $clearMeta->close();
        throw new RuntimeException($err !== '' ? $err : 'exec_clear_meta');
    }
    $clearMeta->close();

    $who = fetch_master('SELECT full_name FROM users WHERE user_id = ? LIMIT 1', [(string) $me]);
    $whoName = trim((string) ($who['full_name'] ?? ''));
    $label = $whoName !== '' ? $whoName : 'Someone';
    $title = trim((string) ($list['title'] ?? ''));
    $bit = $title !== '' ? ' (“' . $title . '”)' : '';
    $line = $label . ' cancelled the pending sale' . $bit . '. '
        . ($lt === 'product' ? 'Stock was restored where applicable. ' : '')
        . 'You can message normally in this chat.';

    insert(
        'INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)',
        [(string) $conv, (string) $me, $line]
    );
    insert(
        'UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE conversation_id = ?',
        [(string) $conv]
    );

    $master_conn->commit();
} catch (Throwable $e) {
    $master_conn->rollback();
    @file_put_contents(
        __DIR__ . '/cancel_pending_sale_debug.log',
        date('c') . ' ' . $e->getMessage() . "\n",
        FILE_APPEND
    );
    header('Location: messages.php?conv=' . $conv . '&error=cancel_sale_db');
    exit;
}

header('Location: messages.php?conv=' . $conv . '&notice=pending_sale_cancelled');
exit;

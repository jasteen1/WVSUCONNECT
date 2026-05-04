<?php

declare(strict_types=1);

/**
 * Seller marks a service booking as done in-chat (no stock change).
 * Reuses conversation_meta pending_sale_* so the buyer uses the same feedback flow as products.
 */

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
$listing_id = (int) ($_POST['listing_id'] ?? 0);

if ($conv <= 0) {
    header('Location: messages.php?error=complete_svc');
    exit;
}

if ($listing_id <= 0) {
    $pick = fetch_master(
        'SELECT cl.listing_id FROM conversation_listings cl
            INNER JOIN listings l ON l.listing_id = cl.listing_id
            INNER JOIN services s ON s.listing_id = l.listing_id
            WHERE cl.conversation_id = ? AND l.owner_id = ?
            ORDER BY cl.id DESC
            LIMIT 1',
        [(string) $conv, (string) $me]
    );
    if ($pick) {
        $listing_id = (int) $pick['listing_id'];
    }
}

if ($listing_id <= 0) {
    header('Location: messages.php?conv=' . $conv . '&error=complete_svc_no_listing');
    exit;
}

$mapped = fetch_master(
    'SELECT 1 AS ok FROM conversation_listings WHERE conversation_id = ? AND listing_id = ? LIMIT 1',
    [(string) $conv, (string) $listing_id]
);
if (! $mapped) {
    header('Location: messages.php?conv=' . $conv . '&error=complete_svc_bad_listing');
    exit;
}

$item = fetch_master(
    'SELECT l.listing_id, l.owner_id, l.title, l.listing_type
     FROM listings l
     INNER JOIN services s ON s.listing_id = l.listing_id
     WHERE l.listing_id = ?
     LIMIT 1',
    [(string) $listing_id]
);

if (! $item) {
    header('Location: messages.php?conv=' . $conv . '&error=complete_svc_service_only');
    exit;
}

if (strtolower(trim((string) ($item['listing_type'] ?? 'product'))) !== 'service') {
    header('Location: messages.php?conv=' . $conv . '&error=complete_svc_service_only');
    exit;
}

if ((int) $item['owner_id'] !== $me) {
    header('Location: messages.php?conv=' . $conv . '&error=complete_svc_seller_only');
    exit;
}

$c = fetch_master(
    'SELECT participant_a, participant_b FROM conversations WHERE conversation_id = ? LIMIT 1',
    [(string) $conv]
);
if (! $c) {
    header('Location: messages.php?conv=' . $conv . '&error=complete_svc');
    exit;
}

$a = (int) $c['participant_a'];
$b = (int) $c['participant_b'];
if ($me !== $a && $me !== $b) {
    header('Location: messages.php?conv=' . $conv . '&error=complete_svc');
    exit;
}

$buyer_id = ($a === $me) ? $b : $a;

if ($buyer_id <= 0) {
    header('Location: messages.php?conv=' . $conv . '&error=complete_svc');
    exit;
}

global $master_conn;

$ensureMeta = static function () use ($master_conn): void {
    $master_conn->query(
        'CREATE TABLE IF NOT EXISTS conversation_meta (
            conversation_id INT UNSIGNED NOT NULL PRIMARY KEY,
            is_closed TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    wvsu_conversation_meta_ensure_sale_feedback_columns($master_conn);
};

if (! $master_conn->begin_transaction()) {
    header('Location: messages.php?conv=' . $conv . '&error=complete_svc');
    exit;
}

try {
    $ensureMeta();
    $qty = 1;
    insert(
        'INSERT INTO conversation_meta (conversation_id, is_closed, pending_sale_buyer_id, pending_sale_listing_id, pending_sale_qty)
         VALUES (?, 1, ?, ?, ?)
         ON DUPLICATE KEY UPDATE is_closed = 1,
            pending_sale_buyer_id = VALUES(pending_sale_buyer_id),
            pending_sale_listing_id = VALUES(pending_sale_listing_id),
            pending_sale_qty = VALUES(pending_sale_qty)',
        [(string) $conv, (string) $buyer_id, (string) $listing_id, (string) $qty]
    );

    $title = trim((string) ($item['title'] ?? 'service'));
    if ($title === '') {
        $title = 'service';
    }
    $content = 'Freelancer marked this service as done for “' . $title . '”.';

    insert(
        'INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)',
        [(string) $conv, (string) $me, $content]
    );
    insert(
        'UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE conversation_id = ?',
        [(string) $conv]
    );

    $master_conn->commit();
} catch (Throwable $e) {
    $master_conn->rollback();
    header('Location: messages.php?conv=' . $conv . '&error=complete_svc_failed');
    exit;
}

header('Location: messages.php?conv=' . $conv . '&success=service_completed');
exit;

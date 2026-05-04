<?php
declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/profiles_reviews.inc.php';
require_once __DIR__ . '/messaging_schema.inc.php';
require_once __DIR__ . '/wvsu_upload_dirs.inc.php';
require_once __DIR__ . '/wvsu_smart_back.inc.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php?next=' . rawurlencode('messages.php'));
    exit;
}

wvsu_messaging_ensure_schema($master_conn);
wvsu_conversation_meta_ensure_sale_feedback_columns($master_conn);

$me = (int) $_SESSION['user_id'];

/** Open latest unread conversation (navbar badge opens this route so opening the thread clears the count). */
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['focus'])
    && (string) $_GET['focus'] === 'unread'
    && (int) ($_GET['conv'] ?? 0) <= 0) {
    $unreadConv = fetch_master(
        'SELECT m.conversation_id FROM messages m
            INNER JOIN conversations c ON m.conversation_id = c.conversation_id
            WHERE m.is_read = 0 AND m.sender_id <> ? AND (c.participant_a = ? OR c.participant_b = ?)
            ORDER BY m.sent_at DESC
            LIMIT 1',
        [(string) $me, (string) $me, (string) $me]
    );
    if ($unreadConv && (int) $unreadConv['conversation_id'] > 0) {
        header('Location: messages.php?conv=' . (int) $unreadConv['conversation_id']);
        exit;
    }
    header('Location: messages.php');
    exit;
}

/** Ensure conversation_meta exists */
$ensureMeta = static function () use ($master_conn): void {
    $master_conn->query(
        'CREATE TABLE IF NOT EXISTS conversation_meta (
            conversation_id INT UNSIGNED NOT NULL PRIMARY KEY,
            is_closed TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
};
$ensureMeta();

/** Returns 1 if conversation is closed, else 0 (always read master — replica lag hid reopens before). */
$convIsClosed = static function (mysqli $master, int $convId): int {
    $meta = fetch_master('SELECT is_closed FROM conversation_meta WHERE conversation_id = ? LIMIT 1', [(string) $convId]);

    return $meta && (int) $meta['is_closed'] === 1 ? 1 : 0;
};

/** Allow messaging again after a completed sale — opening the chat or sending clears the closed flag. */
$ensureConvOpenForMessaging = static function (int $convId) use ($ensureMeta): void {
    if ($convId <= 0) {
        return;
    }
    $ensureMeta();
    insert(
        'INSERT INTO conversation_meta (conversation_id, is_closed) VALUES (?, 0)
         ON DUPLICATE KEY UPDATE is_closed = 0',
        [(string) $convId]
    );
};

/** User belongs to conversation */
$participantCheck = static function (int $convId) use ($master_conn, $me): bool {
    $row = fetch_master(
        'SELECT participant_a, participant_b FROM conversations WHERE conversation_id = ? LIMIT 1',
        [(string) $convId]
    );
    if (! $row) {
        return false;
    }
    $a = (int) $row['participant_a'];
    $b = (int) $row['participant_b'];

    return $me === $a || $me === $b;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conv_id'])) {
    $conv_id = (int) $_POST['conv_id'];

    /* Re-open a conversation closed after completing a sale (either side may resume messaging). */
    if ($conv_id > 0 && $participantCheck($conv_id)
        && isset($_POST['wvsu_reopen_conversation']) && (string) $_POST['wvsu_reopen_conversation'] === '1') {
        $pendReopen = fetch_master(
            'SELECT pending_sale_buyer_id FROM conversation_meta WHERE conversation_id = ? LIMIT 1',
            [(string) $conv_id]
        );
        if ($pendReopen && (int) ($pendReopen['pending_sale_buyer_id'] ?? 0) > 0) {
            header('Location: messages.php?conv=' . $conv_id . '&error=sale_feedback_reopen_blocked');
            exit;
        }
        if ($convIsClosed($master_conn, $conv_id) === 1) {
            insert('UPDATE conversation_meta SET is_closed = 0 WHERE conversation_id = ?', [(string) $conv_id]);
            $me_name = '';
            $nr = fetch_master('SELECT full_name FROM users WHERE user_id = ? LIMIT 1', [(string) $me]);
            if ($nr) {
                $me_name = trim((string) ($nr['full_name'] ?? ''));
            }
            $reopenLine = ($me_name !== '' ? ($me_name . ' ') : '')
                . 'reopened this chat — you can message again.';
            insert(
                'INSERT INTO messages (conversation_id, sender_id, content) VALUES (?,?,?)',
                [(string) $conv_id, (string) $me, $reopenLine]
            );
            insert(
                'UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE conversation_id = ?',
                [(string) $conv_id]
            );
        }
        header('Location: messages.php?conv=' . $conv_id . '&notice=conversation_reopened');
        exit;
    }

    /* Voluntarily end an open chat — either side; use Message again later. Blocked while sale feedback is pending. */
    if ($conv_id > 0 && $participantCheck($conv_id)
        && isset($_POST['wvsu_end_chat']) && (string) $_POST['wvsu_end_chat'] === '1') {
        wvsu_conversation_meta_ensure_sale_feedback_columns($master_conn);
        $pendEnd = fetch_master(
            'SELECT pending_sale_buyer_id FROM conversation_meta WHERE conversation_id = ? LIMIT 1',
            [(string) $conv_id]
        );
        if ($pendEnd && (int) ($pendEnd['pending_sale_buyer_id'] ?? 0) > 0) {
            header('Location: messages.php?conv=' . $conv_id . '&error=end_chat_pending_block');
            exit;
        }
        if ($convIsClosed($master_conn, $conv_id) === 1) {
            header('Location: messages.php?conv=' . $conv_id . '&notice=chat_already_closed');
            exit;
        }
        $endCid = $conv_id;
        $stEnd = $master_conn->prepare(
            'INSERT INTO conversation_meta (conversation_id, is_closed, pending_sale_buyer_id, pending_sale_listing_id, pending_sale_qty)
             VALUES (?, 1, NULL, NULL, 1)
             ON DUPLICATE KEY UPDATE is_closed = 1'
        );
        if (! $stEnd || ! $stEnd->bind_param('i', $endCid) || ! $stEnd->execute()) {
            if ($stEnd) {
                $stEnd->close();
            }
            header('Location: messages.php?conv=' . $conv_id . '&error=end_chat_db');
            exit;
        }
        $stEnd->close();
        $me_name_end = '';
        $nrEnd = fetch_master('SELECT full_name FROM users WHERE user_id = ? LIMIT 1', [(string) $me]);
        if ($nrEnd) {
            $me_name_end = trim((string) ($nrEnd['full_name'] ?? ''));
        }
        $endLine = ($me_name_end !== '' ? ($me_name_end . ' ') : '')
            . 'ended this chat — tap Message again when you want to keep talking.';
        insert(
            'INSERT INTO messages (conversation_id, sender_id, content) VALUES (?,?,?)',
            [(string) $conv_id, (string) $me, $endLine]
        );
        insert(
            'UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE conversation_id = ?',
            [(string) $conv_id]
        );
        header('Location: messages.php?conv=' . $conv_id . '&notice=chat_ended');
        exit;
    }

    $content = trim((string) ($_POST['content'] ?? ''));
    $hasFile = ! empty($_FILES['attachment']['name'])
        && isset($_FILES['attachment']['error'])
        && (int) $_FILES['attachment']['error'] === UPLOAD_ERR_OK;

    if ($conv_id > 0 && $participantCheck($conv_id)) {
        $pendSend = fetch_master(
            'SELECT pending_sale_buyer_id FROM conversation_meta WHERE conversation_id = ? LIMIT 1',
            [(string) $conv_id]
        );
        if ($pendSend && (int) ($pendSend['pending_sale_buyer_id'] ?? 0) > 0) {
            header('Location: messages.php?conv=' . $conv_id . '&error=sale_feedback_block');
            exit;
        }
        if ($convIsClosed($master_conn, $conv_id) === 1) {
            $ensureConvOpenForMessaging($conv_id);
        }

        $attachmentErrCode = isset($_FILES['attachment']['error'])
            ? (int) $_FILES['attachment']['error']
            : UPLOAD_ERR_NO_FILE;
        if (
            $attachmentErrCode !== UPLOAD_ERR_NO_FILE
            && $attachmentErrCode !== UPLOAD_ERR_OK
        ) {
            $photoErrRedir = match ($attachmentErrCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'image_too_large',
                UPLOAD_ERR_PARTIAL => 'upload_failed',
                UPLOAD_ERR_NO_TMP_DIR => 'upload_dir',
                default => 'upload_failed',
            };
            header('Location: messages.php?conv=' . $conv_id . '&error=' . rawurlencode($photoErrRedir));
            exit;
        }

        $msgType = 'text';
        $imageUrl = '';
        $textContent = $content;

        if ($hasFile) {
            $tmp = (string) ($_FILES['attachment']['tmp_name'] ?? '');
            $orig = basename((string) ($_FILES['attachment']['name'] ?? ''));
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (! in_array($ext, $allowedExt, true)) {
                header('Location: messages.php?conv=' . $conv_id . '&error=bad_image_type');
                exit;
            }
            if ($tmp === '' || ! is_uploaded_file($tmp)) {
                header('Location: messages.php?conv=' . $conv_id . '&error=upload_failed');
                exit;
            }
            $mime = '';
            if (class_exists('finfo')) {
                try {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = (string) ($finfo->file($tmp) ?: '');
                } catch (Throwable $e) {
                    $mime = '';
                }
            }
            if ($mime === '' || $mime === 'application/octet-stream') {
                $gi = @getimagesize($tmp);
                if (is_array($gi) && ! empty($gi['mime'])) {
                    $mime = (string) $gi['mime'];
                }
            }
            $allowedMime = [
                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
            ];
            if ($mime !== '' && ! in_array($mime, $allowedMime, true)) {
                header('Location: messages.php?conv=' . $conv_id . '&error=bad_image_type');
                exit;
            }
            if ($mime === '') {
                header('Location: messages.php?conv=' . $conv_id . '&error=bad_image_type');
                exit;
            }
            if ((int) ($_FILES['attachment']['size'] ?? 0) > 6 * 1024 * 1024) {
                header('Location: messages.php?conv=' . $conv_id . '&error=image_too_large');
                exit;
            }

            $dir = wvsu_ensure_writable_messages_upload_dir(__DIR__ . '/message_upload_debug.log');
            if ($dir === null) {
                header('Location: messages.php?conv=' . $conv_id . '&error=upload_dir');
                exit;
            }
            $safeExt = match ($ext) {
                'jpg', 'jpeg' => 'jpg',
                'png' => 'png',
                'webp' => 'webp',
                'gif' => 'gif',
                default => 'jpg',
            };
            $newName = sprintf(
                'm_%d_%d_%s_%s.%s',
                $conv_id,
                $me,
                (string) time(),
                bin2hex(random_bytes(4)),
                $safeExt
            );
            $dest = $dir . DIRECTORY_SEPARATOR . $newName;
            if (! move_uploaded_file($tmp, $dest)) {
                header('Location: messages.php?conv=' . $conv_id . '&error=upload_failed');
                exit;
            }
            @chmod($dest, 0666);
            $imageUrl = 'uploads/messages/' . $newName;
            $msgType = 'image';
        }

        if ($textContent === '' && $msgType !== 'image') {
            header('Location: messages.php?conv=' . $conv_id . '&error=empty_message');
            exit;
        }

        insert(
            'INSERT INTO messages (conversation_id, sender_id, content, message_type, image_url) VALUES (?,?,?,?,?)',
            [
                (string) $conv_id,
                (string) $me,
                $textContent,
                $msgType,
                $imageUrl,
            ]
        );

        insert('UPDATE conversations SET last_message_at = current_timestamp() WHERE conversation_id = ?', [(string) $conv_id]);
        header('Location: messages.php?conv=' . $conv_id);
        exit;
    }

    header('Location: messages.php');
    exit;
}

$convSql = "SELECT c.conversation_id, c.participant_a, c.participant_b, c.last_message_at,
    ua.full_name AS a_name, ub.full_name AS b_name,
    ua.updated_at AS a_updated, ub.updated_at AS b_updated,
    (SELECT LEFT(m.content, 100)
     FROM messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.sent_at DESC LIMIT 1) AS preview_text,
    (SELECT m.message_type
     FROM messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.sent_at DESC LIMIT 1) AS preview_type,
    (SELECT COUNT(*) FROM messages m2
      WHERE m2.conversation_id = c.conversation_id AND m2.sender_id != ? AND m2.is_read = 0) AS unread_cnt,
    (SELECT LEFT(l.title, 56)
     FROM conversation_listings cl
     JOIN listings l ON l.listing_id = cl.listing_id
     WHERE cl.conversation_id = c.conversation_id
       AND (l.owner_id = c.participant_a OR l.owner_id = c.participant_b)
     ORDER BY cl.id DESC LIMIT 1) AS listing_thread_title,
    (SELECT l.listing_type
     FROM conversation_listings cl
     JOIN listings l ON l.listing_id = cl.listing_id
     WHERE cl.conversation_id = c.conversation_id
       AND (l.owner_id = c.participant_a OR l.owner_id = c.participant_b)
     ORDER BY cl.id DESC LIMIT 1) AS listing_thread_type
FROM conversations c
JOIN users ua ON ua.user_id = c.participant_a
JOIN users ub ON ub.user_id = c.participant_b
WHERE c.participant_a = ? OR c.participant_b = ?
ORDER BY c.last_message_at DESC";

$convs = fetchAll_master($convSql, [(string) $me, (string) $me, (string) $me]);

$selected_conv = (int) ($_GET['conv'] ?? 0);
$messages = [];
$other_name = '';
$other_user_id = 0;
$other_updated = '';
$is_closed = 0;
$saleFeedbackPending = false;
$pendingBuyerId = 0;
$pendingListingId = 0;
$pendingSellerId = 0;
$canCancelPendingSale = false;
/** Product row for “Complete sale” when it matches the listing strip + freshness rules (see below). */
$sellerCompleteListingRow = null;
/** Service row for “Done service” (same meta + feedback flow as products). */
$sellerServiceDoneRow = null;
$pendingListingIsService = false;

if ($selected_conv > 0) {
    if (! $participantCheck($selected_conv)) {
        header('Location: messages.php');
        exit;
    }

    $messages = fetchAll_master(
        'SELECT m.message_id, m.sender_id, m.content, m.message_type, m.image_url, m.sent_at, m.is_read, u.full_name
         FROM messages m
         JOIN users u ON u.user_id = m.sender_id
         WHERE m.conversation_id = ?
         ORDER BY m.sent_at ASC',
        [(string) $selected_conv]
    );

    $c = fetch_master(
        'SELECT participant_a, participant_b FROM conversations WHERE conversation_id = ? LIMIT 1',
        [(string) $selected_conv]
    );
    if ($c) {
        $other = ((int) $c['participant_a'] === $me) ? $c['participant_b'] : $c['participant_a'];
        $other_user_id = (int) $other;
        $userrec = fetch_master(
            'SELECT full_name, updated_at FROM users WHERE user_id = ? LIMIT 1',
            [(string) $other_user_id]
        );
        if ($userrec) {
            $other_name = (string) $userrec['full_name'];
            $other_updated = (string) ($userrec['updated_at'] ?? '');
        }
    }

    $convMetaRow = fetch_master(
        'SELECT is_closed, pending_sale_buyer_id, pending_sale_listing_id, pending_sale_qty FROM conversation_meta WHERE conversation_id = ? LIMIT 1',
        [(string) $selected_conv]
    );
    $is_closed = $convMetaRow && (int) ($convMetaRow['is_closed'] ?? 0) === 1 ? 1 : 0;
    $saleFeedbackPending = (bool) ($convMetaRow && (int) ($convMetaRow['pending_sale_buyer_id'] ?? 0) > 0);
    $pendingBuyerId = $saleFeedbackPending ? (int) $convMetaRow['pending_sale_buyer_id'] : 0;
    $pendingListingId = ($convMetaRow && (int) ($convMetaRow['pending_sale_listing_id'] ?? 0) > 0)
        ? (int) $convMetaRow['pending_sale_listing_id']
        : 0;
    if ($saleFeedbackPending && $pendingListingId > 0 && $pendingBuyerId > 0) {
        $psrOw = fetch_master(
            'SELECT owner_id FROM listings WHERE listing_id = ? LIMIT 1',
            [(string) $pendingListingId]
        );
        $pendingSellerId = (int) ($psrOw['owner_id'] ?? 0);
        $canCancelPendingSale = $pendingSellerId > 0 && ($me === $pendingBuyerId || $me === $pendingSellerId);
    }
    if ($pendingListingId > 0) {
        $pltR = fetch_master(
            'SELECT LOWER(TRIM(IFNULL(listing_type, \'product\'))) AS lt FROM listings WHERE listing_id = ? LIMIT 1',
            [(string) $pendingListingId]
        );
        $pendingListingIsService = (bool) ($pltR && (($pltR['lt'] ?? '') === 'service'));
    }

    insert(
        'UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ? AND is_read = 0',
        [(string) $selected_conv, (string) $me]
    );
}

/** Listing linked to conversation (banner + clearer header badge) */
$listingContext = null;
$headerListingTitle = '';
if ($selected_conv > 0) {
    /** Prefer listings owned by someone in this thread (avoids orphan rows / wrong product vs current service chat). */
    $map = null;
    if ($other_user_id > 0) {
        $map = fetch_master(
            'SELECT l.listing_id, l.title, l.image_url, l.listing_type, l.owner_id, l.status,
                    cl.created_at AS linked_at
             FROM conversation_listings cl
             JOIN listings l ON l.listing_id = cl.listing_id
             WHERE cl.conversation_id = ?
               AND (l.owner_id = ? OR l.owner_id = ?)
             ORDER BY cl.id DESC
             LIMIT 1',
            [(string) $selected_conv, (string) $me, (string) $other_user_id]
        );
    }
    if (! $map) {
        $map = fetch_master(
            'SELECT l.listing_id, l.title, l.image_url, l.listing_type, l.owner_id, l.status,
                    cl.created_at AS linked_at
             FROM conversation_listings cl
             JOIN listings l ON l.listing_id = cl.listing_id
             WHERE cl.conversation_id = ?
             ORDER BY cl.id DESC
             LIMIT 1',
            [(string) $selected_conv]
        );
    }
    if ($map) {
        $lid = (int) $map['listing_id'];
        $lt = strtolower((string) ($map['listing_type'] ?? 'product'));
        $stNorm = strtolower(trim((string) ($map['status'] ?? 'active')));
        $listingContext = [
            'listing_id' => $lid,
            'title' => trim((string) ($map['title'] ?? '')) !== ''
                ? trim((string) $map['title'])
                : ($lt === 'service' ? 'Service listing' : 'Product listing'),
            'image_url' => trim((string) ($map['image_url'] ?? '')),
            'listing_type' => $lt === 'service' ? 'service' : 'product',
            'owner_id' => (int) ($map['owner_id'] ?? 0),
            'status' => $stNorm,
            'linked_at' => (string) ($map['linked_at'] ?? ''),
            'href' => wvsu_append_listing_return(
                ($lt === 'service' ? 'view-service.php?id=' : 'view-product.php?id=') . $lid,
                $selected_conv > 0 ? 'messages.php?conv=' . $selected_conv : 'messages.php'
            ),
            'stock' => null,
            'is_sold_out' => false,
            'is_unavailable' => in_array($stNorm, ['inactive', 'banned'], true),
        ];
        if ($listingContext['listing_type'] === 'product') {
            $prRow = fetch_master(
                'SELECT stock FROM products WHERE listing_id = ? LIMIT 1',
                [(string) $lid]
            );
            if ($prRow) {
                $listingContext['stock'] = (int) ($prRow['stock'] ?? 0);
            }
            $listingContext['is_sold_out'] = ($stNorm === 'sold_out')
                || ($listingContext['stock'] !== null && (int) $listingContext['stock'] <= 0);
        } else {
            $listingContext['is_sold_out'] = ($stNorm === 'sold_out');
        }
        $headerListingTitle = $listingContext['title'];
    }
}

/** Only offer Complete sale for the listing in the strip above, if you own it, it is active, in stock, and the contact link is not ancient. */
if (
    $selected_conv > 0
    && $listingContext !== null
    && ! $saleFeedbackPending
    && ! $is_closed
) {
    $linkTs = strtotime($listingContext['linked_at'] ?? '');
    $linkAgeOk = $linkTs !== false
        && $linkTs > 0
        && (time() - $linkTs) <= (42 * 86400);
    if (
        $linkAgeOk
        && ($listingContext['listing_type'] ?? '') === 'product'
        && (int) ($listingContext['owner_id'] ?? 0) === $me
        && (($listingContext['status'] ?? 'active') === 'active')
    ) {
        $sellerCompleteListingRow = fetch_master(
            'SELECT l.listing_id, l.owner_id, l.title, p.stock, p.price
             FROM listings l
             INNER JOIN products p ON p.listing_id = l.listing_id
             WHERE l.listing_id = ? AND l.owner_id = ? AND p.stock > 0
             LIMIT 1',
            [(string) $listingContext['listing_id'], (string) $me]
        );
        if (! $sellerCompleteListingRow) {
            $sellerCompleteListingRow = null;
        }
    } elseif (
        $linkAgeOk
        && ($listingContext['listing_type'] ?? '') === 'service'
        && (int) ($listingContext['owner_id'] ?? 0) === $me
        && (($listingContext['status'] ?? 'active') === 'active')
    ) {
        $sellerServiceDoneRow = fetch_master(
            'SELECT l.listing_id, l.owner_id, l.title, s.rate, s.rate_type
             FROM listings l
             INNER JOIN services s ON s.listing_id = l.listing_id
             WHERE l.listing_id = ? AND l.owner_id = ?
             LIMIT 1',
            [(string) $listingContext['listing_id'], (string) $me]
        );
        if (! $sellerServiceDoneRow) {
            $sellerServiceDoneRow = null;
        }
    }
}

$listingStripHeadline = '';
if ($listingContext !== null && $listingContext['listing_id'] > 0) {
    $oid = $listingContext['owner_id'];
    if ($me === $oid) {
        $listingStripHeadline = "They're messaging you about your listing.";
    } elseif ($other_user_id === $oid) {
        $listingStripHeadline = 'You contacted them about this listing.';
    } else {
        $listingStripHeadline = 'This conversation is tied to a listing.';
    }
}

/** Caption under outbound bubbles when recipient has opened the chat (messages.is_read = 1 for your sends). */
$seenReadReceiptPhrase = 'Seen';
if ($selected_conv > 0 && $other_user_id > 0) {
    if ($listingContext !== null && (int) ($listingContext['owner_id'] ?? 0) > 0) {
        $lo = (int) $listingContext['owner_id'];
        if ($lo === $other_user_id) {
            $seenReadReceiptPhrase = 'Seller has seen your message';
        } elseif ($lo === $me) {
            $seenReadReceiptPhrase = 'Buyer has seen your message';
        }
    }
    if ($seenReadReceiptPhrase === 'Seen' && trim((string) $other_name) !== '') {
        $seenReadReceiptPhrase = trim((string) $other_name) . ' has seen your message';
    }
}

$meRow = fetch_master('SELECT updated_at FROM users WHERE user_id = ? LIMIT 1', [(string) $me]);
$myUt = (string) ($meRow['updated_at'] ?? '');
$myAvatar = htmlspecialchars(wvsu_user_avatar_img_src($me, $myUt), ENT_QUOTES, 'UTF-8');
$theirAvatar = $other_user_id > 0
    ? htmlspecialchars(wvsu_user_avatar_img_src($other_user_id, $other_updated), ENT_QUOTES, 'UTF-8')
    : '';

/** At least one listing you own and one they own appear across all threads between you two (mutual buyer/seller vibe). */
$mutualCrossTrade = false;
if ($selected_conv > 0 && $other_user_id > 0) {
    $pairRows = fetchAll_master(
        'SELECT DISTINCT l.owner_id AS oid FROM conversations c
            INNER JOIN conversation_listings cl ON cl.conversation_id = c.conversation_id
            INNER JOIN listings l ON l.listing_id = cl.listing_id
            WHERE (c.participant_a = ? AND c.participant_b = ?) OR (c.participant_a = ? AND c.participant_b = ?)',
        [(string) $me, (string) $other_user_id, (string) $other_user_id, (string) $me]
    );
    $sawMe = false;
    $sawThem = false;
    foreach ($pairRows as $pr) {
        $oid = (int) ($pr['oid'] ?? 0);
        if ($oid === $me) {
            $sawMe = true;
        }
        if ($oid === $other_user_id) {
            $sawThem = true;
        }
    }
    $mutualCrossTrade = $sawMe && $sawThem;
}

$flashNotice = match ((string) ($_GET['notice'] ?? '')) {
    'conversation_reopened' => 'Chat reopened — you can send messages again.',
    'sale_feedback_saved' => 'Thanks — your feedback and photo were saved. You can keep messaging in this chat; when you’re ready for another purchase or booking, the seller can use Complete sale or Done service again.',
    'reviews_in_messages' => 'Reviews after a handover are submitted here in the chat after the seller uses Complete sale (product) or Done service — not only on the profile page.',
    'pending_sale_cancelled' => 'The pending completion was cancelled — product stock was restored if it applied, and you can message normally in this chat.',
    'chat_ended' => 'This chat was ended — use Message again whenever you want to continue.',
    'chat_already_closed' => 'This chat is already in an ended state — use Message again to send more.',
    default => '',
};
if ($flashNotice === '' && (string) ($_GET['success'] ?? '') === 'transaction_completed') {
    $flashNotice = 'Sale marked complete — stock was updated. The buyer must leave a star rating, a short comment, and a photo before this chat fully reopens.';
}
if ($flashNotice === '' && (string) ($_GET['success'] ?? '') === 'service_completed') {
    $flashNotice = 'Service marked done. The buyer must leave a star rating, a short comment, and a photo before this chat fully reopens.';
}

$flashErr = match ((string) ($_GET['error'] ?? '')) {
    'conversation_closed' => 'This chat was busy refreshing — try sending again.',
    'empty_message' => 'Type a message or attach a photo.',
    'bad_image_type' => 'Please attach JPG, PNG, WebP, or GIF only.',
    'image_too_large' => 'Image is too large (max 6MB).',
    'upload_failed' => 'Could not upload the image. Try again.',
    'upload_dir' => 'Upload folder is not writable — contact admin.',
    'db' => 'Could not save the message.',
    'complete_tx' => 'Could not complete sale — refresh and try again.',
    'complete_tx_no_listing' => 'Nothing to complete in this chat (no listing tied to your seller account here).',
    'complete_tx_bad_listing' => 'That listing is not attached to this chat.',
    'complete_tx_product_only' => 'Complete sale is only for product listings with stock.',
    'complete_tx_seller_only' => 'Only the listing owner can mark the sale.',
    'complete_tx_stock' => 'Not enough stock left for that quantity.',
    'complete_tx_failed' => 'Database error while completing — try again.',
    'complete_svc' => 'Could not mark the service done — refresh and try again.',
    'complete_svc_no_listing' => 'Nothing to complete in this chat (no service listing tied to your account here).',
    'complete_svc_bad_listing' => 'That listing is not attached to this chat.',
    'complete_svc_service_only' => 'Done service is only for service listings.',
    'complete_svc_seller_only' => 'Only the freelancer who owns the listing can mark it done.',
    'complete_svc_failed' => 'Database error while marking done — try again.',
    'sale_feedback_block' => 'Please finish your sale feedback (rating, comment, and photo) first.',
    'sale_feedback_reopen_blocked' => 'The buyer still needs to submit sale feedback — the chat will reopen automatically after they do.',
    'sale_feedback_invalid' => 'That feedback link is no longer valid.',
    'sale_feedback_not_pending' => 'There is no pending sale feedback for you in this chat.',
    'sale_feedback_photo_required' => 'Please upload a photo with your feedback.',
    'sale_feedback_comment_required' => 'Please write a short comment with your feedback.',
    'sale_feedback_bad_image' => 'Use JPG, PNG, WebP, or GIF for your feedback photo.',
    'sale_feedback_image_large' => 'Feedback photo is too large (max 6MB).',
    'sale_feedback_upload' => 'Could not upload your photo — try again.',
    'sale_feedback_upload_dir' => 'Upload folder is not writable — contact admin.',
    'sale_feedback_db' => 'Could not save feedback — try again.',
    'sale_feedback_duplicate' => 'Feedback for this exact listing is already saved. If you just submitted, refresh the page.',
    'cancel_sale_not_pending' => 'There is no pending sale to cancel in this chat.',
    'cancel_sale_forbidden' => 'You can’t cancel this sale in this chat.',
    'cancel_sale_bad_conv' => 'That conversation is invalid.',
    'cancel_sale_bad_listing' => 'The listing tied to this pending sale is missing — try refreshing.',
    'cancel_sale_db' => 'Could not cancel the sale — try again.',
    'end_chat_pending_block' => 'Finish or cancel the pending handover first (buyer feedback or Cancel transaction).',
    'end_chat_db' => 'Could not end the chat — try again.',
    default => '',
};

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0d4daa">
    <title>Messages — WVSU CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body class="wvsu-messenger-page">
<?php include __DIR__ . '/navbar.php'; ?>

<div class="w-100 wvsu-pan-soft wvsu-messenger-page__shell flex-grow-1 d-flex flex-column wvsu-messenger" data-io-animate>
    <?php if ($flashNotice !== ''): ?>
        <div class="alert alert-success border-0 rounded-4 shadow-sm py-3"><?= htmlspecialchars($flashNotice) ?></div>
    <?php endif; ?>
    <?php if ($flashErr !== ''): ?>
        <div class="alert alert-warning border-0 rounded-4 shadow-sm py-3"><?= htmlspecialchars($flashErr) ?></div>
    <?php endif; ?>
    <?php if (! empty($mutualCrossTrade) && $selected_conv > 0): ?>
        <div class="alert alert-info border-0 rounded-4 shadow-sm py-3 small" role="status">
            <i class="bi bi-arrow-left-right me-2" aria-hidden="true"></i>
            You’re dealing as <strong>seller ↔ buyer</strong> on different listings — <strong>Complete sale</strong> or <strong>Done service</strong> only appears when <strong>your</strong> listing in the strip is what this chat is about. Use separate chats for each item or service.
        </div>
    <?php endif; ?>

    <div class="wvsu-messenger wvsu-messenger-shell overflow-hidden flex-grow-1 d-flex flex-column flex-md-row border border-secondary-subtle bg-white">
        <aside class="wvsu-messenger-sidebar flex-shrink-0 border-end border-secondary-subtle">
            <div class="py-3 border-bottom border-secondary-subtle bg-light-subtle wvsu-messenger-edge-pad">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <h1 class="h5 fw-bold mb-0">Messages</h1>
                        <span class="text-muted small">Campus buyer ↔ seller inbox</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#wvsuConvDrawer" aria-controls="wvsuConvDrawer">
                        <i class="bi bi-chat-left-text"></i>
                    </button>
                </div>
            </div>
            <div class="wvsu-messenger-threadlist d-none d-md-block overflow-auto">
                <?php if (empty($convs)): ?>
                    <div class="p-4 text-muted small">No conversations yet. Contact a seller from a listing.</div>
                <?php else: ?>
                    <?php foreach ($convs as $c):
                        $cid = (int) $c['conversation_id'];
                        $otherId = ((int) $c['participant_a'] === $me) ? (int) $c['participant_b'] : (int) $c['participant_a'];
                        $otherNm = ((int) $c['participant_a'] === $me) ? $c['b_name'] : $c['a_name'];
                        $otherUt = ((int) $c['participant_a'] === $me)
                            ? (string) ($c['b_updated'] ?? '')
                            : (string) ($c['a_updated'] ?? '');
                        $av = htmlspecialchars(wvsu_user_avatar_img_src($otherId, $otherUt), ENT_QUOTES, 'UTF-8');
                        $active = $cid === $selected_conv;
                        $unread = (int) ($c['unread_cnt'] ?? 0);
                        $pt = (string) ($c['preview_type'] ?? 'text');
                        $ptext = trim((string) ($c['preview_text'] ?? ''));
                        $snippet = $pt === 'image' ? '📷 Photo' : ($ptext !== '' ? $ptext : 'New chat');
                        $snippet = function_exists('mb_substr')
                            ? (string) mb_substr($snippet, 0, 72)
                            : (string) substr($snippet, 0, 72);
                        ?>
                        <a href="messages.php?conv=<?= $cid ?>" class="wvsu-messenger-thread <?= $active ? 'is-active' : '' ?>">
                            <img src="<?= $av ?>" alt="" class="wvsu-messenger-thread__avatar rounded-circle flex-shrink-0" width="48" height="48">
                            <div class="flex-grow-1 min-width-0">
                                <?php $threadListingTitle = trim((string) ($c['listing_thread_title'] ?? '')); ?>
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <span class="fw-semibold text-dark text-truncate"><?= htmlspecialchars((string) $otherNm) ?></span>
                                    <?php if ($unread > 0): ?>
                                        <span class="badge rounded-pill text-bg-primary flex-shrink-0"><?= $unread > 9 ? '9+' : $unread ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($threadListingTitle !== ''): ?>
                                    <?php
                                    $tlt = strtolower((string) ($c['listing_thread_type'] ?? ''));
                                    $ticon = $tlt === 'service' ? 'bi-palette2' : 'bi-bag-fill';
                                    ?>
                                    <div class="wvsu-messenger-thread__listing text-truncate small mb-1">
                                        <i class="bi <?= $ticon ?>" aria-hidden="true"></i>
                                        <?= htmlspecialchars($threadListingTitle) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="small text-muted text-truncate"><?= htmlspecialchars($snippet) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="wvsuConvDrawer">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title fw-bold">Conversations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body p-0 overflow-auto">
                <?php if (empty($convs)): ?>
                    <div class="p-4 text-muted small">No conversations yet.</div>
                <?php endif; ?>
                <?php foreach ($convs as $c):
                    $cid = (int) $c['conversation_id'];
                    $otherId = ((int) $c['participant_a'] === $me) ? (int) $c['participant_b'] : (int) $c['participant_a'];
                    $otherNm = ((int) $c['participant_a'] === $me) ? $c['b_name'] : $c['a_name'];
                    $otherUt = ((int) $c['participant_a'] === $me)
                        ? (string) ($c['b_updated'] ?? '')
                        : (string) ($c['a_updated'] ?? '');
                    $av = htmlspecialchars(wvsu_user_avatar_img_src($otherId, $otherUt), ENT_QUOTES, 'UTF-8');
                    $active = $cid === $selected_conv;
                    $unread = (int) ($c['unread_cnt'] ?? 0);
                    $pt = (string) ($c['preview_type'] ?? 'text');
                    $ptext = trim((string) ($c['preview_text'] ?? ''));
                    $snippet = $pt === 'image' ? '📷 Photo' : ($ptext !== '' ? $ptext : 'New chat');
                    ?>
                    <?php
                    $threadListingTitleOc = trim((string) ($c['listing_thread_title'] ?? ''));
                    $tltOc = strtolower((string) ($c['listing_thread_type'] ?? ''));
                    $ticonOc = $tltOc === 'service' ? 'bi-palette2' : 'bi-bag-fill';
                    ?>
                    <a href="messages.php?conv=<?= $cid ?>" class="wvsu-messenger-thread <?= $active ? 'is-active' : '' ?>" data-bs-dismiss="offcanvas">
                        <img src="<?= $av ?>" alt="" class="wvsu-messenger-thread__avatar rounded-circle flex-shrink-0" width="48" height="48">
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <span class="fw-semibold text-dark text-truncate"><?= htmlspecialchars((string) $otherNm) ?></span>
                                <?php if ($unread > 0): ?>
                                    <span class="badge rounded-pill text-bg-primary flex-shrink-0"><?= $unread > 9 ? '9+' : $unread ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($threadListingTitleOc !== ''): ?>
                                <div class="wvsu-messenger-thread__listing text-truncate small mb-1">
                                    <i class="bi <?= $ticonOc ?>" aria-hidden="true"></i>
                                    <?= htmlspecialchars($threadListingTitleOc) ?>
                                </div>
                            <?php endif; ?>
                            <div class="small text-muted text-truncate"><?= htmlspecialchars(function_exists('mb_substr') ? mb_substr($snippet, 0, 72) : substr($snippet, 0, 72)) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <main class="wvsu-messenger-main flex-grow-1 d-flex flex-column min-width-0 bg-light-subtle">
            <?php if ($selected_conv <= 0): ?>
                <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-center p-5">
                    <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mb-3" style="width:88px;height:88px;">
                        <i class="bi bi-chat-heart fs-1"></i>
                    </div>
                    <h2 class="h5 fw-bold">Pick a conversation</h2>
                    <p class="text-muted small mb-0" style="max-width:22rem;">Open a chat from the left, or message someone from a product or service listing.</p>
                </div>
            <?php else: ?>
                <header class="wvsu-messenger-header border-bottom bg-white py-3 wvsu-messenger-edge-pad">
                    <div class="d-flex align-items-center gap-3">
                        <a href="profile.php?id=<?= $other_user_id ?>" class="flex-shrink-0 rounded-circle wvsu-messenger-header__avatar-ring" title="View profile">
                            <img src="<?= $theirAvatar ?>" alt="" class="rounded-circle wvsu-messenger-header__avatar-img d-md-none" width="44" height="44">
                            <img src="<?= $theirAvatar ?>" alt="" class="rounded-circle wvsu-messenger-header__avatar-img d-none d-md-block" width="48" height="48">
                        </a>
                        <div class="min-width-0 flex-grow-1">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <a href="profile.php?id=<?= $other_user_id ?>" class="h5 fw-bold mb-0 text-dark text-decoration-none text-truncate wvsu-messenger-header__title"><?= htmlspecialchars($other_name) ?></a>
                                <?php if ($headerListingTitle !== ''): ?>
                                    <span class="badge rounded-pill text-bg-secondary-subtle text-secondary-emphasis fw-normal text-truncate wvsu-messenger-header__badge">
                                        <?= htmlspecialchars($headerListingTitle) ?>
                                    </span>
                                <?php endif; ?>
                                <?php
                                $hdrSold = $listingContext !== null && ! empty($listingContext['is_sold_out']);
                                $hdrBad = $listingContext !== null && ! empty($listingContext['is_unavailable']);
                                ?>
                                <?php if ($hdrSold): ?>
                                    <span class="badge rounded-pill text-bg-danger-subtle text-danger-emphasis fw-semibold">Sold out</span>
                                <?php elseif ($hdrBad): ?>
                                    <span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis fw-semibold">Unavailable</span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted wvsu-messenger-header__subtitle">Wildcat marketplace · Messages stay on-platform</div>
                        </div>
                        <button class="btn btn-sm btn-outline-danger rounded-pill flex-shrink-0" type="button" data-bs-toggle="collapse" data-bs-target="#reportChatUser" aria-expanded="false" title="Report user">
                            <i class="bi bi-flag-fill"></i>
                        </button>
                    </div>
                    <div id="reportChatUser" class="collapse mt-3">
                        <form method="post" action="process-report.php" class="card card-body border-danger-subtle rounded-4">
                            <input type="hidden" name="target_user_id" value="<?= $other_user_id ?>">
                            <input type="hidden" name="conversation_id" value="<?= $selected_conv ?>">
                            <input type="hidden" name="return_to" value="messages.php?conv=<?= $selected_conv ?>">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Reason</label>
                                    <select name="reason_type" class="form-select form-select-sm" required>
                                        <option value="scam">Scam / fake payment</option>
                                        <option value="harassment">Harassment / abuse</option>
                                        <option value="fake_profile">Fake profile</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold">Details</label>
                                    <input type="text" name="details" class="form-control form-control-sm" maxlength="300" placeholder="What happened?">
                                </div>
                            </div>
                            <div class="mt-2"><button class="btn btn-sm btn-danger rounded-pill">Submit report</button></div>
                        </form>
                    </div>
                </header>

                <?php if ($listingContext !== null): ?>
                    <?php
                    $lcImg = $listingContext['image_url'];
                    $lcImgEsc = htmlspecialchars($lcImg, ENT_QUOTES, 'UTF-8');
                    $lcTitleEsc = htmlspecialchars($listingContext['title'], ENT_QUOTES, 'UTF-8');
                    $lcHrefEsc = htmlspecialchars($listingContext['href'], ENT_QUOTES, 'UTF-8');
                    $listingStripHeadEsc = htmlspecialchars($listingStripHeadline, ENT_QUOTES, 'UTF-8');
                    $lcTypeLbl = $listingContext['listing_type'] === 'service' ? 'Service' : 'Product';
                    $lcEyebrow = $listingContext['listing_type'] === 'service'
                        ? 'Current service in this chat'
                        : 'Current product in this chat';
                    $lcSold = ! empty($listingContext['is_sold_out']);
                    $lcUnavail = ! empty($listingContext['is_unavailable']);
                    $lcStock = $listingContext['stock'];
                    if ($lcSold) {
                        $lcHint = 'This product is sold out — you can still message about meet-ups or browse other listings.';
                    } elseif ($lcUnavail) {
                        $lcHint = 'This listing is no longer active on the marketplace.';
                    } elseif ($listingContext['listing_type'] === 'service') {
                        $lcHint = 'Open the listing for rates, portfolio, and how to book this service.';
                    } else {
                        $lcHint = 'Open the listing for price, photos, and meet-up tips.';
                    }
                    $lcStockLine = '';
                    if (
                        ! $lcSold
                        && ! $lcUnavail
                        && $listingContext['listing_type'] === 'product'
                        && $lcStock !== null
                    ) {
                        $lcStockLine = 'Stock available: ' . (int) $lcStock;
                    }
                    ?>
                    <section class="wvsu-messenger-listing-strip border-bottom border-secondary-subtle py-2 wvsu-messenger-edge-pad" aria-labelledby="wvsuListingStripLabel">
                        <h2 id="wvsuListingStripLabel" class="visually-hidden">Listing referenced in this chat</h2>
                        <div class="wvsu-messenger-listing-strip__inner">
                            <?php if ($lcImg !== ''): ?>
                                <a href="<?= $lcHrefEsc ?>" class="wvsu-messenger-listing-strip__thumb flex-shrink-0 rounded-3 overflow-hidden border border-secondary-subtle shadow-sm<?= $lcSold ? ' opacity-50' : '' ?>" title="View listing">
                                    <img src="<?= $lcImgEsc ?>" alt="" class="w-100 h-100 object-fit-cover" width="64" height="64" loading="lazy">
                                </a>
                            <?php else: ?>
                                <div class="wvsu-messenger-listing-strip__thumb wvsu-messenger-listing-strip__thumb--empty flex-shrink-0 rounded-3 border border-secondary-subtle d-flex align-items-center justify-content-center bg-white" aria-hidden="true">
                                    <i class="bi bi-image text-secondary fs-4"></i>
                                </div>
                            <?php endif; ?>
                            <div class="min-width-0 flex-grow-1 wvsu-messenger-listing-strip__body">
                                <p class="small text-uppercase text-muted fw-bold mb-1 wvsu-messenger-listing-strip__eyebrow" style="letter-spacing:.04em;font-size:0.65rem;"><?= htmlspecialchars($lcEyebrow, ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="small fw-semibold text-primary mb-0"><?= $listingStripHeadEsc ?></p>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1 mt-1">
                                    <span class="badge rounded-pill text-bg-light border border-secondary-subtle fw-normal"><?= htmlspecialchars($lcTypeLbl) ?></span>
                                    <?php if ($lcSold): ?>
                                        <span class="badge rounded-pill text-bg-danger text-white fw-semibold"><i class="bi bi-bag-check-fill me-1" aria-hidden="true"></i>Sold out</span>
                                    <?php elseif ($lcUnavail): ?>
                                        <span class="badge rounded-pill text-bg-warning text-dark fw-semibold">Unavailable</span>
                                    <?php endif; ?>
                                    <p class="fw-bold text-dark mb-0 text-truncate"><?= $lcTitleEsc ?></p>
                                </div>
                                <p class="small text-muted mb-0 lh-sm"><?= htmlspecialchars($lcHint, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if ($lcStockLine !== ''): ?>
                                    <p class="small fw-semibold text-body-secondary mb-0 mt-1"><?= htmlspecialchars($lcStockLine, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="wvsu-messenger-listing-strip__actions flex-shrink-0">
                                <a href="<?= $lcHrefEsc ?>" class="btn btn-sm btn-primary rounded-pill fw-semibold d-inline-flex align-items-center justify-content-center wvsu-messenger-listing-strip__btn">
                                    View listing <i class="bi bi-arrow-up-right ms-1" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($selected_conv > 0 && ($canCancelPendingSale || (! $saleFeedbackPending && ! $is_closed))): ?>
                    <section class="border-bottom border-secondary-subtle bg-white py-2 px-3 wvsu-messenger-edge-pad" aria-label="Chat actions">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <span class="small text-muted fw-semibold mb-0">Chat actions</span>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <?php if ($canCancelPendingSale): ?>
                                    <form method="post" action="process-cancel-pending-sale.php" class="mb-0">
                                        <input type="hidden" name="conv_id" value="<?= (int) $selected_conv ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill fw-semibold"
                                            onclick="return confirm('Cancel this pending sale? Stock goes back on the seller’s product (if it was reduced), and you can message freely again.');">
                                            Cancel transaction
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if (! $saleFeedbackPending && ! $is_closed): ?>
                                    <form method="post" action="messages.php?conv=<?= (int) $selected_conv ?>" class="mb-0">
                                        <input type="hidden" name="conv_id" value="<?= (int) $selected_conv ?>">
                                        <input type="hidden" name="wvsu_end_chat" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill fw-semibold"
                                            onclick="return confirm('End this chat for now? You can use Message again later to keep talking.');">
                                            End chat
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <div id="wvsuMsgScroll" class="wvsu-messenger-scroll wvsu-messenger-edge-pad flex-grow-1 overflow-auto py-3">
                    <?php if (empty($messages)): ?>
                        <div class="wvsu-messenger-empty text-center text-muted py-5 px-3">
                            <div class="wvsu-messenger-empty__circle rounded-circle bg-primary-subtle text-primary mx-auto mb-3 d-inline-flex align-items-center justify-content-center"><i class="bi bi-send fs-4"></i></div>
                            <p class="fw-semibold text-dark mb-1">Say hello</p>
                            <p class="small mb-0">Kick off with a polite intro or ask about the listing strip above.</p>
                        </div>
                    <?php else: ?>
                        <div class="wvsu-messenger-bubbles pb-2">
                            <?php
                            $mc = count($messages);
                            foreach ($messages as $idx => $m):
                                $sid = (int) $m['sender_id'];
                                $mine = ($sid === $me);
                                $mid = (int) $m['message_id'];
                                $mtype = (string) ($m['message_type'] ?? 'text');
                                $samePrev = $idx > 0 && (int) $messages[$idx - 1]['sender_id'] === $sid;
                                $sameNext = ($idx + 1) < $mc && (int) $messages[$idx + 1]['sender_id'] === $sid;
                                $rowCls = $mine ? 'wvsu-msg-row wvsu-msg-row--me' : 'wvsu-msg-row wvsu-msg-row--them';
                                if ($samePrev) {
                                    $rowCls .= ' wvsu-msg-row--stack';
                                }
                                $bubbleCls = 'wvsu-messenger-bubble shadow-sm ' . ($mine ? 'wvsu-messenger-bubble--me' : 'wvsu-messenger-bubble--them');
                                if ($mine) {
                                    if ($samePrev) {
                                        $bubbleCls .= ' wvsu-messenger-bubble--me-stack-prev';
                                    }
                                    if ($sameNext) {
                                        $bubbleCls .= ' wvsu-messenger-bubble--me-stack-next';
                                    }
                                } else {
                                    if ($samePrev) {
                                        $bubbleCls .= ' wvsu-messenger-bubble--them-stack-prev';
                                    }
                                    if ($sameNext) {
                                        $bubbleCls .= ' wvsu-messenger-bubble--them-stack-next';
                                    }
                                }
                                $showTheirAvatar = ! $mine && ! $samePrev;
                                $showMyAvatar = $mine && ! $sameNext;
                                $showReadReceiptOutbound = false;
                                if ($mine && (int) ($m['is_read'] ?? 0) === 1) {
                                    $nextSid = ($idx + 1) < $mc ? (int) ($messages[$idx + 1]['sender_id'] ?? 0) : -1;
                                    $showReadReceiptOutbound = ($idx >= $mc - 1 || $nextSid !== $me);
                                }
                                ?>
                                <div class="wvsu-msg-slot">
                                    <?php if (! $mine && ! $samePrev): ?>
                                        <div class="wvsu-msg-cluster-head"><?= htmlspecialchars((string) $m['full_name']) ?></div>
                                    <?php endif; ?>
                                    <div class="<?= htmlspecialchars($rowCls) ?>">
                                    <?php if (! $mine): ?>
                                        <?php if ($showTheirAvatar): ?>
                                            <a href="profile.php?id=<?= $other_user_id ?>" class="wvsu-msg-pfp-link flex-shrink-0" title="<?= htmlspecialchars((string) $other_name, ENT_QUOTES, 'UTF-8') ?>">
                                                <img src="<?= $theirAvatar ?>" alt="" class="wvsu-msg-pfp rounded-circle" width="36" height="36" loading="lazy">
                                            </a>
                                        <?php else: ?>
                                            <span class="wvsu-msg-pfp-spacer" aria-hidden="true"></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($mine): ?>
                                    <div class="<?= htmlspecialchars(trim($bubbleCls)) ?>">
                                            <?php if ($mtype === 'image' && ! empty($m['image_url'])): ?>
                                                <a href="message_media.php?m=<?= $mid ?>" target="_blank" rel="noopener" class="d-block">
                                                    <img src="message_media.php?m=<?= $mid ?>" alt="Attachment" class="wvsu-messenger-bubble__img rounded-3" loading="lazy">
                                                </a>
                                            <?php endif; ?>
                                            <?php if (trim((string) $m['content']) !== ''): ?>
                                                <div class="wvsu-messenger-bubble__text"><?= nl2br(htmlspecialchars((string) $m['content'])) ?></div>
                                            <?php endif; ?>
                                            <div class="wvsu-messenger-bubble__meta small opacity-75 mt-1"><?= date('g:i A', strtotime((string) $m['sent_at'])) ?></div>
                                            <?php if ($showReadReceiptOutbound): ?>
                                                <div class="wvsu-messenger-read-receipt small mt-1">
                                                    <i class="bi bi-check2-all" aria-hidden="true"></i><span><?= htmlspecialchars($seenReadReceiptPhrase, ENT_QUOTES, 'UTF-8') ?></span></div>
                                            <?php endif; ?>
                                    </div>
                                        <?php if ($showMyAvatar): ?>
                                            <a href="profile.php?id=<?= $me ?>" class="wvsu-msg-pfp-link flex-shrink-0" title="You">
                                                <img src="<?= $myAvatar ?>" alt="" class="wvsu-msg-pfp rounded-circle wvsu-msg-pfp--sent" width="36" height="36" loading="lazy">
                                            </a>
                                        <?php else: ?>
                                            <span class="wvsu-msg-pfp-spacer wvsu-msg-pfp-spacer--sent" aria-hidden="true"></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <div class="<?= htmlspecialchars(trim($bubbleCls)) ?>">
                                            <?php if ($mtype === 'image' && ! empty($m['image_url'])): ?>
                                                <a href="message_media.php?m=<?= $mid ?>" target="_blank" rel="noopener" class="d-block">
                                                    <img src="message_media.php?m=<?= $mid ?>" alt="Attachment" class="wvsu-messenger-bubble__img rounded-3" loading="lazy">
                                                </a>
                                            <?php endif; ?>
                                            <?php if (trim((string) $m['content']) !== ''): ?>
                                                <div class="wvsu-messenger-bubble__text"><?= nl2br(htmlspecialchars((string) $m['content'])) ?></div>
                                            <?php endif; ?>
                                            <div class="wvsu-messenger-bubble__meta small opacity-75 mt-1"><?= date('g:i A', strtotime((string) $m['sent_at'])) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="composer" class="wvsu-messenger-compose border-top bg-white py-2 py-md-3 wvsu-messenger-edge-pad">
                    <?php if ($saleFeedbackPending && $me === $pendingBuyerId): ?>
                        <div class="rounded-4 border border-primary-subtle bg-primary-subtle bg-opacity-10 py-4 px-3">
                            <h3 class="h6 fw-bold text-primary mb-2"><i class="bi bi-star-half me-2" aria-hidden="true"></i>Rate <?= htmlspecialchars((string) $other_name, ENT_QUOTES, 'UTF-8') ?></h3>
                            <?php if (! empty($pendingListingIsService)): ?>
                                <p class="small text-muted mb-3">The freelancer marked this service as done. Your feedback and a photo will appear on <a href="profile.php?id=<?= (int) $other_user_id ?>">their profile</a> and on this service’s page before you can message about another booking.</p>
                            <?php else: ?>
                                <p class="small text-muted mb-3">The seller marked this sale complete. Your feedback and a photo will appear on <a href="profile.php?id=<?= (int) $other_user_id ?>">their profile</a> before you can message about another purchase.</p>
                            <?php endif; ?>
                            <form method="post" action="process-sale-feedback.php" enctype="multipart/form-data" class="vstack gap-3">
                                <input type="hidden" name="conv_id" value="<?= (int) $selected_conv ?>">
                                <div>
                                    <label class="form-label small fw-semibold">Star rating</label>
                                    <select name="rating" class="form-select form-select-sm rounded-pill" style="max-width:12rem;" required>
                                        <option value="" disabled selected>Choose…</option>
                                        <?php for ($ri = 5; $ri >= 1; $ri--): ?>
                                            <option value="<?= $ri ?>"><?= $ri ?> star<?= $ri === 1 ? '' : 's' ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold">Written feedback</label>
                                    <textarea name="comment" class="form-control rounded-3" rows="3" maxlength="2000" placeholder="<?= ! empty($pendingListingIsService) ? 'How did the session or deliverable go?' : 'How did the meet-up or item go?' ?>" required></textarea>
                                </div>
                                <div>
                                    <label class="form-label small fw-semibold">Photo <span class="text-danger">(required)</span></label>
                                    <input type="file" name="review_photo" class="form-control form-control-sm rounded-3" accept="image/jpeg,image/png,image/webp,image/gif" required>
                                    <div class="form-text">JPG, PNG, WebP, or GIF · max 6MB — appears on <?= ! empty($pendingListingIsService) ? 'the freelancer’s profile and this service’s page' : 'the seller’s profile and this product’s page' ?>. Reviews can’t be edited after you submit.</div>
                                </div>
                                <button type="submit" class="btn btn-primary rounded-pill fw-semibold align-self-start px-4">
                                    <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Submit feedback
                                </button>
                            </form>
                        </div>
                    <?php elseif ($saleFeedbackPending): ?>
                        <div class="rounded-4 bg-light border border-secondary-subtle py-4 px-3 text-center">
                            <p class="text-muted small mb-0">Waiting for the buyer to leave a star rating, comment, and photo on your profile. <strong><?= ! empty($pendingListingIsService) ? 'Done service' : 'Complete sale' ?></strong> stays hidden until then so you can use it again for the next <?= ! empty($pendingListingIsService) ? 'booking' : 'purchase' ?>.</p>
                        </div>
                    <?php elseif (! empty($is_closed)): ?>
                        <div class="rounded-4 bg-light border border-secondary-subtle py-4 px-3 text-center">
                            <p class="text-muted small mb-3 mb-md-4">This chat was closed after the sale wrapped up — you can still reach each other.</p>
                            <form method="post" action="messages.php?conv=<?= $selected_conv ?>" class="d-inline-block">
                                <input type="hidden" name="conv_id" value="<?= $selected_conv ?>">
                                <input type="hidden" name="wvsu_reopen_conversation" value="1">
                                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                                    <i class="bi bi-chat-dots-fill me-2" aria-hidden="true"></i>Message again
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="messages.php?conv=<?= $selected_conv ?>" enctype="multipart/form-data" class="d-flex flex-column gap-2 w-100 wvsu-messenger-compose-inner">
                            <input type="hidden" name="conv_id" value="<?= $selected_conv ?>">
                            <div id="wvsuAttachPreview" class="d-none rounded-3 overflow-hidden border border-secondary-subtle bg-light align-self-start" style="max-width:180px;">
                                <img src="" alt="" id="wvsuAttachPreviewImg" class="w-100 object-fit-cover" style="max-height:140px;">
                            </div>
                            <div class="wvsu-messenger-compose__bar rounded-4 border border-secondary-subtle bg-white shadow-sm px-2 py-2 px-md-3">
                                <a href="profile.php?id=<?= $me ?>" class="flex-shrink-0 d-none d-sm-block rounded-circle overflow-hidden align-self-center wvsu-messenger-compose__me" title="Your profile"><img src="<?= $myAvatar ?>" alt="" width="40" height="40" class="rounded-circle object-fit-cover"></a>
                                <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,image/gif" class="visually-hidden" id="wvsuAttachInput" autocomplete="off">
                                <label for="wvsuAttachInput" class="btn btn-light border-secondary-subtle rounded-circle mb-0 flex-shrink-0 wvsu-messenger-compose__attach d-flex align-items-center justify-content-center" title="Attach image" aria-label="Attach image">
                                    <i class="bi bi-image-fill text-secondary"></i>
                                </label>
                                <textarea name="content" class="form-control border-0 bg-transparent rounded-4 flex-grow-1 shadow-none wvsu-messenger-compose__input" rows="2" placeholder="Message <?= htmlspecialchars((string) $other_name, ENT_QUOTES, 'UTF-8') ?>…"></textarea>
                                <button type="submit" class="btn btn-primary rounded-pill px-md-4 fw-semibold flex-shrink-0 wvsu-messenger-send">
                                    <i class="bi bi-send-fill"></i><span class="d-none d-md-inline ms-1">Send</span>
                                </button>
                            </div>
                            <div class="wvsu-messenger-compose__hint small text-muted">Photos: JPG · PNG · WebP · GIF · max 6MB</div>
                        </form>
                    <?php endif; ?>
                </div>

                <?php
                if ($sellerCompleteListingRow !== null
                    && (int) ($sellerCompleteListingRow['owner_id'] ?? 0) === $me
                    && (int) ($sellerCompleteListingRow['stock'] ?? 0) > 0) {
                    $slTitleRaw = trim((string) ($sellerCompleteListingRow['title'] ?? ''));
                    $slTitleLen = function_exists('mb_strlen') ? (int) mb_strlen($slTitleRaw) : strlen($slTitleRaw);
                    $slTitleBriefRaw = ($slTitleLen > 52)
                        ? (((function_exists('mb_substr') ? mb_substr($slTitleRaw, 0, 52) : substr($slTitleRaw, 0, 52)) ?: '') . '…')
                        : $slTitleRaw;
                    $slTitleBrief = htmlspecialchars($slTitleBriefRaw !== '' ? $slTitleBriefRaw : 'Untitled listing', ENT_QUOTES, 'UTF-8');
                    ?>
                        <div class="pb-3 pt-2 bg-white border-top border-secondary-subtle wvsu-messenger-edge-pad">
                            <div class="small text-muted mb-2 fw-semibold">Complete sale · listing in this chat <?= $slTitleBrief ?></div>
                            <form method="POST" action="complete_transaction.php" class="row g-2 align-items-center">
                                <div class="col-auto small fw-semibold text-muted text-nowrap">Seller</div>
                                <input type="hidden" name="conv_id" value="<?= $selected_conv ?>">
                                <input type="hidden" name="listing_id" value="<?= (int) $sellerCompleteListingRow['listing_id'] ?>">
                                <div class="col-auto">
                                    <input type="number" name="quantity" value="1" min="1" max="<?= (int) $sellerCompleteListingRow['stock'] ?>" class="form-control form-control-sm rounded-pill" style="width:5rem;" title="Quantity">
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-warning btn-sm rounded-pill fw-semibold" type="submit">Complete sale</button>
                                </div>
                            </form>
                            <div class="small text-muted mt-2 mb-0">Shown only for the active product in the strip above (same chat link, last 6 weeks). Buyers never see this — use only after handover or a clear on-campus agreement.</div>
                        </div>
                    <?php
                }
                if ($sellerServiceDoneRow !== null
                    && (int) ($sellerServiceDoneRow['owner_id'] ?? 0) === $me) {
                    $svTitleRaw = trim((string) ($sellerServiceDoneRow['title'] ?? ''));
                    $svTitleLen = function_exists('mb_strlen') ? (int) mb_strlen($svTitleRaw) : strlen($svTitleRaw);
                    $svTitleBriefRaw = ($svTitleLen > 52)
                        ? (((function_exists('mb_substr') ? mb_substr($svTitleRaw, 0, 52) : substr($svTitleRaw, 0, 52)) ?: '') . '…')
                        : $svTitleRaw;
                    $svTitleBrief = htmlspecialchars($svTitleBriefRaw !== '' ? $svTitleBriefRaw : 'Untitled service', ENT_QUOTES, 'UTF-8');
                    ?>
                        <div class="pb-3 pt-2 bg-white border-top border-secondary-subtle wvsu-messenger-edge-pad">
                            <div class="small text-muted mb-2 fw-semibold">Done service · listing in this chat <?= $svTitleBrief ?></div>
                            <form method="POST" action="complete_service.php" class="row g-2 align-items-center">
                                <div class="col-auto small fw-semibold text-muted text-nowrap">Freelancer</div>
                                <input type="hidden" name="conv_id" value="<?= $selected_conv ?>">
                                <input type="hidden" name="listing_id" value="<?= (int) $sellerServiceDoneRow['listing_id'] ?>">
                                <div class="col-auto">
                                    <button class="btn btn-info btn-sm rounded-pill fw-semibold text-white" type="submit">Done service</button>
                                </div>
                            </form>
                            <div class="small text-muted mt-2 mb-0">Use when the session or deliverable is finished. The buyer will be asked for a rating, comment, and photo before chatting freely again.</div>
                        </div>
                    <?php
                }
                ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var box = document.getElementById('wvsuMsgScroll');
    if (box) box.scrollTop = box.scrollHeight;
    var ta = document.querySelector('#composer textarea[name="content"]');
    if (ta && typeof ta.focus === 'function') {
        try {
            ta.focus({ preventScroll: true });
        } catch (e) {
            ta.focus();
        }
    }
    var inp = document.getElementById('wvsuAttachInput');
    var prev = document.getElementById('wvsuAttachPreview');
    var prevImg = document.getElementById('wvsuAttachPreviewImg');
    if (!inp || !prev || !prevImg) return;
    inp.addEventListener('change', function () {
        var f = inp.files && inp.files[0];
        if (!f || !/^image\//.test(f.type)) {
            prev.classList.add('d-none');
            prevImg.removeAttribute('src');
            return;
        }
        prevImg.src = URL.createObjectURL(f);
        prev.classList.remove('d-none');
    });
})();
</script>
</body>
</html>

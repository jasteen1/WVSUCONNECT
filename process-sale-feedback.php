<?php
declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/profiles_reviews.inc.php';
require_once __DIR__ . '/messaging_schema.inc.php';
require_once __DIR__ . '/wvsu_upload_dirs.inc.php';

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
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim((string) ($_POST['comment'] ?? ''));

if ($conv <= 0 || $rating < 1 || $rating > 5) {
    header('Location: messages.php?error=sale_feedback_invalid');
    exit;
}

wvsu_conversation_meta_ensure_sale_feedback_columns($master_conn);

$meta = fetch_master(
    'SELECT is_closed, pending_sale_buyer_id, pending_sale_listing_id FROM conversation_meta WHERE conversation_id = ? LIMIT 1',
    [(string) $conv]
);
$pendingBuyer = ($meta !== null && array_key_exists('pending_sale_buyer_id', $meta))
    ? (int) $meta['pending_sale_buyer_id']
    : 0;
if ($pendingBuyer !== $me) {
    $stamp = $_SESSION['wvsu_sale_feedback_ok'] ?? null;
    if (
        is_array($stamp)
        && (int) ($stamp['conv'] ?? 0) === $conv
        && (int) ($stamp['buyer'] ?? 0) === $me
        && (time() - (int) ($stamp['t'] ?? 0)) <= 180
    ) {
        unset($_SESSION['wvsu_sale_feedback_ok']);
        header('Location: messages.php?conv=' . $conv . '&notice=sale_feedback_saved');
        exit;
    }
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_not_pending');
    exit;
}

$listingId = (int) ($meta['pending_sale_listing_id'] ?? 0);
if ($listingId <= 0) {
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_invalid');
    exit;
}

$c = fetch_master(
    'SELECT participant_a, participant_b FROM conversations WHERE conversation_id = ? LIMIT 1',
    [(string) $conv]
);
if (! $c) {
    header('Location: messages.php?error=sale_feedback_invalid');
    exit;
}
$a = (int) $c['participant_a'];
$b = (int) $c['participant_b'];
if ($me !== $a && $me !== $b) {
    header('Location: messages.php?error=sale_feedback_invalid');
    exit;
}

$list = fetch_master(
    'SELECT listing_id, owner_id FROM listings WHERE listing_id = ? LIMIT 1',
    [(string) $listingId]
);
if (! $list) {
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_invalid');
    exit;
}
$sellerId = (int) ($list['owner_id'] ?? 0);
if ($sellerId <= 0 || $sellerId === $me) {
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_invalid');
    exit;
}

$hasFile = ! empty($_FILES['review_photo']['name'])
    && isset($_FILES['review_photo']['error'])
    && (int) $_FILES['review_photo']['error'] === UPLOAD_ERR_OK;
if (! $hasFile) {
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_photo_required');
    exit;
}

$tmp = (string) ($_FILES['review_photo']['tmp_name'] ?? '');
$orig = basename((string) ($_FILES['review_photo']['name'] ?? ''));
$ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
$allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
if (! in_array($ext, $allowedExt, true)) {
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_bad_image');
    exit;
}
if ($tmp === '' || ! is_uploaded_file($tmp)) {
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_upload');
    exit;
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmp) ?: '';
$allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (! in_array($mime, $allowedMime, true)) {
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_bad_image');
    exit;
}
if ((int) ($_FILES['review_photo']['size'] ?? 0) > 6 * 1024 * 1024) {
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_image_large');
    exit;
}

$dir = wvsu_ensure_writable_review_upload_dir(__DIR__ . '/review_upload_debug.log');
if ($dir === null) {
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_upload_dir');
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
    'r_%d_%d_%s_%s.%s',
    $conv,
    $me,
    (string) time(),
    bin2hex(random_bytes(4)),
    $safeExt
);
$dest = $dir . DIRECTORY_SEPARATOR . $newName;
if (! move_uploaded_file($tmp, $dest)) {
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_upload');
    exit;
}
@chmod($dest, 0666);
$photoUrl = 'uploads/reviews/' . $newName;

$comment = mb_substr($comment, 0, 2000, 'UTF-8');
if ($comment === '') {
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_comment_required');
    exit;
}

// Re-run schema fixes on master so INSERT always matches columns and legacy unique (reviewer, reviewee) is dropped.
wvsu_user_reviews_ensure_photo_and_indexes($master_conn);
wvsu_user_reviews_ensure_seller_reply_columns($master_conn);
wvsu_user_reviews_drop_pair_unique_if_present($master_conn);

/**
 * Row that blocks or should receive this sale’s feedback: exact listing match, or legacy profile row (listing NULL/0).
 * Ignores other listings from the same seller so we never overwrite a different product’s review.
 */
$wvsu_sale_feedback_find_merge_row = static function (int $reviewer, int $reviewee, int $lid) {
    return fetch_master(
        'SELECT review_id, listing_id, COALESCE(photo_url, \'\') AS photo_url
         FROM user_reviews
         WHERE reviewer_id = ? AND reviewee_id = ?
           AND (listing_id <=> ? OR listing_id IS NULL OR listing_id = 0)
         ORDER BY (listing_id <=> ?) DESC, review_id DESC
         LIMIT 1',
        [(string) $reviewer, (string) $reviewee, (string) $lid, (string) $lid]
    );
};

$row = $wvsu_sale_feedback_find_merge_row($me, $sellerId, $listingId);

/*
 * Commit the review in its own transaction first. If conversation finalize fails (meta row
 * missing, message insert, etc.), a single rolled-back transaction used to undo BOTH —
 * the buyer saw “saved” errors while `user_reviews` stayed empty.
 */
if (! $master_conn->begin_transaction()) {
    @unlink($dest);
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_db');
    exit;
}

try {
    if ($row) {
        $reviewId = (int) ($row['review_id'] ?? 0);
        if ($reviewId <= 0) {
            throw new RuntimeException('bad_review_id');
        }
        $u = $master_conn->prepare(
            'UPDATE user_reviews SET listing_id = ?, rating = ?, comment = ?, photo_url = ?, updated_at = CURRENT_TIMESTAMP WHERE review_id = ?'
        );
        if (! $u) {
            throw new RuntimeException('prepare_update');
        }
        $u->bind_param('iissi', $listingId, $rating, $comment, $photoUrl, $reviewId);
        if (! $u->execute()) {
            throw new RuntimeException($u->error !== '' ? $u->error : 'exec_update');
        }
        $u->close();
    } else {
        wvsu_user_reviews_drop_pair_unique_if_present($master_conn);
        $stmt = $master_conn->prepare(
            'INSERT INTO user_reviews (reviewer_id, reviewee_id, listing_id, rating, comment, photo_url)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (! $stmt) {
            throw new RuntimeException('prepare_insert');
        }
        $stmt->bind_param('iiiiss', $me, $sellerId, $listingId, $rating, $comment, $photoUrl);
        if (! $stmt->execute()) {
            $errno = $stmt->errno;
            $errmsg = $stmt->error;
            $stmt->close();
            @file_put_contents(
                __DIR__ . '/sale_feedback_debug.log',
                date('c') . " INSERT user_reviews errno={$errno} err={$errmsg}\n",
                FILE_APPEND
            );
            if ($errno === 1062) {
                wvsu_user_reviews_drop_pair_unique_if_present($master_conn);
                $row = $wvsu_sale_feedback_find_merge_row($me, $sellerId, $listingId);
                if ($row) {
                    $reviewId = (int) ($row['review_id'] ?? 0);
                    if ($reviewId <= 0) {
                        throw new RuntimeException('race_bad_review_id');
                    }
                    $u2 = $master_conn->prepare(
                        'UPDATE user_reviews SET listing_id = ?, rating = ?, comment = ?, photo_url = ?, updated_at = CURRENT_TIMESTAMP WHERE review_id = ?'
                    );
                    if (! $u2) {
                        throw new RuntimeException('prepare_update_race');
                    }
                    $u2->bind_param('iissi', $listingId, $rating, $comment, $photoUrl, $reviewId);
                    if (! $u2->execute()) {
                        throw new RuntimeException($u2->error !== '' ? $u2->error : 'exec_update_race');
                    }
                    $u2->close();
                } else {
                    $dupExact = fetch_master(
                        'SELECT review_id FROM user_reviews WHERE reviewer_id = ? AND reviewee_id = ? AND listing_id = ? LIMIT 1',
                        [(string) $me, (string) $sellerId, (string) $listingId]
                    );
                    if ($dupExact) {
                        $master_conn->rollback();
                        try {
                            wvsu_finalize_sale_feedback_conversation($conv, $me, $listingId);
                        } catch (Throwable $fe) {
                            @file_put_contents(
                                __DIR__ . '/sale_feedback_debug.log',
                                date('c') . ' finalize_after_dup ' . $fe->getMessage() . "\n",
                                FILE_APPEND
                            );
                            wvsu_sale_feedback_try_clear_pending_meta($master_conn, $conv);
                        }
                        $_SESSION['wvsu_sale_feedback_ok'] = ['conv' => $conv, 'buyer' => $me, 't' => time()];
                        header('Location: messages.php?conv=' . $conv . '&notice=sale_feedback_saved');
                        exit;
                    }
                    /*
                     * Legacy UNIQUE(reviewer, reviewee) + an existing row for another listing_id: INSERT hits 1062 but
                     * merge SELECT does not match that row. Upgrade the existing row to this sale’s listing + feedback.
                     */
                    $pairRow = fetch_master(
                        'SELECT review_id FROM user_reviews WHERE reviewer_id = ? AND reviewee_id = ? ORDER BY review_id DESC LIMIT 1',
                        [(string) $me, (string) $sellerId]
                    );
                    $pairRid = (int) ($pairRow['review_id'] ?? 0);
                    if ($pairRid <= 0) {
                        throw new RuntimeException('duplicate_no_row');
                    }
                    $u3 = $master_conn->prepare(
                        'UPDATE user_reviews SET listing_id = ?, rating = ?, comment = ?, photo_url = ?, updated_at = CURRENT_TIMESTAMP WHERE review_id = ?'
                    );
                    if (! $u3) {
                        throw new RuntimeException('prepare_update_pair');
                    }
                    $u3->bind_param('iissi', $listingId, $rating, $comment, $photoUrl, $pairRid);
                    if (! $u3->execute()) {
                        throw new RuntimeException($u3->error !== '' ? $u3->error : 'exec_update_pair');
                    }
                    $u3->close();
                }
            } else {
                throw new RuntimeException($errmsg !== '' ? $errmsg : 'exec_insert');
            }
        } else {
            $stmt->close();
        }
    }

    $master_conn->commit();
    if (method_exists($master_conn, 'autocommit')) {
        @$master_conn->autocommit(true);
    }
} catch (Throwable $e) {
    $master_conn->rollback();
    if (is_file($dest)) {
        @unlink($dest);
    }
    @file_put_contents(
        __DIR__ . '/sale_feedback_debug.log',
        date('c') . ' review_tx ' . $e->getMessage() . "\n",
        FILE_APPEND
    );
    if ($e->getMessage() === 'duplicate_no_row') {
        header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_duplicate');
        exit;
    }
    header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_db');
    exit;
}

try {
    wvsu_finalize_sale_feedback_conversation($conv, $me, $listingId);
} catch (Throwable $e) {
    @file_put_contents(
        __DIR__ . '/sale_feedback_debug.log',
        date('c') . ' finalize_after_review_commit ' . $e->getMessage() . "\n",
        FILE_APPEND
    );
    wvsu_sale_feedback_try_clear_pending_meta($master_conn, $conv);
}

$_SESSION['wvsu_sale_feedback_ok'] = ['conv' => $conv, 'buyer' => $me, 't' => time()];
header('Location: messages.php?conv=' . $conv . '&notice=sale_feedback_saved');
exit;

/**
 * Runs a write on $master_conn; throws on failure (avoids insert() die() inside transactions).
 *
 * @param list<string|int> $params
 */
function wvsu_sale_feedback_mysqli_exec(mysqli $conn, string $sql, array $params): void
{
    $stmt = $conn->prepare($sql);
    if (! $stmt) {
        throw new RuntimeException('prepare: ' . $conn->error);
    }
    if ($params !== []) {
        wvsu_mysqli_bind_params($stmt, $params);
    }
    if (! $stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new RuntimeException('exec: ' . $err);
    }
    $stmt->close();
}

/**
 * Clears pending-sale flags, reopens the thread, posts the thank-you system message (runs after review is committed).
 */
function wvsu_finalize_sale_feedback_conversation(int $convId, int $buyerId, int $listingId = 0): void
{
    global $master_conn;

    if (function_exists('wvsu_conversation_meta_ensure_sale_feedback_columns')) {
        wvsu_conversation_meta_ensure_sale_feedback_columns($master_conn);
    }

    $thanksLine = 'Buyer left feedback and a photo for this sale — thanks for trading on WVSU Connect.';
    if ($listingId > 0 && function_exists('fetch_master')) {
        $ltRow = fetch_master(
            'SELECT LOWER(TRIM(IFNULL(listing_type, \'product\'))) AS lt FROM listings WHERE listing_id = ? LIMIT 1',
            [(string) $listingId]
        );
        if ($ltRow && ($ltRow['lt'] ?? '') === 'service') {
            $thanksLine = 'Buyer left feedback and a photo for this service — thanks for trading on WVSU Connect.';
        }
    }

    wvsu_sale_feedback_mysqli_exec(
        $master_conn,
        'INSERT INTO conversation_meta (conversation_id, is_closed, pending_sale_buyer_id, pending_sale_listing_id, pending_sale_qty)
         VALUES (?, 0, NULL, NULL, 1)
         ON DUPLICATE KEY UPDATE
           is_closed = VALUES(is_closed),
           pending_sale_buyer_id = VALUES(pending_sale_buyer_id),
           pending_sale_listing_id = VALUES(pending_sale_listing_id),
           pending_sale_qty = VALUES(pending_sale_qty)',
        [(string) $convId]
    );

    wvsu_sale_feedback_mysqli_exec(
        $master_conn,
        'INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)',
        [
            (string) $convId,
            (string) $buyerId,
            $thanksLine,
        ]
    );
    wvsu_sale_feedback_mysqli_exec(
        $master_conn,
        'UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE conversation_id = ?',
        [(string) $convId]
    );
}

/**
 * Last resort: clear pending-sale flags so the buyer is not stuck (review row already committed).
 */
function wvsu_sale_feedback_try_clear_pending_meta(mysqli $conn, int $convId): void
{
    if ($convId <= 0) {
        return;
    }
    if (function_exists('wvsu_conversation_meta_ensure_sale_feedback_columns')) {
        wvsu_conversation_meta_ensure_sale_feedback_columns($conn);
    }
    $cid = (int) $convId;
    $sql = 'INSERT INTO conversation_meta (conversation_id, is_closed, pending_sale_buyer_id, pending_sale_listing_id, pending_sale_qty)
         VALUES (' . $cid . ', 0, NULL, NULL, 1)
         ON DUPLICATE KEY UPDATE
           is_closed = VALUES(is_closed),
           pending_sale_buyer_id = VALUES(pending_sale_buyer_id),
           pending_sale_listing_id = VALUES(pending_sale_listing_id),
           pending_sale_qty = VALUES(pending_sale_qty)';
    if (! @$conn->query($sql)) {
        @file_put_contents(
            __DIR__ . '/sale_feedback_debug.log',
            date('c') . ' try_clear_meta errno=' . (string) $conn->errno . ' ' . $conn->error . "\n",
            FILE_APPEND
        );
    }
}

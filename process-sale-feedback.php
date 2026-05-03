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
if (! $meta || (int) ($meta['pending_sale_buyer_id'] ?? 0) !== $me) {
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

$existingReview = fetch_master(
    'SELECT review_id, COALESCE(photo_url, \'\') AS photo_url FROM user_reviews
     WHERE reviewer_id = ? AND reviewee_id = ? AND listing_id = ? LIMIT 1',
    [(string) $me, (string) $sellerId, (string) $listingId]
);

if ($existingReview) {
    $reviewId = (int) ($existingReview['review_id'] ?? 0);
    $hasPhoto = trim((string) ($existingReview['photo_url'] ?? '')) !== '';
    if (! $master_conn->begin_transaction()) {
        header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_db');
        exit;
    }
    try {
        if (! $hasPhoto && $reviewId > 0) {
            $u = $master_conn->prepare(
                'UPDATE user_reviews SET rating = ?, comment = ?, photo_url = ?, updated_at = CURRENT_TIMESTAMP WHERE review_id = ?'
            );
            if (! $u) {
                throw new RuntimeException('prepare_update');
            }
            $u->bind_param('issi', $rating, $comment, $photoUrl, $reviewId);
            if (! $u->execute()) {
                throw new RuntimeException($u->error !== '' ? $u->error : 'exec_update');
            }
            $u->close();
        }
        wvsu_finalize_sale_feedback_conversation($conv, $me);
        $master_conn->commit();
    } catch (Throwable $e) {
        $master_conn->rollback();
        if (is_file($dest)) {
            @unlink($dest);
        }
        @file_put_contents(
            __DIR__ . '/sale_feedback_debug.log',
            date('c') . ' existing_review_path ' . $e->getMessage() . "\n",
            FILE_APPEND
        );
        header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_db');
        exit;
    }
    if ($hasPhoto) {
        @unlink($dest);
    }
    header('Location: messages.php?conv=' . $conv . '&notice=sale_feedback_saved');
    exit;
}

$insertAttempt = 0;
while ($insertAttempt < 2) {
    $insertAttempt++;
    if (! $master_conn->begin_transaction()) {
        header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_db');
        exit;
    }

    try {
        $stmt = $master_conn->prepare(
            'INSERT INTO user_reviews (reviewer_id, reviewee_id, listing_id, rating, comment, photo_url)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (! $stmt) {
            throw new RuntimeException('prepare');
        }
        $stmt->bind_param('iiiiss', $me, $sellerId, $listingId, $rating, $comment, $photoUrl);
        if (! $stmt->execute()) {
            $errno = $stmt->errno;
            $errmsg = $stmt->error;
            @file_put_contents(
                __DIR__ . '/sale_feedback_debug.log',
                date('c') . " INSERT user_reviews errno={$errno} err={$errmsg} attempt={$insertAttempt}\n",
                FILE_APPEND
            );
            $stmt->close();
            if ($errno === 1062 && $insertAttempt === 1) {
                $master_conn->rollback();
                @$master_conn->query('ALTER TABLE user_reviews DROP INDEX uq_reviewer_reviewee');
                wvsu_user_reviews_drop_pair_unique_if_present($master_conn);
                continue;
            }
            if ($errno === 1062 && $insertAttempt === 2) {
                $master_conn->rollback();
                $race = fetch_master(
                    'SELECT review_id FROM user_reviews WHERE reviewer_id = ? AND reviewee_id = ? AND listing_id = ? LIMIT 1',
                    [(string) $me, (string) $sellerId, (string) $listingId]
                );
                if ($race) {
                    if (! $master_conn->begin_transaction()) {
                        @unlink($dest);
                        header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_db');
                        exit;
                    }
                    try {
                        wvsu_finalize_sale_feedback_conversation($conv, $me);
                        $master_conn->commit();
                    } catch (Throwable $e2) {
                        $master_conn->rollback();
                        @file_put_contents(
                            __DIR__ . '/sale_feedback_debug.log',
                            date('c') . ' race_finalize ' . $e2->getMessage() . "\n",
                            FILE_APPEND
                        );
                        @unlink($dest);
                        header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_db');
                        exit;
                    }
                    @unlink($dest);
                    header('Location: messages.php?conv=' . $conv . '&notice=sale_feedback_saved');
                    exit;
                }
                @unlink($dest);
                header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_duplicate');
                exit;
            }
            throw new RuntimeException($errmsg !== '' ? $errmsg : 'exec');
        }
        $stmt->close();

        wvsu_finalize_sale_feedback_conversation($conv, $me);

        $master_conn->commit();
        break;
    } catch (Throwable $e) {
        $master_conn->rollback();
        if (is_file($dest)) {
            @unlink($dest);
        }
        @file_put_contents(
            __DIR__ . '/sale_feedback_debug.log',
            date('c') . ' ' . $e->getMessage() . "\n",
            FILE_APPEND
        );
        header('Location: messages.php?conv=' . $conv . '&error=sale_feedback_db');
        exit;
    }
}

header('Location: messages.php?conv=' . $conv . '&notice=sale_feedback_saved');
exit;

/**
 * Clears pending-sale flags and posts the buyer feedback line (call inside an open transaction).
 */
function wvsu_finalize_sale_feedback_conversation(int $convId, int $buyerId): void
{
    insert(
        'UPDATE conversation_meta SET is_closed = 1, pending_sale_buyer_id = NULL, pending_sale_listing_id = NULL WHERE conversation_id = ?',
        [(string) $convId]
    );

    insert(
        'INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)',
        [
            (string) $convId,
            (string) $buyerId,
            'Buyer left feedback and a photo for this sale — thanks for trading on WVSU Connect.',
        ]
    );
    insert(
        'UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE conversation_id = ?',
        [(string) $convId]
    );
}

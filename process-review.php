<?php
declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/profiles_reviews.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php?next=edit_profile.php');
    exit;
}

$listingId = (int) ($_POST['listing_id'] ?? 0);
if ($listingId <= 0) {
    header('Location: messages.php?notice=reviews_in_messages');
    exit;
}

$revieweeId = (int) ($_POST['reviewee_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim((string) ($_POST['comment'] ?? ''));
$returnTo = trim((string) ($_POST['return_to'] ?? ''));

if ($revieweeId <= 0 || $rating < 1 || $rating > 5) {
    header('Location: index.php?review_error=1');
    exit;
}

if ((int) $_SESSION['user_id'] === $revieweeId) {
    header('Location: profile.php?id=' . $revieweeId . '&review_error=self');
    exit;
}

$comment = mb_substr($comment, 0, 2000, 'UTF-8');

$defaultReturn = 'profile.php?id=' . $revieweeId;
$safeReturn = $defaultReturn;
if (
    $returnTo !== ''
    && preg_match('#^(?!/)(?!https?://)[a-z0-9_.-]+\.php(?:\?[a-zA-Z0-9_=&%.-]*)?$#', $returnTo)
) {
    $safeReturn = $returnTo;
}

$reviewerId = (int) $_SESSION['user_id'];

$dup = fetch_master(
    'SELECT review_id FROM user_reviews WHERE reviewer_id = ? AND reviewee_id = ? AND listing_id = ? LIMIT 1',
    [(string) $reviewerId, (string) $revieweeId, (string) $listingId]
);
if ($dup) {
    header('Location: profile.php?id=' . $revieweeId . '&review_error=review_locked');
    exit;
}

wvsu_user_reviews_ensure_photo_and_indexes($master_conn);
wvsu_user_reviews_ensure_seller_reply_columns($master_conn);
wvsu_user_reviews_drop_pair_unique_if_present($master_conn);

$ok = false;
$lastErrno = 0;
$lastError = '';
$stmt = $master_conn->prepare(
    'INSERT INTO user_reviews (reviewer_id, reviewee_id, listing_id, rating, comment, photo_url)
     VALUES (?, ?, ?, ?, ?, NULL)'
);
if ($stmt) {
    $stmt->bind_param('iiiis', $reviewerId, $revieweeId, $listingId, $rating, $comment);
    $ok = $stmt->execute();
    if (! $ok) {
        $lastErrno = $stmt->errno;
        $lastError = $stmt->error;
    }
    $stmt->close();
} else {
    $lastError = $master_conn->error;
}

if (! $ok) {
    @file_put_contents(
        __DIR__ . '/process_review_debug.log',
        date('c') . " listingId={$listingId} errno={$lastErrno} err=" . $lastError . "\n",
        FILE_APPEND
    );
    if ($lastErrno === 1062) {
        header('Location: profile.php?id=' . $revieweeId . '&review_error=review_locked');
        exit;
    }
    header('Location: profile.php?id=' . $revieweeId . '&review_error=db');
    exit;
}

$sep = str_contains($safeReturn, '?') ? '&' : '?';
header('Location: ' . $safeReturn . $sep . 'review_saved=1');
exit;

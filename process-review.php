<?php
declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php?next=edit_profile.php');
    exit;
}

$revieweeId = (int) ($_POST['reviewee_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim((string) ($_POST['comment'] ?? ''));
$returnTo = trim((string) ($_POST['return_to'] ?? ''));
$listingId = (int) ($_POST['listing_id'] ?? 0);

if ($revieweeId <= 0 || $rating < 1 || $rating > 5) {
    header('Location: index.php?review_error=1');
    exit;
}

if ((int) $_SESSION['user_id'] === $revieweeId) {
    header('Location: profile.php?id=' . $revieweeId . '&review_error=self');
    exit;
}

$comment = mb_substr($comment, 0, 2000, 'UTF-8');
$lidBind = $listingId > 0 ? $listingId : 0;

$defaultReturn = 'profile.php?id=' . $revieweeId;
$safeReturn = $defaultReturn;
if (
    $returnTo !== ''
    && preg_match('#^(?!/)(?!https?://)[a-z0-9_.-]+\.php(?:\?[a-zA-Z0-9_=&%.-]*)?$#', $returnTo)
) {
    $safeReturn = $returnTo;
}

$reviewerId = (int) $_SESSION['user_id'];

$sql = 'INSERT INTO user_reviews (reviewer_id, reviewee_id, listing_id, rating, comment)
    VALUES (?, ?, NULLIF(?, 0), ?, ?)
    ON DUPLICATE KEY UPDATE
        rating = VALUES(rating),
        comment = VALUES(comment),
        listing_id = VALUES(listing_id)';

$stmt = $master_conn->prepare($sql);
if (! $stmt) {
    header('Location: profile.php?id=' . $revieweeId . '&review_error=db');
    exit;
}

$stmt->bind_param('iiiis', $reviewerId, $revieweeId, $lidBind, $rating, $comment);
if (! $stmt->execute()) {
    header('Location: profile.php?id=' . $revieweeId . '&review_error=db');
    exit;
}

$sep = str_contains($safeReturn, '?') ? '&' : '?';
header('Location: ' . $safeReturn . $sep . 'review_saved=1');
exit;

<?php
declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$me = (int) $_SESSION['user_id'];
$reviewId = (int) ($_POST['review_id'] ?? 0);
$body = trim((string) ($_POST['seller_reply'] ?? ''));
$returnProfileId = (int) ($_POST['return_profile_id'] ?? 0);

if ($reviewId <= 0 || $returnProfileId <= 0) {
    header('Location: index.php');
    exit;
}

if ($me !== $returnProfileId) {
    header('Location: profile.php?id=' . $returnProfileId . '&review_error=reply_forbidden');
    exit;
}

$row = fetch_master(
    'SELECT review_id, reviewee_id, seller_reply FROM user_reviews WHERE review_id = ? LIMIT 1',
    [(string) $reviewId]
);
if (! $row || (int) ($row['reviewee_id'] ?? 0) !== $me) {
    header('Location: profile.php?id=' . $returnProfileId . '&review_error=reply_forbidden');
    exit;
}

$existing = trim((string) ($row['seller_reply'] ?? ''));
if ($existing !== '') {
    header('Location: profile.php?id=' . $returnProfileId . '&review_error=reply_once');
    exit;
}

if ($body === '') {
    header('Location: profile.php?id=' . $returnProfileId . '&review_error=reply_empty');
    exit;
}

$body = mb_substr($body, 0, 2000, 'UTF-8');

$stmt = $master_conn->prepare(
    'UPDATE user_reviews SET seller_reply = ?, seller_replied_at = CURRENT_TIMESTAMP
     WHERE review_id = ? AND reviewee_id = ? AND (seller_reply IS NULL OR seller_reply = \'\')'
);
if (! $stmt) {
    header('Location: profile.php?id=' . $returnProfileId . '&review_error=reply_db');
    exit;
}
$stmt->bind_param('sii', $body, $reviewId, $me);
if (! $stmt->execute() || $stmt->affected_rows < 1) {
    header('Location: profile.php?id=' . $returnProfileId . '&review_error=reply_once');
    $stmt->close();
    exit;
}
$stmt->close();

header('Location: profile.php?id=' . $returnProfileId . '&reply_saved=1');
exit;

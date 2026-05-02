<?php
require_once 'db_conn.php';
require_once __DIR__ . '/moderation.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

wvsu_moderation_ensure_tables($master_conn);

$reporterId = intval($_SESSION['user_id']);
$targetUserId = intval($_POST['target_user_id'] ?? 0);
$listingId = intval($_POST['listing_id'] ?? 0);
$conversationId = intval($_POST['conversation_id'] ?? 0);
$reason = strtolower(trim((string) ($_POST['reason_type'] ?? 'other')));
$details = trim((string) ($_POST['details'] ?? ''));
$returnTo = trim((string) ($_POST['return_to'] ?? ''));

$allowed = ['scam', 'unwanted_item', 'harassment', 'fake_profile', 'other'];
if (!in_array($reason, $allowed, true)) {
    $reason = 'other';
}
if ($targetUserId <= 0 || $targetUserId === $reporterId) {
    $fallback = $returnTo !== '' ? $returnTo : 'index.php';
    header('Location: ' . $fallback . (strpos($fallback, '?') === false ? '?' : '&') . 'report_error=invalid_target');
    exit;
}

// dedupe spam reports by same reporter/target/listing/reason in recent window
$dup = fetch(
    "SELECT report_id FROM user_reports WHERE reporter_id = ? AND target_user_id = ? AND IFNULL(listing_id,0)=? AND reason_type = ? AND created_at >= (NOW() - INTERVAL 5 MINUTE) LIMIT 1",
    [$reporterId, $targetUserId, $listingId, $reason]
);
if (!$dup) {
    $stmt = $master_conn->prepare("INSERT INTO user_reports (reporter_id, target_user_id, listing_id, conversation_id, reason_type, details) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $listingNullable = $listingId > 0 ? $listingId : null;
        $convNullable = $conversationId > 0 ? $conversationId : null;
        $stmt->bind_param('iiiiss', $reporterId, $targetUserId, $listingNullable, $convNullable, $reason, $details);
        $stmt->execute();
    }
}

$go = $returnTo !== '' ? $returnTo : 'index.php';
header('Location: ' . $go . (strpos($go, '?') === false ? '?' : '&') . 'report_sent=1');
exit;
?>

<?php
require_once 'db_conn.php';
require_once __DIR__ . '/moderation.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_dashboard.php');
    exit;
}

$adminId = intval($_SESSION['user_id'] ?? 0);
if (!wvsu_user_is_admin($master_conn, $adminId)) {
    header('Location: index.php');
    exit;
}
wvsu_moderation_ensure_tables($master_conn);

$action = trim((string) ($_POST['action'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

switch ($action) {
    case 'block_user': {
        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId > 0 && $userId !== $adminId) {
            $u = fetch("SELECT role_id FROM users WHERE user_id = ? LIMIT 1", [$userId]);
            if ($u && intval($u['role_id'] ?? 0) !== 1) {
                $master_conn->query("UPDATE users SET is_active = 0 WHERE user_id = " . intval($userId));
                $master_conn->query("UPDATE listings SET status = 'banned' WHERE owner_id = " . intval($userId));
                wvsu_log_admin_action($master_conn, $adminId, 'ban_user', 'user', $userId, $notes);
            }
        }
        break;
    }
    case 'unblock_user': {
        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            $master_conn->query("UPDATE users SET is_active = 1 WHERE user_id = " . intval($userId));
            wvsu_log_admin_action($master_conn, $adminId, 'unban_user', 'user', $userId, $notes);
        }
        break;
    }
    case 'remove_listing': {
        $listingId = intval($_POST['listing_id'] ?? 0);
        if ($listingId > 0) {
            $master_conn->query("UPDATE listings SET status = 'banned' WHERE listing_id = " . intval($listingId));
            wvsu_log_admin_action($master_conn, $adminId, 'remove_listing', 'listing', $listingId, $notes);
        }
        break;
    }
    case 'resolve_report':
    case 'dismiss_report': {
        $reportId = intval($_POST['report_id'] ?? 0);
        if ($reportId > 0) {
            $status = $action === 'resolve_report' ? 'resolved' : 'dismissed';
            $stmt = $master_conn->prepare("UPDATE user_reports SET status = ?, resolved_at = NOW(), resolved_by = ?, resolution_notes = ? WHERE report_id = ?");
            if ($stmt) {
                $stmt->bind_param('sisi', $status, $adminId, $notes, $reportId);
                $stmt->execute();
            }
            wvsu_log_admin_action($master_conn, $adminId, 'resolve_report', 'report', $reportId, $status . ': ' . $notes);
        }
        break;
    }
}

header('Location: admin_dashboard.php?ok=1');
exit;
?>

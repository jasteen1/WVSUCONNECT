<?php
declare(strict_types=1);

function wvsu_moderation_ensure_tables(mysqli $master): void
{
    $sqlAdminActions = "CREATE TABLE IF NOT EXISTS admin_actions (
        action_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        admin_id INT UNSIGNED NOT NULL,
        action_type ENUM('ban_user','unban_user','remove_listing','resolve_report','warn_user','verify_user','change_role') NOT NULL,
        target_entity_id INT UNSIGNED NOT NULL,
        entity_type ENUM('user','listing','report') NOT NULL,
        notes TEXT DEFAULT NULL,
        performed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (action_id),
        KEY idx_admin_actions_admin (admin_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!$master->query($sqlAdminActions)) {
        $fallbackAdmin = "CREATE TABLE IF NOT EXISTS admin_actions (
            action_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            admin_id INT UNSIGNED NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            target_entity_id INT UNSIGNED NOT NULL,
            entity_type VARCHAR(20) NOT NULL,
            notes TEXT DEFAULT NULL,
            performed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (action_id),
            KEY idx_admin_actions_admin (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $master->query($fallbackAdmin);
    }

    $sqlReports = "CREATE TABLE IF NOT EXISTS user_reports (
        report_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        reporter_id INT UNSIGNED NOT NULL,
        target_user_id INT UNSIGNED NOT NULL,
        listing_id INT UNSIGNED DEFAULT NULL,
        conversation_id INT UNSIGNED DEFAULT NULL,
        reason_type ENUM('scam','unwanted_item','harassment','fake_profile','other') NOT NULL DEFAULT 'other',
        details TEXT DEFAULT NULL,
        status ENUM('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL DEFAULT NULL,
        resolved_by INT UNSIGNED DEFAULT NULL,
        resolution_notes TEXT DEFAULT NULL,
        PRIMARY KEY (report_id),
        KEY idx_reports_status (status),
        KEY idx_reports_target (target_user_id),
        KEY idx_reports_listing (listing_id),
        KEY idx_reports_reporter (reporter_id),
        KEY idx_reports_resolved_by (resolved_by),
        CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
        CONSTRAINT fk_reports_target FOREIGN KEY (target_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        CONSTRAINT fk_reports_listing FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE SET NULL,
        CONSTRAINT fk_reports_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!$master->query($sqlReports)) {
        $fallback = "CREATE TABLE IF NOT EXISTS user_reports (
            report_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            reporter_id INT UNSIGNED NOT NULL,
            target_user_id INT UNSIGNED NOT NULL,
            listing_id INT UNSIGNED DEFAULT NULL,
            conversation_id INT UNSIGNED DEFAULT NULL,
            reason_type ENUM('scam','unwanted_item','harassment','fake_profile','other') NOT NULL DEFAULT 'other',
            details TEXT DEFAULT NULL,
            status ENUM('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL DEFAULT NULL,
            resolved_by INT UNSIGNED DEFAULT NULL,
            resolution_notes TEXT DEFAULT NULL,
            PRIMARY KEY (report_id),
            KEY idx_reports_status (status),
            KEY idx_reports_target (target_user_id),
            KEY idx_reports_listing (listing_id),
            KEY idx_reports_reporter (reporter_id),
            KEY idx_reports_resolved_by (resolved_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        if (!$master->query($fallback)) {
            die("Failed creating user_reports table: " . $master->error);
        }
    }
}

function wvsu_user_is_admin(mysqli $conn, int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }
    $stmt = $conn->prepare("SELECT role_id, is_active FROM users WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $u = $res ? $res->fetch_assoc() : null;
    return (bool) ($u && intval($u['role_id'] ?? 0) === 1 && intval($u['is_active'] ?? 0) === 1);
}

function wvsu_log_admin_action(mysqli $conn, int $adminId, string $actionType, string $entityType, int $targetId, string $notes = ''): void
{
    $stmt = $conn->prepare("INSERT INTO admin_actions (admin_id, action_type, target_entity_id, entity_type, notes) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('isiss', $adminId, $actionType, $targetId, $entityType, $notes);
    $stmt->execute();
}

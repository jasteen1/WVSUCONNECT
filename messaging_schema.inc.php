<?php
declare(strict_types=1);

/** Adds message media columns (idempotent). Requires db_conn (profiles_reviews for column check). */
function wvsu_messaging_ensure_schema(mysqli $master): void
{
    if (! function_exists('wvsu_mysql_column_exists')) {
        return;
    }
    if (! wvsu_mysql_column_exists($master, 'messages', 'message_type')) {
        $master->query(
            "ALTER TABLE messages ADD COLUMN message_type ENUM('text','image') NOT NULL DEFAULT 'text'"
        );
    }
    if (! wvsu_mysql_column_exists($master, 'messages', 'image_url')) {
        $master->query('ALTER TABLE messages ADD COLUMN image_url VARCHAR(500) NULL DEFAULT NULL');
    }
}

/**
 * Navbar / home deep links: unread count (master read, no replica lag) + inbox href that routes through latest-unread shortcut when applicable.
 *
 * @return array{count:int, inbox_href:string}
 */
function wvsu_user_messages_nav_state(int $userId): array
{
    if ($userId <= 0 || ! function_exists('fetch_master')) {
        return ['count' => 0, 'inbox_href' => 'messages.php'];
    }
    $u = (string) $userId;
    $row = fetch_master(
        'SELECT COUNT(*) AS cnt FROM messages m
            INNER JOIN conversations c ON m.conversation_id = c.conversation_id
            WHERE m.is_read = 0 AND m.sender_id <> ? AND (c.participant_a = ? OR c.participant_b = ?)',
        [$u, $u, $u]
    );
    $cnt = isset($row['cnt']) ? (int) $row['cnt'] : 0;

    return [
        'count' => $cnt,
        'inbox_href' => $cnt > 0 ? 'messages.php?focus=unread' : 'messages.php',
    ];
}

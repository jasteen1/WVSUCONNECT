<?php
declare(strict_types=1);

/**
 * Ensures profile photo upload directory exists and is writable by the web server (e.g. XAMPP user "daemon" on macOS).
 */
function wvsu_ensure_writable_profile_upload_dir(?string $logPath = null): ?string
{
    $root = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    $profiles = $root . DIRECTORY_SEPARATOR . 'profiles';

    foreach ([$root, $profiles] as $dir) {
        if (! is_dir($dir)) {
            if (! @mkdir($dir, 0777, true)) {
                if ($logPath) {
                    @file_put_contents($logPath, date('c') . " mkdir_failed {$dir}\n", FILE_APPEND);
                }
                return null;
            }
        }
        @chmod($dir, 0777);
        clearstatcache(true, $dir);
        if (! is_writable($dir)) {
            if ($logPath) {
                @file_put_contents(
                    $logPath,
                    date('c') . " not_writable {$dir} perms=" . substr(sprintf('%o', (int) @fileperms($dir)), -4) . "\n",
                    FILE_APPEND
                );
            }
            return null;
        }
    }

    return $profiles;
}

/** Writable uploads/messages for chat attachments */
function wvsu_ensure_writable_messages_upload_dir(?string $logPath = null): ?string
{
    $root = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    $msg = $root . DIRECTORY_SEPARATOR . 'messages';

    foreach ([$root, $msg] as $dir) {
        if (! is_dir($dir)) {
            if (! @mkdir($dir, 0777, true)) {
                if ($logPath) {
                    @file_put_contents($logPath, date('c') . " mkdir_failed {$dir}\n", FILE_APPEND);
                }
                return null;
            }
        }
        @chmod($dir, 0777);
        clearstatcache(true, $dir);
        if (! is_writable($dir)) {
            if ($logPath) {
                @file_put_contents(
                    $logPath,
                    date('c') . " not_writable {$dir}\n",
                    FILE_APPEND
                );
            }
            return null;
        }
    }

    return $msg;
}

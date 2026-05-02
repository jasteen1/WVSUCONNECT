<?php
declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/wvsu_upload_dirs.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: edit_profile.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php?next=edit_profile.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$bio = trim((string) ($_POST['bio'] ?? ''));
$bio = mb_substr($bio, 0, 2000, 'UTF-8');

$socialInstagram = (string) (wvsu_sanitize_profile_url($_POST['social_instagram'] ?? null) ?? '');
$socialFacebook = (string) (wvsu_sanitize_profile_url($_POST['social_facebook'] ?? null) ?? '');
$socialX = (string) (wvsu_sanitize_profile_url($_POST['social_x'] ?? null) ?? '');
$socialTiktok = (string) (wvsu_sanitize_profile_url($_POST['social_tiktok'] ?? null) ?? '');
$socialLinkedin = (string) (wvsu_sanitize_profile_url($_POST['social_linkedin'] ?? null) ?? '');
$socialWebsite = (string) (wvsu_sanitize_profile_url($_POST['social_website'] ?? null) ?? '');

$logPath = __DIR__ . '/profile_edit_debug.log';

function wvsu_profile_log(string $path, string $line): void
{
    @file_put_contents($path, date('c') . ' ' . $line . "\n", FILE_APPEND);
}

$newPic = null;
$uploadErr = isset($_FILES['profile_photo']['error'])
    ? (int) $_FILES['profile_photo']['error']
    : UPLOAD_ERR_NO_FILE;

if ($uploadErr !== UPLOAD_ERR_NO_FILE) {
    if ($uploadErr !== UPLOAD_ERR_OK) {
        $code = match ($uploadErr) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'upload_too_large',
            UPLOAD_ERR_PARTIAL => 'upload_partial',
            UPLOAD_ERR_NO_TMP_DIR => 'upload_no_tmp',
            default => 'upload_failed',
        };
        wvsu_profile_log($logPath, 'upload_php_error=' . $uploadErr . ' name=' . ($_FILES['profile_photo']['name'] ?? ''));
        header('Location: edit_profile.php?err=' . rawurlencode($code));
        exit;
    }
    $tmp = (string) ($_FILES['profile_photo']['tmp_name'] ?? '');
    $name = basename((string) ($_FILES['profile_photo']['name'] ?? ''));
    if ($tmp === '' || $name === '') {
        wvsu_profile_log($logPath, 'empty_tmp_or_name tmp=' . $tmp);
        header('Location: edit_profile.php?err=no_file_received');
        exit;
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (! in_array($ext, $allowed, true)) {
        header('Location: edit_profile.php?err=' . rawurlencode('invalid_type'));
        exit;
    }
    $targetDir = wvsu_ensure_writable_profile_upload_dir($logPath);
    if ($targetDir === null) {
        header('Location: edit_profile.php?err=folder_perm');
        exit;
    }
    $newName = 'user_' . $userId . '_' . time() . '.' . $ext;
    $dest = $targetDir . DIRECTORY_SEPARATOR . $newName;

    $moved = move_uploaded_file($tmp, $dest);
    if (! $moved) {
        $isUp = is_uploaded_file($tmp) ? '1' : '0';
        $readable = is_readable($tmp) ? '1' : '0';
        $last = error_get_last();
        wvsu_profile_log(
            $logPath,
            'move_failed tmp=' . $tmp . ' dest=' . $dest . ' is_uploaded=' . $isUp
            . ' readable=' . $readable . ' err=' . json_encode($last, JSON_UNESCAPED_SLASHES)
        );
        header('Location: edit_profile.php?err=upload_move');
        exit;
    }
    @chmod($dest, 0666);
    $newPic = 'uploads/profiles/' . $newName;
}

if ($newPic !== null) {
    $st = $master_conn->prepare('UPDATE users SET profile_pic_url = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
    if (! $st) {
        wvsu_profile_log($logPath, 'prepare_photo ' . $master_conn->error);
        header('Location: edit_profile.php?err=save_photo');
        exit;
    }
    $st->bind_param('si', $newPic, $userId);
    if (! $st->execute()) {
        wvsu_profile_log($logPath, 'exec_photo ' . $st->error);
        header('Location: edit_profile.php?err=save_photo');
        exit;
    }
}

$sql = 'UPDATE users SET bio = ?,
    social_instagram = NULLIF(?, \'\'), social_facebook = NULLIF(?, \'\'), social_x = NULLIF(?, \'\'),
    social_tiktok = NULLIF(?, \'\'), social_linkedin = NULLIF(?, \'\'), social_website = NULLIF(?, \'\'),
    updated_at = CURRENT_TIMESTAMP WHERE user_id = ?';

$stmt = $master_conn->prepare($sql);
if (! $stmt) {
    wvsu_profile_log($logPath, 'prepare_bio ' . $master_conn->error);
    header('Location: edit_profile.php?err=save_profile');
    exit;
}
$stmt->bind_param(
    str_repeat('s', 7) . 'i',
    $bio,
    $socialInstagram,
    $socialFacebook,
    $socialX,
    $socialTiktok,
    $socialLinkedin,
    $socialWebsite,
    $userId
);
if (! $stmt->execute()) {
    wvsu_profile_log($logPath, 'exec_bio ' . $stmt->error);
    header('Location: edit_profile.php?err=save_profile');
    exit;
}

header('Location: profile.php?id=' . $userId . '&saved=1');
exit;

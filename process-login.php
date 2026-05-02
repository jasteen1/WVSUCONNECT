<?php

declare(strict_types=1);

// process-login.php
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/wvsu_auth_redirect.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$emailRaw = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';
$email = strtolower($emailRaw);

$wantRaw = isset($_POST['redirect_after']) ? trim((string) $_POST['redirect_after']) : '';
$safeNext = wvsu_login_redirect_destination($wantRaw);

function wvsu_login_fail_redirect(string $message, string $safeNext): never
{
    $q = ['error' => $message];
    if ($safeNext !== '') {
        $q['next'] = $safeNext;
    }
    header('Location: login.php?' . http_build_query($q));
    exit;
}

/** phpMyAdmin / CSV often saves plaintext wrapped in literal quote characters */
function wvsu_unwrap_outer_quotes(string $s): string
{
    $s = trim($s);
    $len = strlen($s);
    if ($len >= 2) {
        $a = $s[0];
        $z = $s[$len - 1];
        if (($a === "'" && $z === "'") || ($a === '"' && $z === '"')) {
            return trim(substr($s, 1, $len - 2));
        }
    }

    return $s;
}

/**
 * Undo common tooling artifacts: BOM, accidental backslash-escaping of “$”, outer quotes.
 */
function wvsu_normalize_db_password_blob(string $raw): string
{
    $s = trim($raw);
    if ($s !== '' && strncmp($s, "\xEF\xBB\xBF", 3) === 0) {
        $s = trim(substr($s, 3));
    }
    if (str_contains($s, '\\$2y$') || str_contains($s, '\\$2a$') || str_contains($s, '\\$2b$')
        || str_contains($s, '\\$argon2')) {
        $s = trim(stripslashes($s));
    }

    /* Double pass: pasted values sometimes look `" '123' "` */
    $s = wvsu_unwrap_outer_quotes($s);

    return wvsu_unwrap_outer_quotes($s);
}

if ($email === '' || $password === '') {
    wvsu_login_fail_redirect('Please enter both your campus email and password.', $safeNext);
}

$sql = 'SELECT user_id, full_name, `password` AS pwd_hash, role_id, is_active FROM users WHERE LOWER(TRIM(email)) = ? LIMIT 1';
$user = fetch_master($sql, [$email]);

/* If DB row uses odd spacing/casing mysql LOWER(TRIM) didn’t align with PHP trim+lower — try literal stored email once. */
if (! $user && $emailRaw !== '') {
    $user = fetch_master(
        'SELECT user_id, full_name, `password` AS pwd_hash, role_id, is_active FROM users WHERE email = ? LIMIT 1',
        [trim($emailRaw)]
    );
}

if (! $user) {
    wvsu_login_fail_redirect(
        "We couldn't find an account with that email. Double-check your address — or join WVSU Connect via Register.",
        $safeNext
    );
}

if (intval($user['is_active'] ?? 0) !== 1) {
    wvsu_login_fail_redirect(
        'This account is inactive. Contact an administrator if you think that is wrong.',
        $safeNext
    );
}

$storedRaw = (string) ($user['pwd_hash'] ?? $user['password'] ?? '');
$stored = $storedRaw === '' ? '' : wvsu_normalize_db_password_blob($storedRaw);

/**
 * password_get_info() returns algo 0 for plaintext, MD5 hex, garbled values, etc.
 * Only real bcrypt/argon strings get a non-zero algo — so we only auto-migrate legacy/plain when algo is 0.
 */
$storedAlgo = ($stored === '') ? 0 : (int) (password_get_info($stored)['algo'] ?? 0);

$passwordCandidates = array_values(array_unique(array_filter([
    $password,
    trim($password),
], static fn(string $x): bool => $x !== '')));

$verified = false;
if ($stored !== '') {
    foreach ($passwordCandidates as $tryPass) {
        if (password_verify($tryPass, $stored)) {
            $verified = true;
            break;
        }
    }
}

/* Plaintext (phpMyAdmin “123”) or legacy MD5 hex: accept once, rewrite row to password_hash(). */
if (! $verified && $stored !== '' && $storedAlgo === 0) {
    $pt = trim($password);
    $stTrim = trim($stored);
    $plainForNewHash = null;
    if ($stTrim !== '' && hash_equals($stTrim, $pt)) {
        $plainForNewHash = $pt;
    } elseif (strlen($stTrim) === 32 && ctype_xdigit($stTrim)
        && hash_equals(strtolower($stTrim), md5($password))) {
        $plainForNewHash = $password;
    }

    if ($plainForNewHash !== null) {
        global $master_conn;
        $newHash = password_hash($plainForNewHash, PASSWORD_DEFAULT);
        $uid = (int) ($user['user_id'] ?? 0);
        if ($uid > 0) {
            $st = $master_conn->prepare('UPDATE users SET `password` = ? WHERE user_id = ? LIMIT 1');
            if ($st) {
                /* Use same bind helper as fetch_master — mixed bind_param('si', …) is unreliable on some PHP builds. */
                wvsu_mysqli_bind_params($st, [$newHash, (string) $uid]);
                if ($st->execute()) {
                    $verified = password_verify($plainForNewHash, $newHash);
                } else {
                    error_log('wvsu_login: password migrate UPDATE failed: ' . $st->error);
                }
            } else {
                error_log('wvsu_login: password migrate prepare failed: ' . $master_conn->error);
            }
        }
    }
}

if (! $verified || $stored === '') {
    wvsu_login_fail_redirect('Incorrect password. Please try again.', $safeNext);
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
}

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role_id'] = intval($user['role_id'] ?? 3);

if ($safeNext !== '') {
    header('Location: ' . $safeNext);
    exit;
}

header('Location: index.php');
exit;
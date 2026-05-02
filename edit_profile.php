<?php
require_once __DIR__ . '/db_conn.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php?next=edit_profile.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$row = fetch_master(
    'SELECT user_id, full_name, profile_pic_url, bio, social_instagram, social_facebook, social_x,
            social_tiktok, social_linkedin, social_website, updated_at
     FROM users WHERE user_id = ? LIMIT 1',
    [(string) $userId]
);
if (! $row) {
    exit('Account not found.');
}

$preview = htmlspecialchars(
    wvsu_user_avatar_img_src((int) $userId, (string) ($row['updated_at'] ?? '')),
    ENT_QUOTES,
    'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d4daa">
    <title>Edit profile — WVSU CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>

<div class="container mt-4 pb-5 wvsu-pan-soft">
    <div class="mx-auto" style="max-width: 640px;" data-io-animate>
        <h1 class="h4 fw-bold mb-1">Edit profile</h1>
        <p class="text-muted small mb-4">Photo, bio, and social links show on your public profile.</p>

        <?php
        $err = isset($_GET['err']) ? (string) $_GET['err'] : '';
        $errMsg = match ($err) {
            'upload_too_large' => 'Photo is too large for the server limit. In XAMPP, edit php/php.ini — raise upload_max_filesize and post_max_size (both), then restart Apache.',
            'upload_partial' => 'Upload was interrupted. Try again or use a smaller photo.',
            'upload_failed', 'upload_move' => 'Could not save the file on the server (move failed). On Mac/Linux run: chmod -R 777 uploads (from your WVSUCONNECT folder), or see profile_edit_debug.log.',
            'upload_no_tmp' => 'PHP has no temporary folder for uploads. Fix upload_tmp_dir in php.ini.',
            'no_file_received' => 'Browser did not send a usable file — try choosing the image again.',
            'folder_perm' => 'The uploads folder is not writable. From Terminal: cd path/to/WVSUCONNECT && chmod -R 777 uploads',
            'invalid_type' => 'Please use JPG, PNG, WebP, or GIF for your photo.',
            'save_photo' => 'Photo saved to disk but database update failed. Check profile_edit_debug.log in the project folder.',
            'save_profile' => 'Could not save profile fields. Check profile_edit_debug.log for MySQL errors.',
            default => '',
        };
        ?>
        <?php if ($errMsg !== ''): ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-4"><?= htmlspecialchars($errMsg) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm market-card">
            <div class="card-body p-4">
                <form method="post" action="process-edit-profile.php" enctype="multipart/form-data">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <img src="<?= $preview ?>" alt="" class="rounded-4 shadow-sm" width="88" height="88" style="object-fit:cover;" id="avPreview">
                        <div>
                            <label class="form-label small fw-semibold mb-1">Profile photo</label>
                            <input type="file" name="profile_photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif">
                            <span class="text-muted" style="font-size: .75rem;">JPG, PNG, WebP, or GIF · max sensible size (~5MB)</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">About you</label>
                        <textarea name="bio" class="form-control" rows="5" maxlength="2000" placeholder="Course, hobbies, what you sell or freelance in..."><?= htmlspecialchars((string) ($row['bio'] ?? '')) ?></textarea>
                    </div>

                    <p class="small fw-semibold text-secondary mb-2">Social links (full URLs, https recommended)</p>
                    <div class="mb-3">
                        <label class="form-label small">Instagram</label>
                        <input type="url" name="social_instagram" class="form-control" value="<?= htmlspecialchars((string) ($row['social_instagram'] ?? '')) ?>" placeholder="https://instagram.com/...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Facebook</label>
                        <input type="url" name="social_facebook" class="form-control" value="<?= htmlspecialchars((string) ($row['social_facebook'] ?? '')) ?>" placeholder="https://facebook.com/...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">X (Twitter)</label>
                        <input type="url" name="social_x" class="form-control" value="<?= htmlspecialchars((string) ($row['social_x'] ?? '')) ?>" placeholder="https://x.com/...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">TikTok</label>
                        <input type="url" name="social_tiktok" class="form-control" value="<?= htmlspecialchars((string) ($row['social_tiktok'] ?? '')) ?>" placeholder="https://tiktok.com/@...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">LinkedIn</label>
                        <input type="url" name="social_linkedin" class="form-control" value="<?= htmlspecialchars((string) ($row['social_linkedin'] ?? '')) ?>" placeholder="https://linkedin.com/in/...">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small">Website</label>
                        <input type="url" name="social_website" class="form-control" value="<?= htmlspecialchars((string) ($row['social_website'] ?? '')) ?>" placeholder="https://">
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Save changes</button>
                        <a href="profile.php?id=<?= (int) $userId ?>" class="btn btn-outline-secondary rounded-pill">View public profile</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var inp = document.querySelector('input[name="profile_photo"]');
  var img = document.getElementById('avPreview');
  if (!inp || !img) return;
  inp.addEventListener('change', function () {
    var f = inp.files && inp.files[0];
    if (!f || !/^image\//.test(f.type)) return;
    img.src = URL.createObjectURL(f);
  });
})();
</script>
</body>
</html>

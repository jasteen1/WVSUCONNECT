<?php
session_start();

require_once __DIR__ . '/wvsu_auth_redirect.inc.php';

$nextRaw = isset($_GET['next']) ? (string) $_GET['next'] : '';
$nextSafeFull = wvsu_login_redirect_destination($nextRaw);

$loginErrorRaw = isset($_GET['error']) ? (string) $_GET['error'] : '';
$loginErrorDisplay = '';
if ($loginErrorRaw !== '') {
    if ($loginErrorRaw === 'email_registered') {
        $loginErrorDisplay = 'This campus email is already registered. Log in below, or go back to sign up with a different email.';
    } else {
        $loginErrorDisplay = $loginErrorRaw;
    }
}

$prefillEmail = '';
if (isset($_GET['email'])) {
    $e = trim((string) $_GET['email']);
    if (strlen($e) <= 254 && str_contains($e, '@')) {
        $prefillEmail = $e;
    }
}

$loginSuccess = isset($_GET['success']) ? trim((string) $_GET['success']) : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a1628">
    <title>Log in — WVSU CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body class="wvsu-auth-bg d-flex align-items-center justify-content-center py-5 px-3">
<?php include __DIR__ . '/entry_loader.inc.php'; ?>
    <div class="wvsu-auth-orbs" aria-hidden="true"></div>

    <div class="card border-0 shadow-lg wvsu-auth-card mx-auto overflow-hidden position-relative w-100"
         style="max-width: 420px; border-radius: 22px;">
        <div class="position-absolute top-0 start-0 w-100" style="height: 5px; background: linear-gradient(90deg,#f5b408,#2563eb);"></div>
        <div class="card-body p-4 p-lg-5">
            <div class="text-center mb-4">
                <img src="assets/logowithtext.png" alt="WVSU CONNECT" class="mb-4" height="44" decoding="async" loading="eager">
                <p class="text-muted mb-0 small fw-semibold">Student marketplace • Sign in</p>
            </div>

            <?php if ($loginSuccess !== ''): ?>
                <div class="alert alert-success rounded-4 alert-dismissible fade show small py-3" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo htmlspecialchars($loginSuccess); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($loginErrorDisplay !== ''): ?>
                <div class="alert alert-danger rounded-4 alert-dismissible fade show small py-3" role="alert">
                    <i class="bi bi-envelope-exclamation me-2"></i>
                    <?php echo htmlspecialchars($loginErrorDisplay); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="process-login.php" method="POST">
                <?php if ($nextSafeFull !== ''): ?>
                    <input type="hidden" name="redirect_after" value="<?php echo htmlspecialchars($nextSafeFull, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Campus email</label>
                    <input type="email" name="email" class="form-control rounded-pill px-3 py-2" placeholder="student.name@wvsu.edu.ph" required autocomplete="username"
                           value="<?php echo htmlspecialchars($prefillEmail, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control rounded-pill px-3 py-2" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Continue
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top border-secondary-subtle">
                <p class="small text-muted mb-0">
                    New here?
                    <a href="register.php" class="text-decoration-none fw-bold">Create an account</a>
                </p>
                <a href="index.php" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1 mt-3">
                    <i class="bi bi-arrow-left"></i> Back to home
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
declare(strict_types=1);
require_once __DIR__ . '/profiles_reviews.inc.php';
$wvsuMainColleges = array_values(array_filter(
    wvsu_college_codes(),
    static fn (string $c): bool => $c !== 'ILS'
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a1628">
    <title>Create account — WVSU CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body class="wvsu-auth-bg d-flex align-items-center justify-content-center py-5 px-3">
<?php include __DIR__ . '/entry_loader.inc.php'; ?>
    <div class="wvsu-auth-orbs" aria-hidden="true"></div>

    <div class="card border-0 shadow-lg wvsu-auth-card mx-auto overflow-hidden position-relative w-100"
         style="max-width: 500px; border-radius: 22px;">
        <div class="position-absolute top-0 start-0 w-100" style="height: 5px; background: linear-gradient(90deg,#2563eb,#f5b408);"></div>
        <div class="card-body p-4 p-lg-5">
            <div class="text-center mb-4">
                <img src="assets/logowithtext.png" alt="WVSU CONNECT" class="mb-3" height="44" decoding="async" loading="eager">
                <p class="text-muted mb-0 small fw-semibold">Join the marketplace for Taga-West</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger rounded-4 alert-dismissible fade show py-3 small" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars((string) $_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success rounded-4 alert-dismissible fade show py-3 small" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo htmlspecialchars((string) $_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="process-register.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Full name</label>
                    <input type="text" name="full_name" class="form-control rounded-pill px-3 py-2" placeholder="Complete name as in school records" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Campus email</label>
                    <input type="email" name="email" class="form-control rounded-pill px-3 py-2" placeholder="your.name@wvsu.edu.ph" required autocomplete="email">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">College / unit</label>
                    <select name="college" class="form-select rounded-3 px-3 py-2" required>
                        <option value="" disabled selected>Select college…</option>
                        <?php foreach ($wvsuMainColleges as $c): ?>
                            <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                        <optgroup label="Others">
                            <option value="ILS">ILS (Others)</option>
                        </optgroup>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Year level</label>
                    <select name="year_level" class="form-select rounded-3 px-3 py-2" required>
                        <option value="" disabled selected>Select year…</option>
                        <option value="1">1st year</option>
                        <option value="2">2nd year</option>
                        <option value="3">3rd year</option>
                        <option value="4">4th year</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Course / program</label>
                    <input type="text" name="course" class="form-control rounded-3 px-3 py-2" maxlength="200" placeholder="e.g. BS Computer Science, BSED English" autocomplete="off">
                    <div class="form-text small">Optional — type your degree or program as listed in your college.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control rounded-pill px-3 py-2" placeholder="At least 6 characters" required autocomplete="new-password">
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold">Confirm password</label>
                    <input type="password" name="confirm_password" class="form-control rounded-pill px-3 py-2" placeholder="Repeat password" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">
                    <i class="bi bi-person-plus me-2"></i>Create account
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top border-secondary-subtle">
                <p class="small text-muted mb-0">
                    Already have an account?
                    <a href="login.php" class="text-decoration-none fw-bold">Log in</a>
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

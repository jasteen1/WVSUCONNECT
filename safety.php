<?php

declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/messaging_schema.inc.php';

$msgHref = ! empty($_SESSION['user_id'])
    ? wvsu_user_messages_nav_state((int) $_SESSION['user_id'])['inbox_href']
    : 'login.php?next=' . rawurlencode('messages.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d4daa">
    <title>Safety &amp; meetups — WVSU CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body>

<?php include __DIR__ . '/navbar.php'; ?>

<main class="container py-5 mb-5 wvsu-pan-soft" data-io-animate style="max-width: 720px;">
    <a href="index.php" class="small text-muted text-decoration-none d-inline-flex align-items-center gap-1 mb-3">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to home
    </a>
    <h1 class="fw-bold display-6 mb-2">Safety &amp; meetups</h1>
    <p class="text-muted lead fs-6 mb-4 pb-2 border-bottom border-secondary-subtle">
        Peer-to-peer is powerful when everyone keeps it public, honest, and on-campus smart. Same reminders we swear by everywhere on WVSU Connect.
    </p>

    <aside class="wvsu-home-safety-tip wvsu-home-safety-tip--spotlight mb-5" aria-label="Safety pledge">
        <div class="wvsu-home-safety-tip__icon" aria-hidden="true"><i class="bi bi-shield-check"></i></div>
        <div class="wvsu-home-safety-tip__copy">
            <strong class="d-block mb-1">The fine print you actually want — stay safe, Taga-West</strong>
            <span class="text-muted small d-block mb-3">Stick to daytime, well-lit spots. Confirm ID-face match when it matters. Cash or trusted apps — whatever you agree in Messages.</span>
            <div class="d-flex flex-wrap gap-2">
                <a href="index.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">Back to marketplace</a>
                <a href="<?= htmlspecialchars($msgHref) ?>" class="btn btn-sm btn-light rounded-pill px-3 border fw-semibold">Go to inbox</a>
            </div>
        </div>
    </aside>

    <section class="small text-muted" aria-labelledby="safety-extra">
        <h2 id="safety-extra" class="h6 fw-bold text-dark mb-2">One more beat</h2>
        <p class="mb-0">Prefer listing questions and swaps in Messages so everyone has context. Meet where others can see you, and bail if anything feels sketchy — no deal is worth the risk.</p>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php

declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/messaging_schema.inc.php';

/**
 * One card on the homepage “Recently listed” grid.
 *
 * @param array<string, mixed> $item
 */
function wvsu_index_render_recent_card(array $item): void
{
    $listingId = (int) ($item['listing_id'] ?? 0);
    $isService = (($item['listing_type'] ?? '') === 'service');
    $img = ! empty($item['image_url'])
        ? (string) $item['image_url']
        : 'https://via.placeholder.com/600x400?text=No+Image';
    $badgeClass = $isService ? 'bg-info text-white' : 'bg-white text-dark';
    $isSoldOut = ((($item['listing_type'] ?? '') === 'product') && (int) ($item['stock'] ?? 0) <= 0);
    $href = $isService
        ? 'view-service.php?id=' . $listingId
        : 'view-product.php?id=' . $listingId;
    ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm item-card market-card <?= $isSoldOut ? 'sold-out' : '' ?>">
                    <div class="position-absolute top-0 start-0 m-2">
                        <span class="badge <?= $badgeClass ?> shadow-sm"><?= htmlspecialchars((string) ($item['category_name'] ?? (($isService ? 'Service' : 'Product')))) ?></span>
                    </div>
                    <?php if ($isSoldOut): ?>
                        <div class="position-absolute top-0 end-0 m-2">
                            <span class="badge bg-danger text-white shadow-sm">Sold out</span>
                        </div>
                    <?php endif; ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" alt="<?= htmlspecialchars((string) ($item['title'] ?? '')) ?>" style="height: 200px; object-fit: cover;">
                    <div class="card-body p-3">
                        <h6 class="card-title mb-1 text-truncate fw-bold"><?= htmlspecialchars((string) ($item['title'] ?? '')) ?></h6>
                        <div class="d-flex align-items-center mb-2">
                            <?php if (! $isService): ?>
                                <span class="text-primary fw-bolder">₱<?= number_format((float) ($item['price'] ?? 0), 2) ?></span>
                                <span class="ms-2 badge bg-light text-muted fw-normal" style="font-size: 0.7rem;">Stock <?= (int) ($item['stock'] ?? 0) ?></span>
                                <?php if ($isSoldOut): ?>
                                    <span class="ms-2 badge bg-danger text-white" style="font-size: 0.7rem;">Sold out</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-primary fw-bolder">₱<?= number_format((float) ($item['rate'] ?? 0), 2) ?><?php if (! empty($item['rate_type']) && $item['rate_type'] === 'per_hour') {
                                    echo '/hr';
                                } ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center pt-2 border-top">
                            <div class="bg-secondary rounded-circle me-2" style="width: 20px; height: 20px;"></div>
                            <small class="text-muted">By <?= htmlspecialchars((string) ($item['owner_name'] ?? 'User')) ?></small>
                        </div>
                        <?php if (! $isSoldOut): ?>
                            <a href="<?= htmlspecialchars($href) ?>" class="stretched-link"></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d4daa">
    <title>WVSU CONNECT — Student marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body class="wvsu-home-page">

<?php include 'navbar.php'; ?>

<?php
/** Master read so counts match what you just published (replica lag can hide new services/products). */
$heroCounts = fetch_master(
    "SELECT
        (SELECT COUNT(*) FROM listings WHERE status = 'active' AND listing_type = 'product') AS pc,
        (SELECT COUNT(*) FROM listings WHERE status = 'active' AND listing_type = 'service') AS sc"
);
if (! is_array($heroCounts)) {
    $heroCounts = ['pc' => 0, 'sc' => 0];
}
$heroProductCount = (int) ($heroCounts['pc'] ?? 0);
$heroServiceCount = (int) ($heroCounts['sc'] ?? 0);
$productSell = ! empty($_SESSION['user_id']) ? 'addproduct.php' : 'login.php?next=' . rawurlencode('addproduct.php');
$serviceSell = ! empty($_SESSION['user_id']) ? 'addservice.php' : 'login.php?next=' . rawurlencode('addservice.php');
$msgHref = ! empty($_SESSION['user_id'])
    ? wvsu_user_messages_nav_state((int) $_SESSION['user_id'])['inbox_href']
    : 'login.php?next=' . rawurlencode('messages.php');
$yourListHref = ! empty($_SESSION['user_id']) ? 'your_listings.php' : 'login.php?next=' . rawurlencode('your_listings.php');
?>

<section class="hero-market hero-market--clean text-white">
    <div class="hero-market__mesh" aria-hidden="true"></div>
    <div class="hero-market__bg" style="background-image: url('wvsucover.png');"></div>
    <div class="hero-market__scrim"></div>

    <div class="container position-relative py-5 py-lg-6">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9 hero-clean text-center text-lg-start">
                <p class="hero-clean__eyebrow mb-3 mb-lg-4">WVSU Connect · Campus marketplace</p>
                <h1 class="hero-clean__title mb-3 mb-lg-4">
                    <span class="hero-clean__line d-block">Products and services,</span>
                    <span class="hero-clean__line d-block"><span class="hero-clean__accent">one trusted place.</span></span>
                </h1>
                <p class="hero-clean__lead mb-4 mb-lg-5 mx-auto mx-lg-0">
                    Buy from classmates, book student-run services, or list what you sell — chat and meet on campus.
                </p>

                <p class="hero-clean__stats mb-4 mb-lg-5 mx-auto mx-lg-0">
                    <span class="hero-clean__stat-num"><?= number_format(max(0, $heroProductCount)) ?></span> active products
                    <span class="hero-clean__dot" aria-hidden="true"></span>
                    <span class="hero-clean__stat-num"><?= number_format(max(0, $heroServiceCount)) ?></span> active services
                </p>

                <div class="d-flex flex-wrap gap-4 justify-content-center justify-content-lg-start align-items-center small hero-clean__foot">
                    <a href="<?= htmlspecialchars($msgHref) ?>" class="text-white text-opacity-85 text-decoration-none hero-clean__link">
                        <i class="bi bi-chat-dots me-1" aria-hidden="true"></i>Messages
                    </a>
                    <span class="text-white text-opacity-50 d-none d-sm-inline">·</span>
                    <span class="text-white text-opacity-75">Meet in public spots on campus</span>
                    <span class="text-white text-opacity-50 d-none d-sm-inline">·</span>
                    <a href="#get-started" class="text-white text-opacity-85 text-decoration-none hero-clean__link">How it works</a>
                    <span class="text-white text-opacity-50 d-none d-sm-inline">·</span>
                    <a href="#recently-listed" class="text-white text-opacity-85 text-decoration-none hero-clean__link d-inline-flex align-items-center gap-1">
                        Latest listings <i class="bi bi-arrow-down-short fs-5" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<main class="wvsu-home-flow">

<section id="get-started" class="wvsu-home-panel wvsu-process-show scroll-margin-top" data-io-animate>
    <div class="container wvsu-home-container py-5 py-lg-6">
        <div class="wvsu-process-show__ribbon mx-auto mb-4 mb-lg-5">
            <span class="wvsu-process-show__ribbon-dot"></span>
            Peer-to-peer, on campus
            <span class="wvsu-process-show__ribbon-sep"></span>
            Chat stays on-platform
            <span class="wvsu-process-show__ribbon-sep"></span>
            Browse free — list when you&apos;re ready
        </div>

        <header class="wvsu-process-show__head text-center mx-auto mb-4 mb-lg-5">
            <span class="wvsu-home-eyebrow">Our process</span>
            <h2 class="wvsu-home-section-title mb-3">Three moves, one flow — <span class="wvsu-home-section-title-accent">deal closed, awkwardness optional.</span></h2>
            <p class="wvsu-home-section-desc mx-auto mb-4">Here&apos;s the play-by-play: list or browse → hash it out in Messages → meet somewhere everyone already passes through. Campus marketplace energy, minus the scavenger hunt.</p>

            <div class="wvsu-process-show__cta-bar justify-content-center" role="navigation" aria-label="Quick marketplace links">
                <a href="products.php" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                    <i class="bi bi-bag-fill me-1" aria-hidden="true"></i>Browse products
                </a>
                <a href="services.php" class="btn btn-outline-primary rounded-pill px-4 fw-semibold">
                    <i class="bi bi-palette2 me-1" aria-hidden="true"></i>Browse services
                </a>
                <a href="<?= htmlspecialchars($msgHref) ?>" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold">
                    <i class="bi bi-chat-dots-fill me-1" aria-hidden="true"></i>Open Messages
                </a>
                <a href="#recently-listed" class="btn btn-link fw-semibold text-decoration-none py-2">See latest listings <i class="bi bi-arrow-down-short ms-1" aria-hidden="true"></i></a>
            </div>
        </header>

        <div class="wvsu-process-show__timeline wvsu-stagger">
            <article class="wvsu-process-step" aria-label="Step 1: Lead with clarity">
                <div class="wvsu-process-step__body">
                    <div class="wvsu-process-step__bubble wvsu-process-step__bubble--1" aria-hidden="true">
                        <span class="wvsu-process-step__bubble-kicker">Step</span>
                        <span class="wvsu-process-step__bubble-digit">1</span>
                    </div>
                    <div class="wvsu-process-step__icon-wrap" aria-hidden="true"><i class="bi bi-camera"></i></div>
                    <h3 class="wvsu-process-step__hook">Lead with clarity</h3>
                    <p class="wvsu-process-step__lead">Bold photos. Straight price tag. Answers to &ldquo;how much?&rdquo; and &ldquo;still available?&rdquo; before they ping you.</p>
                    <ul class="wvsu-process-step__bullets list-unstyled mb-0">
                        <li><i class="bi bi-check-circle-fill text-primary" aria-hidden="true"></i> <span>Works for <strong>products</strong> and <strong>services</strong> — spell out what buyers actually receive.</span></li>
                        <li><i class="bi bi-check-circle-fill text-primary" aria-hidden="true"></i> <span>Honest descriptions &rarr; fewer flaky meet-ups.</span></li>
                    </ul>
                    <div class="wvsu-process-step__actions">
                        <a href="<?= htmlspecialchars($productSell) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">List a product</a>
                        <a href="<?= htmlspecialchars($serviceSell) ?>" class="btn btn-sm btn-warning rounded-pill px-3 fw-semibold text-dark">Offer a service</a>
                        <a href="<?= htmlspecialchars($yourListHref) ?>" class="btn btn-link btn-sm px-2 fw-semibold">Manage listings</a>
                    </div>
                </div>
            </article>

            <article class="wvsu-process-step" aria-label="Step 2: Lock in with Messages">
                <div class="wvsu-process-step__body">
                    <div class="wvsu-process-step__bubble wvsu-process-step__bubble--2" aria-hidden="true">
                        <span class="wvsu-process-step__bubble-kicker">Step</span>
                        <span class="wvsu-process-step__bubble-digit">2</span>
                    </div>
                    <div class="wvsu-process-step__icon-wrap" aria-hidden="true"><i class="bi bi-chat-dots-fill"></i></div>
                    <h3 class="wvsu-process-step__hook">Lock in with Messages</h3>
                    <p class="wvsu-process-step__lead">Negotiate time, pickup spot, and final price inside WVSU Connect — neat paper trail when you need it.</p>
                    <ul class="wvsu-process-step__bullets list-unstyled mb-0">
                        <li><i class="bi bi-check-circle-fill text-primary" aria-hidden="true"></i> No more scavenger hunts through three group chats.</li>
                        <li><i class="bi bi-check-circle-fill text-primary" aria-hidden="true"></i> Keep it campus-friendly until you&apos;re ready to meet.</li>
                    </ul>
                    <div class="wvsu-process-step__actions">
                        <a href="<?= htmlspecialchars($msgHref) ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold">
                            <i class="bi bi-chat-dots me-1" aria-hidden="true"></i>Open Messages
                        </a>
                        <a href="safety.php" class="btn btn-link btn-sm px-2 fw-semibold">Safety tips</a>
                    </div>
                </div>
            </article>

            <article class="wvsu-process-step" aria-label="Step 3: Meet where people already are">
                <div class="wvsu-process-step__body">
                    <div class="wvsu-process-step__bubble wvsu-process-step__bubble--3" aria-hidden="true">
                        <span class="wvsu-process-step__bubble-kicker">Step</span>
                        <span class="wvsu-process-step__bubble-digit">3</span>
                    </div>
                    <div class="wvsu-process-step__icon-wrap" aria-hidden="true"><i class="bi bi-geo-alt-fill"></i></div>
                    <h3 class="wvsu-process-step__hook">Meet where people already are</h3>
                    <p class="wvsu-process-step__lead">Prefer busy hubs — think <strong>Quezon Hall</strong> markers or <strong>CAF court</strong>. Inspect goods, handshake on service scope, pay when satisfied.</p>
                    <ul class="wvsu-process-step__bullets list-unstyled mb-0">
                        <li><i class="bi bi-check-circle-fill text-primary" aria-hidden="true"></i> Bring a buddy if you&apos;re meeting someone new.</li>
                        <li><i class="bi bi-check-circle-fill text-primary" aria-hidden="true"></i> If it feels off, walk away — your call, full stop.</li>
                    </ul>
                    <div class="wvsu-process-step__actions">
                        <a href="products.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">Hunt deals</a>
                        <a href="services.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">Find services</a>
                    </div>
                </div>
            </article>
        </div>

        <p class="text-center text-muted small mt-5 mb-0 pb-lg-1">
            <a href="safety.php" class="fw-semibold text-decoration-none">Safety &amp; meetups<i class="bi bi-arrow-right-short fs-5 align-middle" aria-hidden="true"></i></a>
            — meet smart on campus.</p>
    </div>
</section>

<div id="recently-listed" class="container wvsu-home-container py-5 py-lg-6 scroll-margin-top mb-5" data-io-animate>
    <header class="wvsu-home-section-head wvsu-home-section-head--split d-flex flex-column flex-lg-row justify-content-lg-between align-items-lg-start gap-3 mb-4 mb-lg-5">
        <div class="text-center text-lg-start">
            <span class="wvsu-home-eyebrow">Fresh drops</span>
            <h2 class="wvsu-home-section-title mb-2 mb-lg-1">Recently listed</h2>
            <p class="wvsu-home-section-desc mb-0 mx-auto mx-lg-0">Latest products and services from your campus marketplace.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-end align-items-center flex-shrink-0">
            <a href="products.php" class="btn btn-primary rounded-pill px-4 fw-semibold wvsu-home-chip-btn shadow-sm"><i class="bi bi-bag-fill me-1"></i>All products</a>
            <a href="services.php" class="btn btn-outline-primary rounded-pill px-4 fw-semibold wvsu-home-chip-btn"><i class="bi bi-palette2 me-1"></i>All services</a>
        </div>
    </header>

    <?php
    $recentListSqlBase = '
         SELECT l.*, c.name AS category_name, u.full_name AS owner_name, p.price, p.stock, s.rate, s.rate_type
         FROM listings l
         LEFT JOIN categories c ON l.category_id = c.category_id
         LEFT JOIN users u ON l.owner_id = u.user_id
         LEFT JOIN products p ON p.listing_id = l.listing_id
         LEFT JOIN services s ON s.listing_id = l.listing_id
         WHERE l.status = ? AND l.listing_type = ?
         ORDER BY l.created_at DESC
         LIMIT 4';
    $recentProducts = fetchAll($recentListSqlBase, ['active', 'product']);
    $recentServices = fetchAll($recentListSqlBase, ['active', 'service']);
    $hasRecentAny = ($recentProducts !== [] || $recentServices !== []);
    ?>

    <?php if (! $hasRecentAny): ?>
        <div class="alert alert-light border rounded-4 text-muted">Nothing new yet — open <a href="products.php" class="fw-semibold">products</a> or <a href="services.php" class="fw-semibold">services</a> anytime.</div>
    <?php else: ?>
        <?php if ($recentProducts !== []): ?>
            <div class="mb-5" id="recent-products">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h3 class="h5 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary" style="width:2rem;height:2rem;"><i class="bi bi-bag-fill"></i></span>
                        Recent products
                    </h3>
                    <a href="products.php" class="small fw-semibold text-decoration-none">View all products <i class="bi bi-arrow-right-short fs-5 align-middle"></i></a>
                </div>
                <div class="row g-4 wvsu-stagger">
                    <?php foreach ($recentProducts as $item): wvsu_index_render_recent_card($item); endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($recentServices !== []): ?>
            <div class="<?= $recentProducts !== [] ? 'mt-5 pt-2 border-top border-secondary-subtle' : '' ?>" id="recent-services">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 <?= $recentProducts !== [] ? 'mt-4' : '' ?>">
                    <h3 class="h5 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-info-subtle text-info" style="width:2rem;height:2rem;"><i class="bi bi-palette2"></i></span>
                        Recent services
                    </h3>
                    <a href="services.php" class="small fw-semibold text-info text-decoration-none">View all services <i class="bi bi-arrow-right-short fs-5 align-middle"></i></a>
                </div>
                <div class="row g-4 wvsu-stagger">
                    <?php foreach ($recentServices as $item): wvsu_index_render_recent_card($item); endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

    <section class="container wvsu-home-container mb-4 mb-lg-5 pb-3" aria-label="Get started selling" data-io-animate>
        <div class="wvsu-process-show__finale rounded-4 p-4 p-lg-5 text-center">
            <p class="wvsu-process-show__finale-tag text-uppercase mb-2">Go time</p>
            <h3 class="wvsu-process-show__finale-title mb-2">Thousands of taps start with one — yours can be next.</h3>
            <p class="wvsu-process-show__finale-desc mx-auto mb-4 mb-lg-5">Shopping, hustling side income, booking a barkada artist? Same playbook: list or browse → message → meet on campus.</p>
            <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 justify-content-center align-items-stretch align-items-sm-center">
                <a href="<?= htmlspecialchars($productSell) ?>" class="btn btn-warning rounded-pill px-4 fw-bold text-dark shadow-sm"><i class="bi bi-tag-fill me-1" aria-hidden="true"></i>Sell something today</a>
                <a href="<?= htmlspecialchars($serviceSell) ?>" class="btn btn-outline-light rounded-pill px-4 fw-semibold border-2">Monetize a skill</a>
                <a href="#recently-listed" class="btn btn-link fw-semibold text-white text-opacity-90">Jump to newest listings ↑</a>
            </div>
        </div>
    </section>

</main>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
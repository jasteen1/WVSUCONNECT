<?php
require_once 'db_conn.php';
require_once __DIR__ . '/service_pricing.inc.php';
require_once __DIR__ . '/profiles_reviews.inc.php';
require_once __DIR__ . '/wvsu_smart_back.inc.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid listing.');
}

$sql = 'SELECT l.*, s.rate, s.rate_type, u.full_name AS owner_name, u.user_id AS owner_user_id, u.updated_at AS owner_updated_at
    FROM listings l
    JOIN services s ON s.listing_id = l.listing_id
    JOIN users u ON u.user_id = l.owner_id
    WHERE l.listing_id = ? AND l.listing_type = \'service\' LIMIT 1';
$item = fetch_master($sql, [(string) $id]);
if (! $item) {
    die('Service not found.');
}
$wvsu_listing_review_id = (int) $item['listing_id'];
$listRevStats = wvsu_listing_review_stats($wvsu_listing_review_id);

$wvsuReturnTo = wvsu_safe_listing_return_url(isset($_GET['return']) ? (string) $_GET['return'] : '');
$wvsuListingBackHref = $wvsuReturnTo !== '' ? $wvsuReturnTo : 'services.php';
$wvsuListingBackUseHistory = $wvsuReturnTo === '';

$portfolio = fetchAll_master(
    'SELECT portfolio_id, media_type, file_path, grid_span FROM service_portfolio_items
     WHERE listing_id = ? ORDER BY sort_order ASC, portfolio_id ASC',
    [(string) $id]
);
$priceItems = fetchAll_master(
    'SELECT item_name, amount FROM service_pricing_items WHERE listing_id = ? ORDER BY sort_order ASC, price_item_id ASC',
    [(string) $id]
);

$svcViewerUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$svcIsOwnerLoggedIn = $svcViewerUserId > 0 && (int) ($item['owner_id'] ?? 0) === $svcViewerUserId;

function wvsu_rate_label(string $rateType): string
{
    switch ($rateType) {
        case 'per_hour':
            return '/hr';
        case 'per_task':
            return ' / project';
        case 'negotiable':
            return '';
        default:
            return '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d4daa">
    <title><?= htmlspecialchars($item['title']) ?> — WVSU CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
    <style>
        .svc-portfolio-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: .75rem;
        }
        .svc-portfolio-grid .svc-tile {
            border-radius: 1rem;
            overflow: hidden;
            background: #0f172a;
            min-height: 160px;
            position: relative;
        }
        .svc-portfolio-grid .svc-tile.span-2 {
            grid-column: span 2;
            min-height: 220px;
        }
        .svc-portfolio-grid img, .svc-portfolio-grid video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        @media (max-width: 576px) {
            .svc-portfolio-grid { grid-template-columns: 1fr; }
            .svc-portfolio-grid .svc-tile.span-2 { grid-column: span 1; min-height: 200px; }
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4 pb-5 wvsu-pan-soft">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= htmlspecialchars($wvsuListingBackHref, ENT_QUOTES, 'UTF-8') ?>"
           class="text-dark text-decoration-none me-3"
           title="Back"
           <?= $wvsuListingBackUseHistory ? 'data-wvsu-smart-back="1"' : '' ?>><i class="bi bi-arrow-left fs-2"></i></a>
        <div>
            <h4 class="mb-0 fw-bold">Service</h4>
            <span class="text-muted small">Portfolio &amp; details</span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <?php if (!empty($item['image_url'])): ?>
                <?php
                $cov = wvsu_listing_media_href((string) $item['image_url']);
                $vidCov = preg_match('/\.(mp4|webm|mov)$/i', $cov);
                ?>
                <?php if ($vidCov): ?>
                    <video class="w-100 rounded-4 shadow" controls playsinline src="<?= htmlspecialchars($cov, ENT_QUOTES, 'UTF-8') ?>"></video>
                <?php else: ?>
                    <img src="<?= htmlspecialchars($cov, ENT_QUOTES, 'UTF-8') ?>" class="w-100 rounded-4 shadow object-fit-cover" style="max-height:380px;object-fit:cover;" alt="">
                <?php endif; ?>
            <?php else: ?>
                <div class="ratio ratio-4x3 bg-light rounded-4 d-flex align-items-center justify-content-center text-muted">
                    <i class="bi bi-person-workspace display-3"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-7">
            <h2 class="fw-bold"><?= htmlspecialchars($item['title']) ?></h2>
            <p class="text-muted small mb-2">Updated <?= date('M j, Y', strtotime($item['updated_at'] ?? $item['created_at'])) ?></p>
            <?php
            $suffix = wvsu_rate_label((string) ($item['rate_type'] ?? 'fixed'));
            ?>
            <?php if (($item['rate_type'] ?? '') === 'negotiable' && empty($priceItems)): ?>
                <p class="fs-4 fw-bold text-primary mb-3">Negotiable <span class="fs-6 fw-semibold text-muted">/ quote based</span></p>
            <?php else: ?>
                <p class="fs-3 fw-bold text-primary mb-3">₱<?= number_format((float) $item['rate'], 2) ?><span class="fs-6 fw-semibold text-muted"><?= htmlspecialchars($suffix) ?></span></p>
            <?php endif; ?>
            <div class="fs-6 mb-4"><?= nl2br(htmlspecialchars((string) $item['description'])) ?></div>
            <?php if (!empty($priceItems)): ?>
                <div class="border rounded-4 p-3 mb-4 bg-white">
                    <h3 class="h6 fw-bold mb-2"><i class="bi bi-receipt me-2 text-primary"></i>Price list</h3>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                            <?php foreach ($priceItems as $pi): ?>
                                <tr>
                                    <td class="text-muted"><?= htmlspecialchars((string) $pi['item_name']) ?></td>
                                    <td class="text-end fw-semibold">
                                        <?php if ($pi['amount'] === null): ?>
                                            Quote first
                                        <?php else: ?>
                                            ₱<?= number_format((float) $pi['amount'], 2) ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            $fid = (int) ($item['owner_user_id'] ?? $item['owner_id'] ?? 0);
            $fstats = $fid > 0 ? wvsu_review_average_for_user($fid) : ['avg' => 0.0, 'count' => 0];
            $fav = $fid > 0
                ? htmlspecialchars(wvsu_user_avatar_img_src($fid, (string) ($item['owner_updated_at'] ?? '')), ENT_QUOTES, 'UTF-8')
                : htmlspecialchars('https://ui-avatars.com/api/?name=' . rawurlencode((string) ($item['owner_name'] ?? 'Freelancer')) . '&background=0d4daa&color=fff&size=96', ENT_QUOTES, 'UTF-8');
            ?>
            <div class="card border-0 bg-light rounded-4 p-3 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?= $fav ?>" alt="" class="rounded-circle" width="48" height="48" style="object-fit:cover;">
                    <div class="flex-grow-1">
                        <div class="small text-muted fw-semibold text-uppercase" style="letter-spacing:.04em;">Freelancer</div>
                        <div class="fw-semibold"><?= htmlspecialchars((string) ($item['owner_name'] ?? 'Freelancer')) ?></div>
                        <div class="small text-muted">
                            <?php if ($fstats['count'] > 0): ?>
                                <?= htmlspecialchars(number_format($fstats['avg'], 1)) ?>★ · <?= (int) $fstats['count'] ?> profile review<?= $fstats['count'] === 1 ? '' : 's' ?>
                            <?php else: ?>
                                New on WVSU CONNECT
                            <?php endif; ?>
                            <?php if ($listRevStats['count'] > 0): ?>
                                <span class="d-block mt-1 text-primary fw-semibold">
                                    This service: <?= htmlspecialchars(number_format($listRevStats['avg'], 1)) ?>★ · <?= (int) $listRevStats['count'] ?> review<?= $listRevStats['count'] === 1 ? '' : 's' ?>
                                </span>
                                <a href="#wvsu-listing-reviews" class="d-inline-block mt-1 small fw-semibold">Read buyer reviews below ↓</a>
                            <?php else: ?>
                                <span class="d-block mt-1 small text-muted">No buyer reviews yet — feedback appears below after completed sales.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($fid > 0): ?>
                        <a href="profile.php?id=<?= $fid ?>" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold flex-shrink-0">View profile</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <?php if (! $svcIsOwnerLoggedIn): ?>
                    <a href="contact.php?listing_id=<?= intval($item['listing_id']) ?>" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-chat-dots me-1"></i> Message freelancer
                    </a>
                <?php endif; ?>
                <?php if ($svcIsOwnerLoggedIn): ?>
                    <a href="edit_listing.php?id=<?= intval($item['listing_id']) ?>" class="btn btn-primary rounded-pill px-4 fw-semibold"
                       onclick="return confirm('Are you sure you want to edit this service listing?');">Edit listing</a>
                <?php elseif (!empty($_SESSION['user_id'])): ?>
                    <button class="btn btn-outline-danger rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#reportServiceUser">
                        <i class="bi bi-flag me-1"></i>Report freelancer
                    </button>
                <?php endif; ?>
            </div>
            <?php if (!empty($_SESSION['user_id']) && intval($item['owner_id']) !== (int)($_SESSION['user_id'] ?? 0)): ?>
                <div id="reportServiceUser" class="collapse mt-3">
                    <form method="post" action="process-report.php" class="card card-body border-danger-subtle">
                        <input type="hidden" name="target_user_id" value="<?= intval($item['owner_id']) ?>">
                        <input type="hidden" name="listing_id" value="<?= intval($item['listing_id']) ?>">
                        <input type="hidden" name="return_to" value="view-service.php?id=<?= intval($item['listing_id']) ?>">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Reason</label>
                                <select name="reason_type" class="form-select form-select-sm" required>
                                    <option value="scam">Scam / fake</option>
                                    <option value="unwanted_item">Unwanted or prohibited service</option>
                                    <option value="harassment">Harassment</option>
                                    <option value="fake_profile">Fake profile</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Details</label>
                                <input type="text" name="details" class="form-control form-control-sm" maxlength="300" placeholder="Describe the issue">
                            </div>
                        </div>
                        <div class="mt-2"><button class="btn btn-sm btn-danger">Submit report</button></div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/listing_reviews_block.inc.php'; ?>

    <?php if (!empty($portfolio)): ?>
        <hr class="my-5 opacity-25">
        <h3 class="h5 fw-bold mb-3"><i class="bi bi-images me-2 text-primary"></i>Portfolio</h3>
        <div class="svc-portfolio-grid">
            <?php foreach ($portfolio as $pf):
                $path = wvsu_listing_media_href((string) $pf['file_path']);
                $span = max(1, min(2, (int) ($pf['grid_span'] ?? 1)));
                $tileClass = 'svc-tile shadow-sm' . ($span === 2 ? ' span-2' : '');
                ?>
                <?php if ($pf['media_type'] === 'video'): ?>
                    <div class="<?= $tileClass ?>">
                        <video src="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" controls playsinline preload="metadata"></video>
                    </div>
                <?php else: ?>
                    <div class="<?= $tileClass ?>">
                        <img src="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

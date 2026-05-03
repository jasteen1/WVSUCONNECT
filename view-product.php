<?php
require_once 'db_conn.php';
require_once __DIR__ . '/profiles_reviews.inc.php';
require_once __DIR__ . '/wvsu_smart_back.inc.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid product ID.');
}

$sql = 'SELECT l.*, p.price, p.stock, u.full_name AS owner_name, u.user_id AS owner_user_id, u.updated_at AS owner_updated_at
    FROM listings l
    JOIN products p ON p.listing_id = l.listing_id
    JOIN users u ON u.user_id = l.owner_id
    WHERE l.listing_id = ? LIMIT 1';
$item = fetch_master($sql, [(string) $id]);
if (! $item) {
    die('Product not found.');
}
$wvsu_listing_review_id = (int) $item['listing_id'];
$listRevStats = wvsu_listing_review_stats($wvsu_listing_review_id);

$wvsuReturnTo = wvsu_safe_listing_return_url(isset($_GET['return']) ? (string) $_GET['return'] : '');
$wvsuListingBackHref = $wvsuReturnTo !== '' ? $wvsuReturnTo : 'products.php';
$wvsuListingBackUseHistory = $wvsuReturnTo === '';
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
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5 pb-5 wvsu-pan-soft">
    <div class="d-flex align-items-center mb-4 pb-3">
    <a href="<?= htmlspecialchars($wvsuListingBackHref, ENT_QUOTES, 'UTF-8') ?>"
       class="text-dark text-decoration-none me-3 arrow-hover"
       title="Back"
       <?= $wvsuListingBackUseHistory ? 'data-wvsu-smart-back="1"' : '' ?>><i class="bi bi-arrow-left fs-2"></i></a>
    
    <div>
        <h4 class="mb-0 fw-bold">Product details</h4>
        <span class="text-muted small">From a fellow student • Message safely through WVSU CONNECT</span>
    </div>
</div>
<style>
    .arrow-hover {
        transition: transform 0.2s ease, color 0.2s ease;
    }
    .arrow-hover:hover {
        color: #0d6efd; /* Bootstrap primary blue */
        transform: translateX(-3px);
    }
</style>
    <div class="row">
        <div class="col-md-6">
            <img src="<?= $item['image_url'] ? htmlspecialchars($item['image_url']) : 'https://via.placeholder.com/600x400?text=No+Image' ?>" class="img-fluid rounded-4 shadow" alt="">
        </div>
        <div class="col-md-6">
            <h2 class="fw-bold"><?= htmlspecialchars($item['title']) ?></h2>
            <p class="text-muted small">Posted: <?= date('M j, Y', strtotime($item['created_at'])) ?></p>
            <h3 class="text-primary">₱<?= number_format($item['price'],2) ?></h3>
            <p class="mt-4"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
            <p class="small text-muted">Stock: <?= intval($item['stock']) ?></p>

            <?php
            $oid = (int) ($item['owner_user_id'] ?? $item['owner_id'] ?? 0);
            $ownStats = $oid > 0 ? wvsu_review_average_for_user($oid) : ['avg' => 0.0, 'count' => 0];
            $oav = $oid > 0
                ? htmlspecialchars(wvsu_user_avatar_img_src($oid, (string) ($item['owner_updated_at'] ?? '')), ENT_QUOTES, 'UTF-8')
                : htmlspecialchars('https://ui-avatars.com/api/?name=' . rawurlencode((string) ($item['owner_name'] ?? 'Seller')) . '&background=0d4daa&color=fff&size=96', ENT_QUOTES, 'UTF-8');
            ?>
            <div class="card border-0 bg-light rounded-4 p-3 mt-4">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?= $oav ?>" alt="" class="rounded-circle" width="48" height="48" style="object-fit:cover;">
                    <div class="flex-grow-1">
                        <div class="small text-muted fw-semibold text-uppercase" style="letter-spacing:.04em;">Seller</div>
                        <div class="fw-semibold"><?= htmlspecialchars((string) ($item['owner_name'] ?? 'Seller')) ?></div>
                        <div class="small text-muted">
                            <?php if ($ownStats['count'] > 0): ?>
                                <?= htmlspecialchars(number_format($ownStats['avg'], 1)) ?>★ · <?= (int) $ownStats['count'] ?> profile review<?= $ownStats['count'] === 1 ? '' : 's' ?>
                            <?php else: ?>
                                New on WVSU CONNECT
                            <?php endif; ?>
                            <?php if ($listRevStats['count'] > 0): ?>
                                <span class="d-block mt-1 text-primary fw-semibold">
                                    This product: <?= htmlspecialchars(number_format($listRevStats['avg'], 1)) ?>★ · <?= (int) $listRevStats['count'] ?> review<?= $listRevStats['count'] === 1 ? '' : 's' ?>
                                </span>
                                <a href="#wvsu-listing-reviews" class="d-inline-block mt-1 small fw-semibold">Read buyer reviews below ↓</a>
                            <?php else: ?>
                                <span class="d-block mt-1 small text-muted">No buyer reviews yet — first purchase feedback will show below.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($oid > 0): ?>
                        <a href="profile.php?id=<?= $oid ?>" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold flex-shrink-0">View profile</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <a href="contact.php?listing_id=<?= intval($item['listing_id']) ?>" class="btn btn-outline-primary">Contact Seller</a>
                <?php if (intval($item['owner_id']) === (int)($_SESSION['user_id'] ?? 0)): ?>
                    <span class="badge bg-secondary">Your listing</span>
                <?php else: ?>
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <button class="btn btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#reportSellerForm" aria-expanded="false">
                            <i class="bi bi-flag me-1"></i>Report seller
                        </button>
                    <?php endif; ?>
                    <?php if (intval($item['stock']) <= 0): ?>
                        <span class="badge bg-danger">Sold out</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($_SESSION['user_id']) && intval($item['owner_id']) !== (int)($_SESSION['user_id'] ?? 0)): ?>
            <div id="reportSellerForm" class="collapse mt-3">
                <form method="post" action="process-report.php" class="card card-body border-danger-subtle">
                    <input type="hidden" name="target_user_id" value="<?= intval($item['owner_id']) ?>">
                    <input type="hidden" name="listing_id" value="<?= intval($item['listing_id']) ?>">
                    <input type="hidden" name="return_to" value="view-product.php?id=<?= intval($item['listing_id']) ?>">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Reason</label>
                            <select name="reason_type" class="form-select form-select-sm" required>
                                <option value="scam">Scam / fake</option>
                                <option value="unwanted_item">Unwanted / prohibited item</option>
                                <option value="harassment">Harassment</option>
                                <option value="fake_profile">Fake profile</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Details</label>
                            <input type="text" name="details" class="form-control form-control-sm" maxlength="300" placeholder="Briefly explain the issue">
                        </div>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-danger">Submit report</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/listing_reviews_block.inc.php'; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

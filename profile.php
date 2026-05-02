<?php
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/profiles_reviews.inc.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('HTTP/1.0 404 Not Found');
    exit('Profile not found.');
}

$user = fetch_master(
    'SELECT user_id, full_name, profile_pic_url, bio, social_instagram, social_facebook, social_x,
            social_tiktok, social_linkedin, social_website, is_active, role_id, updated_at
     FROM users WHERE user_id = ? LIMIT 1',
    [(string) $id]
);
if (! $user) {
    header('HTTP/1.0 404 Not Found');
    exit('Profile not found.');
}

$meId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$isOwn = $meId === $id;
$isAdmin = ! empty($_SESSION['role_id']) && (int) $_SESSION['role_id'] === 1;
$isBlocked = (int) ($user['is_active'] ?? 1) !== 1;
$canViewFull = ! $isBlocked || $isOwn || $isAdmin;

$stats = wvsu_review_average_for_user($id);
$reviews = [];
if ($canViewFull) {
    $reviews = fetchAll(
        'SELECT r.rating, r.comment, r.created_at, u.full_name AS reviewer_name, u.user_id AS reviewer_id
         FROM user_reviews r
         JOIN users u ON u.user_id = r.reviewer_id
         WHERE r.reviewee_id = ?
         ORDER BY r.created_at DESC',
        [(string) $id]
    );
}

$profileListingsProducts = [];
$profileListingsServices = [];
if ($canViewFull) {
    $profileListingsProducts = fetchAll(
        'SELECT listing_id, title, listing_type, image_url, status
         FROM listings
         WHERE owner_id = ? AND status = ? AND listing_type = ?
         ORDER BY created_at DESC
         LIMIT 12',
        [(string) $id, 'active', 'product']
    );
    $profileListingsServices = fetchAll(
        'SELECT listing_id, title, listing_type, image_url, status
         FROM listings
         WHERE owner_id = ? AND status = ? AND listing_type = ?
         ORDER BY created_at DESC
         LIMIT 12',
        [(string) $id, 'active', 'service']
    );
}

$profileHasAnyListings = $profileListingsProducts !== [] || $profileListingsServices !== [];

$myReview = null;
if ($meId > 0 && $meId !== $id && $canViewFull) {
    $myReview = fetch(
        'SELECT rating, comment FROM user_reviews WHERE reviewer_id = ? AND reviewee_id = ? LIMIT 1',
        [(string) $meId, (string) $id]
    );
}

function wvsu_render_stars(float $avg): string
{
    $r = (int) round(max(0, min(5, $avg)));
    $html = '<span class="wvsu-star-row" aria-label="Rating ' . htmlspecialchars((string) $avg) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $cls = $i <= $r ? 'bi bi-star-fill text-warning' : 'bi bi-star text-secondary opacity-25';
        $html .= '<i class="' . $cls . '"></i>';
    }
    $html .= '</span>';
    return $html;
}

$pageTitle = htmlspecialchars((string) $user['full_name']) . ' — WVSU CONNECT';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d4daa">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>

<div class="container mt-4 pb-5 wvsu-pan-soft">
    <?php if (! empty($_GET['review_saved'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4">Thanks — your review was saved.</div>
    <?php endif; ?>
    <?php if (! empty($_GET['saved'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4">Profile updated.</div>
    <?php endif; ?>
    <?php if (isset($_GET['review_error']) && $_GET['review_error'] === 'self'): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4">You cannot review your own profile.</div>
    <?php endif; ?>

    <?php if ($isBlocked && ! $isOwn && ! $isAdmin): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4">This account is not available.</div>
    <?php else: ?>

    <div class="card border-0 shadow-sm overflow-hidden market-card mb-4" data-io-animate>
        <div class="card-body p-4 p-lg-5">
            <div class="row g-4 align-items-center">
                <div class="col-auto">
                    <?php
                    $picSrc = htmlspecialchars(
                        wvsu_user_avatar_img_src($id, (string) ($user['updated_at'] ?? '')),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                    <img src="<?= $picSrc ?>" alt="" class="rounded-4 shadow-sm" width="120" height="120" style="object-fit:cover;width:120px;height:120px;">
                </div>
                <div class="col">
                    <h1 class="h3 fw-bold mb-1"><?= htmlspecialchars((string) $user['full_name']) ?></h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <?= wvsu_render_stars($stats['avg']) ?>
                        <span class="text-muted small">
                            <?= $stats['count'] > 0
                                ? htmlspecialchars(number_format($stats['avg'], 1)) . ' · ' . (int) $stats['count'] . ' review' . ($stats['count'] === 1 ? '' : 's')
                                : 'No reviews yet' ?>
                        </span>
                    </div>
                    <?php if ($canViewFull && trim((string) ($user['bio'] ?? '')) !== ''): ?>
                        <p class="text-secondary mb-3 mb-lg-0" style="max-width:40rem;"><?= nl2br(htmlspecialchars((string) $user['bio'])) ?></p>
                    <?php elseif ($canViewFull): ?>
                        <p class="text-muted small mb-0 fst-italic">No bio yet.</p>
                    <?php endif; ?>
                </div>
                <div class="col-lg-auto text-lg-end">
                    <?php if ($isOwn): ?>
                        <a href="edit_profile.php" class="btn btn-primary rounded-pill px-4 fw-semibold">
                            <i class="bi bi-pencil-square me-1"></i> Edit profile
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($canViewFull): ?>
            <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top border-secondary-subtle">
                <?php
                $links = [
                    'social_instagram' => ['Instagram', 'bi-instagram', $user['social_instagram'] ?? null],
                    'social_facebook' => ['Facebook', 'bi-facebook', $user['social_facebook'] ?? null],
                    'social_x' => ['X', 'bi-twitter-x', $user['social_x'] ?? null],
                    'social_tiktok' => ['TikTok', 'bi-tiktok', $user['social_tiktok'] ?? null],
                    'social_linkedin' => ['LinkedIn', 'bi-linkedin', $user['social_linkedin'] ?? null],
                    'social_website' => ['Website', 'bi-link-45deg', $user['social_website'] ?? null],
                ];
                $anySocial = false;
                foreach ($links as $row) {
                    if (! empty($row[2])) {
                        $anySocial = true;
                        $u = htmlspecialchars((string) $row[2]);
                        echo '<a href="' . $u . '" class="btn btn-sm btn-outline-secondary rounded-pill" target="_blank" rel="noopener noreferrer"><i class="bi ' . htmlspecialchars($row[1]) . ' me-1"></i>' . htmlspecialchars($row[0]) . '</a>';
                    }
                }
                if (! $anySocial): ?>
                    <span class="text-muted small">No social links added yet.</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canViewFull && $profileHasAnyListings): ?>
    <section class="mb-5 wvsu-profile-listings" data-io-animate>
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-4">
            <h2 class="h5 fw-bold mb-0"><i class="bi bi-shop-window me-2 text-primary"></i>Active listings</h2>
            <?php if ($isOwn): ?>
                <div class="d-flex flex-wrap gap-2">
                    <a href="addproduct.php" class="btn btn-sm btn-outline-primary rounded-pill fw-semibold"><i class="bi bi-plus-lg me-1"></i>Product</a>
                    <a href="addservice.php" class="btn btn-sm btn-outline-info rounded-pill fw-semibold text-info-emphasis"><i class="bi bi-plus-lg me-1"></i>Service</a>
                </div>
            <?php endif; ?>
        </div>

        <?php
        $wvsuProfileListingCard = static function (array $L): void {
            $isSvc = (($L['listing_type'] ?? '') === 'service');
            $href = $isSvc ? 'view-service.php?id=' : 'view-product.php?id=';
            $href .= (int) ($L['listing_id'] ?? 0);
            $img = ! empty($L['image_url'])
                ? htmlspecialchars((string) $L['image_url'], ENT_QUOTES, 'UTF-8')
                : 'https://via.placeholder.com/400x240?text=Listing';
            $badgeCls = $isSvc ? 'text-bg-info-subtle text-info-emphasis' : 'text-bg-primary-subtle text-primary-emphasis';
            $badgeTxt = $isSvc ? 'Service' : 'Product';
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm h-100 market-card overflow-hidden wvsu-profile-listing-card">
                        <div class="ratio ratio-4x3 bg-light"><img src="<?= $img ?>" class="object-fit-cover" alt=""></div>
                        <div class="card-body p-3">
                            <span class="badge <?= $badgeCls ?> mb-1 fw-semibold"><?= htmlspecialchars($badgeTxt) ?></span>
                            <div class="small fw-semibold text-truncate"><?= htmlspecialchars((string) $L['title']) ?></div>
                        </div>
                    </div>
                </a>
            </div>
            <?php
        };
        ?>

        <?php if ($profileListingsProducts !== []): ?>
            <div class="mb-5" id="profile-products">
                <h3 class="h6 fw-bold mb-3 pb-2 border-bottom border-primary border-opacity-25 d-flex align-items-center gap-2">
                    <i class="bi bi-bag-fill text-primary"></i> Products <span class="badge rounded-pill text-bg-light border fw-normal"><?= count($profileListingsProducts) ?></span>
                </h3>
                <div class="row g-3 wvsu-stagger">
                    <?php foreach ($profileListingsProducts as $L): $wvsuProfileListingCard($L); endforeach; ?>
                </div>
                <div class="text-end mt-2">
                    <a href="products.php" class="small fw-semibold text-decoration-none">Browse all products <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($profileListingsServices !== []): ?>
            <div id="profile-services" class="<?= $profileListingsProducts !== [] ? 'pt-4 border-top border-secondary-subtle' : '' ?>">
                <h3 class="h6 fw-bold mb-3 pb-2 border-bottom border-info border-opacity-25 d-flex align-items-center gap-2 <?= $profileListingsProducts !== [] ? 'mt-2' : '' ?>">
                    <i class="bi bi-palette2 text-info"></i> Services <span class="badge rounded-pill text-bg-light border fw-normal"><?= count($profileListingsServices) ?></span>
                </h3>
                <div class="row g-3 wvsu-stagger">
                    <?php foreach ($profileListingsServices as $L): $wvsuProfileListingCard($L); endforeach; ?>
                </div>
                <div class="text-end mt-2">
                    <a href="services.php" class="small fw-semibold text-info text-decoration-none">Browse all services <i class="bi bi-arrow-right-short"></i></a>
                </div>
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="mb-5" data-io-animate>
        <h2 class="h5 fw-bold mb-3"><i class="bi bi-chat-square-text me-2 text-primary"></i>Reviews</h2>
        <?php if ($meId > 0 && ! $isOwn && $canViewFull): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold mb-3"><?= $myReview ? 'Update your review' : 'Leave a review' ?></h3>
                    <?php $reviewRatingInit = ($myReview && (int) $myReview['rating'] >= 1 && (int) $myReview['rating'] <= 5)
                        ? (int) $myReview['rating']
                        : 0; ?>
                    <form method="post" action="process-review.php" id="wvsuReviewForm">
                        <input type="hidden" name="reviewee_id" value="<?= (int) $id ?>">
                        <input type="hidden" name="return_to" value="profile.php?id=<?= (int) $id ?>">
                        <fieldset class="mb-3 border-0 p-0 m-0">
                            <legend class="form-label small fw-semibold mb-2">Rating</legend>
                            <div id="wvsuStarRating" class="wvsu-review-stars-input d-inline-flex gap-1 align-items-center rounded-3" role="radiogroup" aria-label="Rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php $rid = 'wvsuRating' . $i; ?>
                                    <label class="wvsu-star-rate mb-0" for="<?= htmlspecialchars($rid) ?>">
                                        <input type="radio" name="rating" value="<?= $i ?>" id="<?= htmlspecialchars($rid) ?>"
                                               class="visually-hidden" <?= ($reviewRatingInit === $i) ? 'checked' : '' ?> <?= $i === 1 ? 'required' : '' ?>>
                                        <span class="wvsu-star-rate__glyph" aria-hidden="true"><i class="bi <?= ($reviewRatingInit >= $i) ? 'bi-star-fill' : 'bi-star' ?>"></i></span>
                                        <span class="visually-hidden"><?= $i ?> stars</span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </fieldset>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Comment</label>
                            <textarea name="comment" class="form-control" rows="3" maxlength="2000" placeholder="Share your experience..." required><?= $myReview ? htmlspecialchars((string) $myReview['comment']) : '' ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold"><?= $myReview ? 'Update review' : 'Submit review' ?></button>
                    </form>
                </div>
            </div>
        <?php elseif ($meId <= 0 && $canViewFull): ?>
            <p class="text-muted small"><a href="login.php?next=<?= rawurlencode('profile.php?id=' . $id) ?>">Log in</a> to leave a review.</p>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
            <?php if ($canViewFull && ! $isOwn): ?>
                <p class="text-muted">No written reviews yet — be the first to share feedback.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="vstack gap-3">
                <?php foreach ($reviews as $rv): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                <div>
                                    <a href="profile.php?id=<?= (int) $rv['reviewer_id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars((string) $rv['reviewer_name']) ?></a>
                                    <span class="text-muted small ms-2"><?= date('M j, Y', strtotime((string) $rv['created_at'])) ?></span>
                                </div>
                                <?= wvsu_render_stars((float) $rv['rating']) ?>
                            </div>
                            <p class="mb-0 small"><?= nl2br(htmlspecialchars((string) ($rv['comment'] ?? ''))) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var wrap = document.getElementById('wvsuStarRating');
    if (!wrap) return;
    var radios = wrap.querySelectorAll('input[type="radio"][name="rating"]');
    function valueFromRadios() {
        for (var r = 0; r < radios.length; r++) {
            if (radios[r].checked) return parseInt(radios[r].value, 10);
        }
        return 0;
    }
    function paint(n) {
        var show = Math.max(0, Math.min(5, n));
        for (var k = 0; k < radios.length; k++) {
            var v = parseInt(radios[k].value, 10);
            if (!(v >= 1 && v <= 5)) continue;
            var g = radios[k].parentNode.querySelector('.wvsu-star-rate__glyph i');
            if (!g) continue;
            var on = show >= v;
            g.classList.toggle('bi-star-fill', on);
            g.classList.toggle('bi-star', !on);
        }
    }
    function paintFromRadios() { paint(valueFromRadios()); }
    paintFromRadios();
    radios.forEach(function (el) {
        el.addEventListener('change', paintFromRadios);
    });
    [].slice.call(wrap.querySelectorAll('label.wvsu-star-rate')).forEach(function (lab) {
        var inp = lab.querySelector('input[type="radio"]');
        if (!inp) return;
        var star = parseInt(inp.value, 10);
        if (!(star >= 1 && star <= 5)) return;
        lab.addEventListener('mouseenter', function () { paint(star); });
    });
    wrap.addEventListener('mouseleave', paintFromRadios);
})();
</script>
</body>
</html>

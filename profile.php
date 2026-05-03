<?php
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/profiles_reviews.inc.php';
require_once __DIR__ . '/wvsu_smart_back.inc.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('HTTP/1.0 404 Not Found');
    exit('Profile not found.');
}

$user = fetch_master(
    'SELECT user_id, full_name, profile_pic_url, bio, social_instagram, social_facebook, social_x,
            social_tiktok, social_linkedin, social_website, college, year_level, course,
            is_active, role_id, updated_at
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
    $reviews = fetchAll_master(
        'SELECT r.review_id, r.rating, r.comment, r.created_at, r.photo_url, r.listing_id,
                r.seller_reply, r.seller_replied_at,
                COALESCE(NULLIF(TRIM(u.full_name), \'\'), CONCAT(\'Member #\', r.reviewer_id)) AS reviewer_name,
                r.reviewer_id,
                l.title AS reviewed_listing_title,
                l.listing_type AS reviewed_listing_type,
                c.name AS reviewed_category_name
         FROM user_reviews r
         LEFT JOIN users u ON u.user_id = r.reviewer_id
         LEFT JOIN listings l ON l.listing_id = r.listing_id
         LEFT JOIN categories c ON c.category_id = l.category_id
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

/** Legacy profile-only review row (if any); new purchase feedback is submitted only in Messages. */
$myReview = null;
if ($meId > 0 && $meId !== $id && $canViewFull) {
    $myReview = fetch_master(
        'SELECT review_id, rating, comment, created_at FROM user_reviews
         WHERE reviewer_id = ? AND reviewee_id = ? AND (listing_id IS NULL OR listing_id = 0)
         ORDER BY created_at DESC LIMIT 1',
        [(string) $meId, (string) $id]
    );
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
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            Thank you — your review is now public. For fairness to buyers and sellers, reviews can’t be edited after they’re posted.
        </div>
    <?php endif; ?>
    <?php if (! empty($_GET['reply_saved'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            Your reply is live. You can post one reply per review; keep it courteous and helpful.
        </div>
    <?php endif; ?>
    <?php if (! empty($_GET['saved'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4">Profile updated.</div>
    <?php endif; ?>
    <?php
    $reviewErr = isset($_GET['review_error']) ? (string) $_GET['review_error'] : '';
    $reviewErrMsg = match ($reviewErr) {
        'self' => 'You can’t review your own profile.',
        'review_locked' => 'You’ve already posted a review here. Like on major marketplaces, reviews can’t be changed once submitted.',
        'reply_forbidden' => 'You can only reply to reviews on your own profile.',
        'reply_once' => 'You’ve already replied to this review. One seller reply per review keeps the thread clear.',
        'reply_empty' => 'Please write something before posting your reply.',
        'reply_db' => 'Couldn’t save your reply — try again in a moment.',
        'db' => 'Couldn’t save your review — try again in a moment.',
        default => '',
    };
    ?>
    <?php if ($reviewErrMsg !== ''): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4"><?= htmlspecialchars($reviewErrMsg) ?></div>
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
                    <?php if ($canViewFull): ?>
                        <?php
                        $pCollege = wvsu_college_display((string) ($user['college'] ?? ''));
                        $pYear = wvsu_year_level_display((int) ($user['year_level'] ?? 0));
                        ?>
                        <?php if ($pCollege !== '' || $pYear !== ''): ?>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <?php if ($pCollege !== ''): ?>
                                    <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis border border-primary-subtle fw-semibold">
                                        <i class="bi bi-building me-1" aria-hidden="true"></i><?= htmlspecialchars($pCollege, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($pYear !== ''): ?>
                                    <span class="badge rounded-pill text-bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle fw-semibold">
                                        <i class="bi bi-mortarboard me-1" aria-hidden="true"></i><?= htmlspecialchars($pYear, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php
                        $pCourse = trim((string) ($user['course'] ?? ''));
                        if ($pCourse !== ''):
                            ?>
                            <p class="small text-secondary mb-2 mb-md-0" style="max-width:40rem;">
                                <span class="fw-semibold text-dark">Course:</span>
                                <?= htmlspecialchars($pCourse, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
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
        $wvsuProfileListingCard = static function (array $L) use ($id): void {
            $isSvc = (($L['listing_type'] ?? '') === 'service');
            $href = $isSvc ? 'view-service.php?id=' : 'view-product.php?id=';
            $href .= (int) ($L['listing_id'] ?? 0);
            $href = wvsu_append_listing_return($href, 'profile.php?id=' . (int) $id);
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
        <h2 class="h5 fw-bold mb-2"><i class="bi bi-chat-square-text me-2 text-primary"></i>Ratings &amp; reviews</h2>
        <p class="text-muted small mb-3">Honest feedback helps everyone trade with confidence on campus. Purchase ratings are submitted in <a href="messages.php" class="fw-semibold">Messages</a> after the seller marks the sale complete.</p>

        <?php if ($meId > 0 && ! $isOwn && $canViewFull && $myReview): ?>
            <div class="card border-0 shadow-sm mb-4 border-start border-4 border-primary">
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold mb-2">Your review</h3>
                    <p class="text-muted small mb-3 mb-md-4">
                        Posted <?= htmlspecialchars(date('M j, Y', strtotime((string) ($myReview['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?>.
                        Reviews stay on the record and <strong>can’t be changed</strong> after submission.
                    </p>
                    <div class="mb-2"><?= wvsu_render_stars((float) ($myReview['rating'] ?? 0)) ?></div>
                    <p class="mb-0 small"><?= nl2br(htmlspecialchars((string) ($myReview['comment'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            </div>
        <?php elseif ($meId > 0 && $isOwn && $canViewFull): ?>
            <p class="text-muted small mb-4">When buyers complete sale feedback in Messages, it appears in this section.</p>
        <?php elseif ($meId <= 0 && $canViewFull): ?>
            <p class="text-muted small mb-4"><a href="login.php?next=<?= rawurlencode('messages.php') ?>">Log in</a> to use Messages and leave feedback after a purchase.</p>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
            <?php if ($canViewFull && ! $isOwn): ?>
                <p class="text-muted mb-0">No reviews yet — buyers leave purchase feedback in Messages after a completed sale.</p>
            <?php elseif ($canViewFull && $isOwn): ?>
                <p class="text-muted mb-0">No reviews on your profile yet. Great service earns great ratings.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="vstack gap-3">
                <?php foreach ($reviews as $rv):
                    $rvId = (int) ($rv['review_id'] ?? 0);
                    $sellerReply = trim((string) ($rv['seller_reply'] ?? ''));
                    $repliedAt = trim((string) ($rv['seller_replied_at'] ?? ''));
                    $hasListing = (int) ($rv['listing_id'] ?? 0) > 0;
                    $rvLid = (int) ($rv['listing_id'] ?? 0);
                    ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                <div>
                                    <a href="profile.php?id=<?= (int) $rv['reviewer_id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars((string) $rv['reviewer_name']) ?></a>
                                    <span class="text-muted small ms-2"><?= date('M j, Y', strtotime((string) $rv['created_at'])) ?></span>
                                    <?php if ($hasListing): ?>
                                        <span class="badge rounded-pill text-bg-light border ms-1 small">Purchase review</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill text-bg-secondary-subtle border ms-1 small">Profile review</span>
                                    <?php endif; ?>
                                </div>
                                <?= wvsu_render_stars((float) $rv['rating']) ?>
                            </div>
                            <?php if ($hasListing): ?>
                                <p class="small text-muted mb-2 mb-md-3">
                                    <i class="bi bi-tag me-1" aria-hidden="true"></i>
                                    <a href="<?= htmlspecialchars(
                                            wvsu_append_listing_return(
                                                wvsu_review_listing_public_url($rvLid, (string) ($rv['reviewed_listing_type'] ?? '')),
                                                'profile.php?id=' . (int) $id
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">
                                        <?= htmlspecialchars(
                                            wvsu_review_purchase_context_label(
                                                (string) ($rv['reviewed_listing_type'] ?? ''),
                                                (string) ($rv['reviewed_listing_title'] ?? ''),
                                                (string) ($rv['reviewed_category_name'] ?? ''),
                                                $rvLid
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <p class="mb-2 small"><?= nl2br(htmlspecialchars((string) ($rv['comment'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php
                            $rvPhoto = trim((string) ($rv['photo_url'] ?? ''));
                            if ($rvPhoto !== ''):
                                $rvPhotoWeb = htmlspecialchars(wvsu_public_asset_web_path($rvPhoto), ENT_QUOTES, 'UTF-8');
                                ?>
                                <div class="mt-2">
                                    <a href="<?= $rvPhotoWeb ?>" target="_blank" rel="noopener" class="d-inline-block rounded-3 overflow-hidden border border-secondary-subtle shadow-sm">
                                        <img src="<?= $rvPhotoWeb ?>" alt="Review photo" class="object-fit-cover" style="max-width:220px;max-height:160px;width:auto;height:auto;" loading="lazy">
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if ($sellerReply !== ''): ?>
                                <div class="mt-3 pt-3 border-top border-secondary-subtle">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge text-bg-secondary rounded-pill small">Seller reply</span>
                                        <?php if ($repliedAt !== ''): ?>
                                            <span class="text-muted small"><?= htmlspecialchars(date('M j, Y', strtotime($repliedAt)), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-0 small text-secondary"><?= nl2br(htmlspecialchars($sellerReply, ENT_QUOTES, 'UTF-8')) ?></p>
                                </div>
                            <?php elseif ($isOwn && $canViewFull && $rvId > 0): ?>
                                <div class="mt-3 pt-3 border-top border-secondary-subtle">
                                    <p class="small fw-semibold text-dark mb-2">Reply publicly to this review <span class="text-muted fw-normal">(once only)</span></p>
                                    <form method="post" action="process-review-reply.php" class="vstack gap-2">
                                        <input type="hidden" name="review_id" value="<?= $rvId ?>">
                                        <input type="hidden" name="return_profile_id" value="<?= (int) $id ?>">
                                        <textarea name="seller_reply" class="form-control form-control-sm" rows="3" maxlength="2000" required placeholder="Thank the buyer, clarify details, or offer help — keep it professional."></textarea>
                                        <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold align-self-start px-3">Post reply</button>
                                    </form>
                                </div>
                            <?php endif; ?>
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
</body>
</html>

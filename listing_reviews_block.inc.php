<?php
declare(strict_types=1);

/**
 * Renders reviews for a single listing (user_reviews.listing_id).
 * Expects $wvsu_listing_review_id (int) set by the parent view.
 */
if (! isset($wvsu_listing_review_id) || (int) $wvsu_listing_review_id <= 0) {
    return;
}
$wvsuLid = (int) $wvsu_listing_review_id;
require_once __DIR__ . '/profiles_reviews.inc.php';
$wvsuListingStats = wvsu_listing_review_stats($wvsuLid);
$wvsuListingRows = wvsu_listing_reviews_for_listing($wvsuLid);
?>
<section id="wvsu-listing-reviews" class="mt-5 pt-4 border-top border-secondary-subtle" aria-labelledby="wvsuListingReviewsHeading">
    <h2 id="wvsuListingReviewsHeading" class="h5 fw-bold mb-2">
        <i class="bi bi-stars text-warning me-2" aria-hidden="true"></i>Buyer reviews for this listing
    </h2>
    <p class="text-muted small mb-3">Ratings from buyers who completed a purchase for this item also appear on the seller’s profile, with product or service name and category.</p>
    <?php if ($wvsuListingStats['count'] > 0): ?>
        <p class="small fw-semibold text-dark mb-3">
            <?= htmlspecialchars(number_format($wvsuListingStats['avg'], 1), ENT_QUOTES, 'UTF-8') ?> out of 5
            <span class="text-muted fw-normal">· <?= (int) $wvsuListingStats['count'] ?> review<?= $wvsuListingStats['count'] === 1 ? '' : 's' ?> for this listing</span>
        </p>
    <?php endif; ?>

    <?php if ($wvsuListingRows === []): ?>
        <p class="text-muted small mb-0">No reviews for this listing yet — they appear here after a buyer completes a sale and submits feedback.</p>
    <?php else: ?>
        <div class="vstack gap-3">
            <?php foreach ($wvsuListingRows as $lr):
                $lrReply = trim((string) ($lr['seller_reply'] ?? ''));
                $lrReplyAt = trim((string) ($lr['seller_replied_at'] ?? ''));
                $lrCtx = wvsu_review_purchase_context_label(
                    (string) ($lr['review_listing_type'] ?? ''),
                    (string) ($lr['review_listing_title'] ?? ''),
                    (string) ($lr['review_category_name'] ?? ''),
                    $wvsuLid
                );
                ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <a href="profile.php?id=<?= (int) $lr['reviewer_id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars((string) $lr['reviewer_name'], ENT_QUOTES, 'UTF-8') ?></a>
                                <span class="text-muted small ms-2"><?= htmlspecialchars(date('M j, Y', strtotime((string) $lr['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <?= wvsu_render_stars((float) $lr['rating']) ?>
                        </div>
                        <?php if ($lrCtx !== ''): ?>
                            <p class="small text-muted mb-2 mb-md-3"><i class="bi bi-tag me-1" aria-hidden="true"></i><?= htmlspecialchars($lrCtx, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <p class="mb-2 small"><?= nl2br(htmlspecialchars((string) ($lr['comment'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
                        <?php
                        $lrPhoto = trim((string) ($lr['photo_url'] ?? ''));
                        if ($lrPhoto !== ''):
                            $lrPhotoWeb = htmlspecialchars(wvsu_public_asset_web_path($lrPhoto), ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="mt-2">
                                <a href="<?= $lrPhotoWeb ?>" target="_blank" rel="noopener" class="d-inline-block rounded-3 overflow-hidden border border-secondary-subtle shadow-sm">
                                    <img src="<?= $lrPhotoWeb ?>" alt="" class="object-fit-cover" style="max-width:220px;max-height:160px;" loading="lazy">
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($lrReply !== ''): ?>
                            <div class="mt-3 pt-3 border-top border-secondary-subtle">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge text-bg-secondary rounded-pill small">Seller reply</span>
                                    <?php if ($lrReplyAt !== ''): ?>
                                        <span class="text-muted small"><?= htmlspecialchars(date('M j, Y', strtotime($lrReplyAt)), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="mb-0 small text-secondary"><?= nl2br(htmlspecialchars($lrReply, ENT_QUOTES, 'UTF-8')) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

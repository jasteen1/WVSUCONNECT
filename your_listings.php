<?php
require_once 'db_conn.php';
// require login
if (empty($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}
$uid = intval($_SESSION['user_id']);

$listings = fetchAll_master(
    "SELECT l.*, p.price, p.stock, s.rate, s.rate_type, c.name AS category_name
     FROM listings l
     LEFT JOIN products p ON p.listing_id = l.listing_id
     LEFT JOIN services s ON s.listing_id = l.listing_id
     LEFT JOIN categories c ON c.category_id = l.category_id
     WHERE l.owner_id = ? AND l.status IN ('active', 'sold_out')
     ORDER BY l.created_at DESC",
    [(string) $uid]
);

$listingProducts = [];
$listingServices = [];
foreach ($listings as $item) {
    if (($item['listing_type'] ?? '') === 'service') {
        $listingServices[] = $item;
        continue;
    }
    $listingProducts[] = $item;
}

/**
 * Render one row card for Your Listings page.
 *
 * @param array<string,mixed> $l
 */
$wvsu_yourlisting_card = static function ($l): void {
    $kind = (($l['listing_type'] ?? '') === 'service') ? 'service' : 'product';
    $isSold = (($l['listing_type'] ?? '') === 'product' && intval($l['stock'] ?? 0) <= 0);
    $img = ! empty($l['image_url'])
        ? (string) $l['image_url']
        : 'https://images.unsplash.com/photo-1555448248-2571daf6344b?q=80&w=300&auto=format&fit=crop';
    ?>
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 hover-lift p-2 <?php if ($isSold) {
            echo 'sold-out-card';
        } ?>">
          <div class="row g-0 align-items-center">
            <div class="col-auto p-2 position-relative">
              <img src="<?= htmlspecialchars($img) ?>" class="listing-img <?php if ($isSold) {
                  echo 'sold-out-img';
              } ?>" alt="">
              <?php if ($isSold): ?>
                <span class="position-absolute top-50 start-50 translate-middle badge bg-dark shadow" style="font-size: 0.7rem;">SOLD OUT</span>
              <?php endif; ?>
            </div>
            <div class="col">
              <div class="card-body py-2 pe-3">
                <div class="row">
                  <div class="col-md-7 mb-2 mb-md-0">
                    <div class="d-flex align-items-center mb-1">
                        <?php $isSvcRow = (($l['listing_type'] ?? '') === 'service'); ?>
                        <span class="badge <?php echo $isSvcRow ? 'bg-info' : 'bg-secondary'; ?> me-2 rounded-pill" style="font-size: 0.65rem;">
                            <?= strtoupper(htmlspecialchars((string) ($l['listing_type'] ?? ''))) ?>
                        </span>
                        <span class="text-muted small"><i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars((string) ($l['category_name'] ?? 'Uncategorized')) ?></span>
                    </div>
                    <h5 class="fw-bold mb-1 text-truncate"><?= htmlspecialchars((string) $l['title']) ?></h5>
                    <div class="text-primary fw-bolder fs-5 mt-1">
                        <?php if (($l['listing_type'] ?? '') === 'product'): ?>
                            ₱<?= number_format((float) ($l['price'] ?? 0), 2) ?>
                        <?php else: ?>
                            ₱<?= number_format((float) ($l['rate'] ?? 0), 2) ?>
                            <span class="text-muted fs-6 fw-normal"><?php if (! empty($l['rate_type']) && $l['rate_type'] === 'per_hour') {
                                echo '/hr';
                            } else {
                                echo ' (Flat Rate)';
                            } ?></span>
                        <?php endif; ?>
                    </div>
                  </div>
                  <div class="col-md-5 d-flex flex-column justify-content-end align-items-md-end">
                    <?php if (($l['listing_type'] ?? '') === 'product'): ?>
                        <form method="post" action="process-update-stock.php" class="mb-3 w-100" style="max-width: 200px;">
                            <input type="hidden" name="listing_id" value="<?= intval($l['listing_id']) ?>">
                            <label class="form-label small fw-bold text-muted mb-1">Manage Stock</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-box-seam"></i></span>
                                <input name="stock" type="number" min="0" class="form-control" value="<?= intval($l['stock'] ?? 0) ?>">
                                <button class="btn btn-outline-primary fw-bold" type="submit">Save</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="mb-3 d-none d-md-block" style="height: 45px;"></div>
                    <?php endif; ?>
                    <div class="d-flex gap-2 w-100 justify-content-md-end">
                      <a class="btn btn-sm btn-light border fw-bold text-secondary flex-grow-1 flex-md-grow-0"
                         href="edit_listing.php?id=<?= intval($l['listing_id']) ?>"
                         onclick="return confirm('Are you sure you want to edit this <?= $kind === 'service' ? 'service' : 'product' ?> listing?');">
                        <i class="bi bi-pencil-square me-1"></i>Edit
                      </a>
                      <button type="button" class="btn btn-sm btn-outline-danger fw-bold flex-grow-1 flex-md-grow-0 w-100"
                              data-bs-toggle="modal" data-bs-target="#wvsuDeleteListingModal"
                              data-listing-id="<?= intval($l['listing_id']) ?>"
                              data-listing-title="<?= htmlspecialchars((string) ($l['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                              data-listing-kind="<?= htmlspecialchars($kind, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-trash3 me-1"></i>Delete
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0d4daa">
  <title>Your listings — WVSU CONNECT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php include __DIR__ . '/head_assets.php'; ?>
  <style>
    body { background-color: transparent; color: inherit; }
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.08)!important; }
    .sold-out-card { opacity: 0.75; }
    .sold-out-img { filter: grayscale(100%); }
    .listing-img { height: 120px; width: 120px; object-fit: cover; border-radius: 0.5rem; }
    .wvsu-yourlist-heading { scroll-margin-top: 5rem; }
    @media (max-width: 576px) {
        .listing-img { width: 100px; height: 100px; }
    }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5 mb-5 pb-5 wvsu-pan-soft" style="max-width: 900px;" data-io-animate>

  <?php if (! empty($_GET['removed'])): ?>
    <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4" role="alert">
      Listing removed from your page and hidden from the marketplace.
    </div>
  <?php endif; ?>
  <?php if (! empty($_GET['error']) && (string) $_GET['error'] === 'delete_failed'): ?>
    <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4" role="alert">
      Could not remove that listing (the database may have rolled the change back). Reload the site once so setup can fix log tables, then try again. If it persists, open <code>db_conn_debug.log</code> in this folder and check for <code>alter_item_status_autoinc</code> / <code>alter_audit_logs_autoinc</code> errors.
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div class="d-flex align-items-center">
        <a href="index.php" class="text-dark text-decoration-none me-3" title="Back to Home">
            <i class="bi bi-arrow-left fs-2 arrow-hover"></i>
        </a>
        <div>
            <h3 class="mb-0 fw-bold">Your Listings</h3>
            <span class="text-muted small">Manage your marketplace items and services</span>
        </div>
    </div>
    <div class="d-none d-md-flex align-items-center gap-2 flex-shrink-0">
        <a class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold shadow-sm" href="addproduct.php"
           onclick="return confirm('Go to the form to add a new product listing?');">
            <i class="bi bi-bag-fill me-1"></i>Product
        </a>
        <a class="btn btn-outline-info rounded-pill px-3 py-2 fw-semibold shadow-sm" href="addservice.php"
           onclick="return confirm('Go to the form to add a new service listing?');">
            <i class="bi bi-palette2 me-1"></i>Service
        </a>
    </div>
    <div class="dropdown d-md-none flex-shrink-0">
        <button class="btn btn-primary rounded-circle shadow" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 45px; height: 45px; line-height: 33px;" aria-label="Add listing">
            <i class="bi bi-plus-lg"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 border-secondary-subtle">
            <li><a class="dropdown-item rounded-3 fw-semibold" href="addproduct.php" onclick="return confirm('Go to the form to add a new product listing?');"><i class="bi bi-bag-fill me-2 text-primary"></i>Add product</a></li>
            <li><a class="dropdown-item rounded-3 fw-semibold" href="addservice.php" onclick="return confirm('Go to the form to add a new service listing?');"><i class="bi bi-palette2 me-2 text-info"></i>Offer service</a></li>
        </ul>
    </div>
  </div>

  <?php if (empty($listings)): ?>
    <div class="card border-0 shadow-sm rounded-4 text-center py-5">
        <div class="card-body">
            <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle mb-3" style="width: 80px; height: 80px;">
                <i class="bi bi-inbox fs-1"></i>
            </div>
            <h4 class="fw-bold">No listings yet!</h4>
            <p class="text-muted mb-4">You haven't posted any products or services to the marketplace.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="addproduct.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="return confirm('Go to the form to add a new product listing?');">List a product</a>
                <a href="addservice.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold" onclick="return confirm('Go to the form to add a new service listing?');">Offer a service</a>
            </div>
        </div>
    </div>
  <?php else: ?>
    <?php $pc = count($listingProducts); $sc = count($listingServices); ?>
    <nav class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 p-3 rounded-4 bg-white shadow-sm border border-secondary-subtle" aria-label="Jump to listings">
        <div class="small text-muted">
            You have <span class="fw-bold text-dark"><?= (int) $pc ?></span> product<?= $pc === 1 ? '' : 's' ?>
            · <span class="fw-bold text-dark"><?= (int) $sc ?></span> service<?= $sc === 1 ? '' : 's' ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="#your-products" class="btn btn-sm btn-outline-secondary rounded-pill fw-semibold">Products</a>
            <a href="#your-services" class="btn btn-sm btn-outline-secondary rounded-pill fw-semibold">Services</a>
        </div>
    </nav>

    <section id="your-products" class="mb-5 wvsu-yourlist-heading">
        <header class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3 pb-2 border-bottom border-primary border-opacity-25">
            <h4 class="h5 fw-bold mb-0 d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary" style="width:2rem;height:2rem;"><i class="bi bi-bag-fill"></i></span>
                Products
            </h4>
            <?php if ($pc > 0): ?>
                <a href="addproduct.php" class="btn btn-sm btn-link text-decoration-none fw-semibold px-1" onclick="return confirm('Go to the form to add a new product listing?');">Add product <i class="bi bi-arrow-right-short"></i></a>
            <?php endif; ?>
        </header>
        <?php if ($pc <= 0): ?>
            <div class="rounded-4 border border-dashed border-secondary-subtle bg-light-subtle p-4 text-center text-muted small">
                <p class="mb-2 fw-semibold text-dark">No product listings yet</p>
                <p class="mb-3 mb-md-4">Sell textbooks, gadgets, apparel, or anything students need.</p>
                <a href="addproduct.php" class="btn btn-primary rounded-pill btn-sm fw-bold px-4" onclick="return confirm('Go to the form to add a new product listing?');">Add a product</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
              <?php foreach ($listingProducts as $l): $wvsu_yourlisting_card($l); endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section id="your-services" class="wvsu-yourlist-heading">
        <header class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3 pb-2 border-bottom border-info border-opacity-25">
            <h4 class="h5 fw-bold mb-0 d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-info-subtle text-info" style="width:2rem;height:2rem;"><i class="bi bi-palette2"></i></span>
                Services
            </h4>
            <?php if ($sc > 0): ?>
                <a href="addservice.php" class="btn btn-sm btn-link text-info text-decoration-none fw-semibold px-1" onclick="return confirm('Go to the form to add a new service listing?');">Offer a service <i class="bi bi-arrow-right-short"></i></a>
            <?php endif; ?>
        </header>
        <?php if ($sc <= 0): ?>
            <div class="rounded-4 border border-dashed border-secondary-subtle bg-light-subtle p-4 text-center text-muted small">
                <p class="mb-2 fw-semibold text-dark">No service listings yet</p>
                <p class="mb-3 mb-md-4">Tutoring, design, gigs—list what you can do for classmates.</p>
                <a href="addservice.php" class="btn btn-outline-primary rounded-pill btn-sm fw-bold px-4" onclick="return confirm('Go to the form to add a new service listing?');">Add a service</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
              <?php foreach ($listingServices as $l): $wvsu_yourlisting_card($l); endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
  <?php endif; ?>
</div>

<div class="modal fade" id="wvsuDeleteListingModal" tabindex="-1" aria-labelledby="wvsuDeleteListingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h2 class="modal-title h5 fw-bold" id="wvsuDeleteListingModalLabel">Remove listing?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="mb-0 text-muted" id="wvsuDeleteListingModalBody"></p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
        <form method="post" action="process-delete-listing.php" class="d-inline">
          <input type="hidden" name="listing_id" id="wvsuDeleteListingModalListingId" value="">
          <button type="submit" class="btn btn-danger rounded-pill fw-semibold">Yes, remove it</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var modal = document.getElementById('wvsuDeleteListingModal');
  if (!modal) return;
  modal.addEventListener('show.bs.modal', function (event) {
    var btn = event.relatedTarget;
    if (!btn) return;
    var id = btn.getAttribute('data-listing-id') || '';
    var title = btn.getAttribute('data-listing-title') || 'this listing';
    var kind = btn.getAttribute('data-listing-kind') || 'listing';
    var idInput = document.getElementById('wvsuDeleteListingModalListingId');
    var body = document.getElementById('wvsuDeleteListingModalBody');
    if (idInput) idInput.value = id;
    if (body) {
      body.textContent = 'Remove “' + title + '” (' + kind + ') from your listings? It will be hidden from the marketplace. You can list again anytime.';
    }
  });
})();
</script>
</body>
</html>
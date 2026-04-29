<?php
require_once 'db_conn.php';
// require login
if (empty($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}
$uid = intval($_SESSION['user_id']);

$listings = fetchAll(
    "SELECT l.*, p.price, p.stock, s.rate, s.rate_type, c.name AS category_name
     FROM listings l
     LEFT JOIN products p ON p.listing_id = l.listing_id
     LEFT JOIN services s ON s.listing_id = l.listing_id
     LEFT JOIN categories c ON c.category_id = l.category_id
     WHERE l.owner_id = ? ORDER BY l.created_at DESC",
    [$uid]
);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Listings - WVSU Connect</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    body { background-color: #fafafa; color: #131313; }
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.08)!important; }
    .sold-out-card { opacity: 0.75; }
    .sold-out-img { filter: grayscale(100%); }
    .listing-img { height: 120px; width: 120px; object-fit: cover; border-radius: 0.5rem; }
    @media (max-width: 576px) {
        .listing-img { width: 100px; height: 100px; }
    }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5 mb-5" style="max-width: 900px;">
  
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
    <a class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-none d-md-block" href="add-product.php">
        <i class="bi bi-plus-lg me-2"></i>Add Listing
    </a>
    <a class="btn btn-primary rounded-circle shadow d-md-none" href="add-product.php" style="width: 45px; height: 45px; line-height: 33px;">
        <i class="bi bi-plus-lg"></i>
    </a>
  </div>

  <?php if (empty($listings)): ?>
    <div class="card border-0 shadow-sm rounded-4 text-center py-5">
        <div class="card-body">
            <div class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle mb-3" style="width: 80px; height: 80px;">
                <i class="bi bi-inbox fs-1"></i>
            </div>
            <h4 class="fw-bold">No listings yet!</h4>
            <p class="text-muted mb-4">You haven't posted any products or services to the marketplace.</p>
            <a href="add-product.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                Start Selling
            </a>
        </div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($listings as $l):
        $isSold = ($l['listing_type'] === 'product' && intval($l['stock'] ?? 0) <= 0);
        $img = !empty($l['image_url']) ? $l['image_url'] : 'https://images.unsplash.com/photo-1555448248-2571daf6344b?q=80&w=300&auto=format&fit=crop';
      ?>
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 hover-lift p-2 <?php if($isSold) echo 'sold-out-card'; ?>">
          <div class="row g-0 align-items-center">
            
            <div class="col-auto p-2 position-relative">
              <img src="<?= htmlspecialchars($img) ?>" class="listing-img <?php if($isSold) echo 'sold-out-img'; ?>" alt="Item Image">
              <?php if($isSold): ?>
                <span class="position-absolute top-50 start-50 translate-middle badge bg-dark shadow" style="font-size: 0.7rem;">SOLD OUT</span>
              <?php endif; ?>
            </div>
            
            <div class="col">
              <div class="card-body py-2 pe-3">
                <div class="row">
                  
                  <div class="col-md-7 mb-2 mb-md-0">
                    <div class="d-flex align-items-center mb-1">
                        <span class="badge <?php echo ($l['listing_type'] == 'service') ? 'bg-info' : 'bg-secondary'; ?> me-2 rounded-pill" style="font-size: 0.65rem;">
                            <?= strtoupper(htmlspecialchars($l['listing_type'])) ?>
                        </span>
                        <span class="text-muted small"><i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($l['category_name'] ?? 'Uncategorized') ?></span>
                    </div>
                    <h5 class="fw-bold mb-1 text-truncate"><?= htmlspecialchars($l['title']) ?></h5>
                    
                    <div class="text-primary fw-bolder fs-5 mt-1">
                        <?php if ($l['listing_type'] === 'product'): ?>
                            ₱<?= number_format($l['price'] ?? 0, 2) ?>
                        <?php else: ?>
                            ₱<?= number_format($l['rate'] ?? 0, 2) ?>
                            <span class="text-muted fs-6 fw-normal"><?php if(!empty($l['rate_type']) && $l['rate_type']==='per_hour') echo '/hr'; else echo ' (Flat Rate)'; ?></span>
                        <?php endif; ?>
                    </div>
                  </div>
                  
                  <div class="col-md-5 d-flex flex-column justify-content-end align-items-md-end">
                    
                    <?php if ($l['listing_type'] === 'product'): ?>
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
                      <a class="btn btn-sm btn-light border fw-bold text-secondary flex-grow-1 flex-md-grow-0" href="edit_listing.php?id=<?= intval($l['listing_id']) ?>">
                        <i class="bi bi-pencil-square me-1"></i>Edit
                      </a>
                      <form method="post" action="process-delete-listing.php" onsubmit="return confirm('Are you sure you want to delete this listing?')" class="d-inline flex-grow-1 flex-md-grow-0">
                        <input type="hidden" name="listing_id" value="<?= intval($l['listing_id']) ?>">
                        <button class="btn btn-sm btn-outline-danger fw-bold w-100" type="submit">
                          <i class="bi bi-trash3 me-1"></i>Delete
                        </button>
                      </form>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
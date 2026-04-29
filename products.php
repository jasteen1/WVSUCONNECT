<?php
require_once 'db_conn.php';
$products = fetchAll("SELECT l.listing_id, l.title, l.description, l.image_url, p.price, p.stock, l.created_at FROM listings l JOIN products p ON p.listing_id = l.listing_id WHERE l.listing_type='product' AND l.status='active' ORDER BY l.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - WVSU Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4">

    <h4 class="fw-bold mb-3">Products</h4>
    
    <div class="row g-4">
        <?php if (empty($products)): ?>
            <div class="col-12">No products found.</div>
        <?php else: ?>
            <?php foreach ($products as $p):
                $img = $p['image_url'] ? $p['image_url'] : 'https://via.placeholder.com/300x300?text=Product+Image';
                $isSoldOut = intval($p['stock'] ?? 0) <= 0;
            ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden product-card <?= $isSoldOut ? 'sold-out' : '' ?>">
                        <?php $img = $p['image_url'] ? $p['image_url'] : 'https://via.placeholder.com/300x300?text=Product+Image'; ?>
                        <?php if ($isSoldOut): ?>
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-danger text-white shadow-sm">Sold out</span>
                            </div>
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" alt="Product" style="height: 180px; object-fit: cover;">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($p['title']) ?></h6>
                            <p class="text-primary fw-bold mb-2">₱<?= number_format($p['price'],2) ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-light text-muted fw-normal">Stock: <?= intval($p['stock']) ?></span>
                                <?php if ($isSoldOut): ?>
                                    <span class="ms-2 badge bg-danger text-white" style="font-size: 0.75rem;">Sold out</span>
                                <?php endif; ?>
                                <small class="text-muted"><i class="bi bi-clock"></i> <?= date('M j', strtotime($p['created_at'])) ?></small>
                            </div>
                                <?php if (!$isSoldOut): ?>
                                    <a href="view-product.php?id=<?= intval($p['listing_id']) ?>" class="stretched-link"></a>
                                <?php else: ?>
                                    <!-- Sold-out item: no link (non-clickable) -->
                                <?php endif; ?>
                        </div>
                    </div>
                        <style>
                        /* Dim sold-out cards */
                        .sold-out {
                            opacity: 0.72;
                            filter: grayscale(20%);
                        }
                        .product-card.sold-out { cursor: default; pointer-events: none; }
                        </style>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<footer class="bg-white border-top pt-5 pb-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="fw-bold text-primary">WVSU <span class="text-dark">Marketplace</span></h5>
                <p class="text-muted small mt-2">
                    A dedicated platform for Taga-West to trade, share, and grow together within the campus community.
                </p>
            </div>

            <div class="col-md-4 mb-4 mb-md-0">
                <h6 class="fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
                    <li class="mb-2"><a href="products.php" class="text-decoration-none text-muted">Browse Products</a></li>
                    <li class="mb-2"><a href="services.php" class="text-decoration-none text-muted">Explore Services</a></li>
                </ul>
            </div>

            <div class="col-md-4 text-md-end">
                <h6 class="fw-bold mb-3">Project Team</h6>
                <p class="text-muted small mb-0">Developed with ❤️ by</p>
                <p class="fw-bold text-dark">Group 5 - WVSU Students</p>
                <div class="mt-3">
                    <i class="bi bi-facebook me-2 text-muted"></i>
                    <i class="bi bi-github me-2 text-muted"></i>
                    <i class="bi bi-envelope text-muted"></i>
                </div>
            </div>
        </div>

        <hr class="my-4 text-muted opacity-25">

        <div class="row align-items-center">
            <div class="col-md-6 small text-muted">
                &copy; 2026 WVSU Marketplace. For Academic Purposes Only.
            </div>
            <div class="col-md-6 text-md-end small">
                <a href="#" class="text-decoration-none text-muted me-3">Privacy Policy</a>
                <a href="#" class="text-decoration-none text-muted">Terms of Use</a>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

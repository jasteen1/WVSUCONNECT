<?php
require_once 'db_conn.php';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid product ID.');

$sql = "SELECT l.*, p.price, p.stock FROM listings l JOIN products p ON p.listing_id = l.listing_id WHERE l.listing_id = ? LIMIT 1";
$item = fetch($sql, [$id]);
if (!$item) die('Product not found.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['title']) ?> - Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="d-flex align-items-center mb-4 pb-3">
    <a href="products.php" class="text-dark text-decoration-none me-3" title="Back to Products">
        <i class="bi bi-arrow-left fs-2 arrow-hover"></i>   
    </a>
    
    <div>
        <h4 class="mb-0 fw-bold">Back to Marketplace</h4>
        <span class="text-muted small">Lorem ipsum dolor sit amet consectetur adipisicing elit. Facilis nesciunt mollitia nobis </span>
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
            <img src="<?= $item['image_url'] ? htmlspecialchars($item['image_url']) : 'https://via.placeholder.com/600x400?text=No+Image' ?>" class="img-fluid rounded shadow-sm" alt="">
        </div>
        <div class="col-md-6">
            <h2 class="fw-bold"><?= htmlspecialchars($item['title']) ?></h2>
            <p class="text-muted small">Posted: <?= date('M j, Y', strtotime($item['created_at'])) ?></p>
            <h3 class="text-primary">₱<?= number_format($item['price'],2) ?></h3>
            <p class="mt-4"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
            <p class="small text-muted">Stock: <?= intval($item['stock']) ?></p>
            <div class="mt-4 d-flex gap-2">
                <a href="contact.php?listing_id=<?= intval($item['listing_id']) ?>" class="btn btn-outline-primary">Contact Seller</a>
                <?php if (intval($item['owner_id']) === (int)($_SESSION['user_id'] ?? 0)): ?>
                    <span class="badge bg-secondary">Your listing</span>
                <?php else: ?>
                    <?php if (intval($item['stock']) <= 0): ?>
                        <span class="badge bg-danger">Sold out</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
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

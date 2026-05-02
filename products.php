<?php
require_once 'db_conn.php';
$q = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = intval($_GET['category'] ?? 0);

$categories = fetchAll(
    "SELECT category_id, name
     FROM categories
     WHERE category_type IN ('product','both')
     ORDER BY name ASC"
);

$productsSql = "SELECT l.listing_id, l.title, l.description, l.image_url, l.category_id, l.created_at,
                       c.name AS category_name, p.price, p.stock
                FROM listings l
                JOIN products p ON p.listing_id = l.listing_id
                LEFT JOIN categories c ON c.category_id = l.category_id
                WHERE l.listing_type='product' AND l.status='active'";
$params = [];
if ($q !== '') {
    $productsSql .= " AND (l.title LIKE CONCAT('%', ?, '%') OR l.description LIKE CONCAT('%', ?, '%'))";
    $params[] = $q;
    $params[] = $q;
}
if ($categoryFilter > 0) {
    $productsSql .= " AND l.category_id = ?";
    $params[] = (string) $categoryFilter;
}
$productsSql .= " ORDER BY l.created_at DESC";
$products = fetchAll($productsSql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d4daa">
    <title>Products — WVSU CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4 pb-5 wvsu-pan-soft" data-io-animate>

    <h1 class="h3 fw-bold mb-1">Products</h1>
    <p class="text-muted small mb-4">Gear, books, thrift—listed by Wildcats for Wildcats.</p>

    <form class="card border-0 shadow-sm mb-4" method="get" action="products.php">
        <div class="card-body p-3 p-md-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Search items</label>
                    <input type="search" class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by title or description">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Category</label>
                    <select name="category" class="form-select">
                        <option value="0">All categories</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= intval($c['category_id']) ?>" <?= intval($c['category_id']) === $categoryFilter ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Search</button>
                </div>
            </div>
            <?php if ($q !== '' || $categoryFilter > 0): ?>
                <div class="mt-2 small">
                    <a href="products.php" class="text-decoration-none">Clear filters</a>
                </div>
            <?php endif; ?>
        </div>
    </form>
    
    <div class="row g-4 wvsu-stagger">
        <?php if (empty($products)): ?>
            <div class="col-12"><div class="alert alert-info">No products found for your current filters.</div></div>
        <?php else: ?>
            <?php foreach ($products as $p):
                $img = $p['image_url'] ? $p['image_url'] : 'https://via.placeholder.com/300x300?text=Product+Image';
                $isSoldOut = intval($p['stock'] ?? 0) <= 0;
            ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden product-card market-card <?= $isSoldOut ? 'sold-out' : '' ?>">
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
                                <span class="badge bg-light text-muted fw-normal"><?= htmlspecialchars((string) ($p['category_name'] ?? 'Item')) ?> • Stock: <?= intval($p['stock']) ?></span>
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
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php $sellHref = !empty($_SESSION['user_id']) ? 'addproduct.php' : 'login.php?next=addproduct.php'; ?>
    <div class="card border-0 shadow-sm mt-4 market-card overflow-hidden">
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-lg-row align-items-lg-center gap-3 justify-content-between">
            <div>
                <span class="badge text-bg-primary-subtle text-primary-emphasis mb-2">Seller zone</span>
                <h2 class="h5 fw-bold mb-1">Have something to sell? Put an item now!</h2>
                <p class="text-muted mb-0">List your books, gadgets, uniforms, and campus essentials in minutes.</p>
            </div>
            <a href="<?= htmlspecialchars($sellHref) ?>" class="btn btn-primary rounded-pill px-4 fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> List an item
            </a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once 'db_conn.php';
$services = fetchAll("SELECT l.listing_id, l.title, l.description, l.image_url, s.rate, s.rate_type, l.created_at FROM listings l JOIN services s ON s.listing_id = l.listing_id WHERE l.listing_type='service' AND l.status='active' ORDER BY l.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - WVSU Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4">
    <h4 class="fw-bold mb-3">Services</h4>
    <div class="row g-4">
        <?php if (empty($services)): ?>
            <div class="col-12">No services found.</div>
        <?php else: ?>
            <?php foreach ($services as $s): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm service-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?= $s['image_url'] ? htmlspecialchars($s['image_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($s['title']) ?>" class="rounded-circle" width="45" height="45" alt="">
                                <div class="ms-3">
                                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($s['title']) ?></h6>
                                    <small class="text-muted">Service</small>
                                </div>
                            </div>
                            <p class="text-muted small mb-4"><?= htmlspecialchars($s['description']) ?></p>
                            <div class="d-flex align-items-center justify-content-between mt-auto pt-3 border-top">
                                <div>
                                    <span class="text-dark small d-block mb-0">Starting at</span>
                                    <span class="h5 fw-bold text-primary mb-0">₱<?= number_format($s['rate'],2) ?></span>
                                </div>
                                <a href="service-details.php?id=<?= intval($s['listing_id']) ?>" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">View Gig</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<footer class="bg-white border-top pt-5 pb-4 mt-5">
    <div class="container small text-muted">&copy; 2026 WVSU Marketplace</div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

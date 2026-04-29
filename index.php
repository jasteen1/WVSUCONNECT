<?php
require_once 'db_conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WVSU Marketplace</title>
    <!-- Latest compiled and minified CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Latest compiled JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="position-relative overflow-hidden bg-dark text-white" style="
    /* Placeholder - High quality campus/student life image */
    background: linear-gradient(to right, rgba(31, 30, 30, 0.8) 30%, rgba(0,0,0,0.2) 100%), 
                url('wvsucover.png');
    background-size: cover;
    background-position: center;
    min-height: 600px;
    display: flex;
    align-items: center;
">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-md-8 text-start">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="#" class="text-warning text-decoration-none fw-bold">WVSU Exclusive</a></li>
                    </ol>
                </nav>
                
                <h1 class="display-3 fw-bold mb-3 shadow-sm">
                    Agi si james <br><span class="text-warning">Very cool motto</span>
                </h1>
                
                <p class="lead mb-4 opacity-75">
                   Agi si james Agi si james
                   Agi si james Agi si james
                   Agi si james Agi si james
                   Agi si james Agi si james
                   Agi si james Agi si james    
                </p>
                
                <div class="mt-5 d-flex align-items-center gap-4 opacity-50">
                    <small><i class="bi bi-shield-check me-1"></i> Verified Students</small>
                    <small><i class="bi bi-geo-alt me-1"></i> Campus Meetups</small>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="py-5 bg-light border-top border-bottom">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase tracking-wider">Our Process</h6>
            <h2 class="display-5 fw-bold">Simple. Secure. <span class="text-primary">Taga-West.</span></h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">
                Trading within the WVSU community has never been easier. Follow these three steps to get started.
            </p>
        </div>

        <div class="row g-5">
            <div class="col-lg-4">
                <div class="text-center text-lg-start">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle mb-4 shadow" style="width: 60px; height: 60px;">
                        <i class="bi bi-megaphone fs-3"></i>
                    </div>
                    <h4 class="fw-bold">1. Post Your Need</h4>
                    <p class="text-muted">
                        Snap a photo of your pre-loved lab gown, or list your tutoring services. Provide a clear description and set your student-friendly price.
                    </p>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="text-center text-lg-start">
                    <div class="d-inline-flex align-items-center justify-content-center bg-warning text-dark rounded-circle mb-4 shadow" style="width: 60px; height: 60px;">
                        <i class="bi bi-chat-dots fs-3"></i>
                    </div>
                    <h4 class="fw-bold">2. Connect via Chat</h4>
                    <p class="text-muted">
                        No need to share your personal phone number or social media. Use our integrated messaging to discuss details and agree on a price.
                    </p>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="text-center text-lg-start">
                    <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle mb-4 shadow" style="width: 60px; height: 60px;">
                        <i class="bi bi-hand-thumbs-up fs-3"></i>
                    </div>
                    <h4 class="fw-bold">3. Meet & Exchange</h4>
                    <p class="text-muted">
                        Meet up at the **Quezon Hall marker** or the **CAF court**. Inspect the item or receive the service, then settle the payment safely on the spot.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-5 p-4 rounded-4 bg-white shadow-sm border-start border-warning border-5 d-flex align-items-center">
            <i class="bi bi-shield-lock-fill text-warning fs-1 me-4"></i>
            <div>
                <h6 class="fw-bold mb-1">Stay Safe, Taga-West!</h6>
                <p class="small text-muted mb-0">Always meet in well-lit, public areas of the campus. Verify the student's ID if necessary before finalizing the transaction.</p>
            </div>
        </div>
    </div>
</section>

<div class="container mt-5 position-relative z-index-2">
    <div class="row g-4">
         <div>
            <h2 class="fw-bold mb-0">Get Started</h2>
            <p class="text-muted mb-0">Here are some of the things you can do</p>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100 hover-lift">
                <div class="card-body p-4 position-relative">
                    <div class="text-start pe-5">
                        <h6 class="text-primary fw-bold text-uppercase mb-1" style="font-size: 0.8rem;">Marketplace</h6>
                        <h4 class="fw-bold mb-2">Buy Products</h4>
                        <p class="text-muted small">Score pre-loved gears and campus essentials.</p>
                        <a href="products.php" class="btn btn-link p-0 text-decoration-none fw-bold stretched-link">Browse Items <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <i class="bi bi-bag-check-fill position-absolute end-0 bottom-0 mb-n2 me-n2 opacity-10" style="font-size: 5rem;"></i>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100 hover-lift">
                <div class="card-body p-4 position-relative border-bottom border-primary border-4">
                    <div class="text-start pe-5">
                        <h6 class="text-success fw-bold text-uppercase mb-1" style="font-size: 0.8rem;">Earn Cash</h6>
                        <h4 class="fw-bold mb-2">Sell Products</h4>
                        <p class="text-muted small">Declutter your room and earn extra allowance.</p>
                        <a href="addproduct.php" class="btn btn-link p-0 text-decoration-none fw-bold text-success stretched-link">List an Item <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <i class="bi bi-tags-fill position-absolute end-0 bottom-0 mb-n2 me-n2 opacity-10" style="font-size: 5rem;"></i>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100 hover-lift">
                <div class="card-body p-4 position-relative">
                    <div class="text-start pe-5">
                        <h6 class="text-info fw-bold text-uppercase mb-1" style="font-size: 0.8rem;">Get Help</h6>
                        <h4 class="fw-bold mb-2">Avail Services</h4>
                        <p class="text-muted small">Find tutors, artists, and student pros.</p>
                        <a href="services.php" class="btn btn-link p-0 text-decoration-none fw-bold text-info stretched-link">Find Support <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <i class="bi bi-person-workspace position-absolute end-0 bottom-0 mb-n2 me-n2 opacity-10" style="font-size: 5rem;"></i>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm overflow-hidden h-100 hover-lift">
                <div class="card-body p-4 position-relative border-bottom border-info border-4">
                    <div class="text-start pe-5">
                        <h6 class="text-dark fw-bold text-uppercase mb-1" style="font-size: 0.8rem;">Be a Pro</h6>
                        <h4 class="fw-bold mb-2">Offer Services</h4>
                        <p class="text-muted small">Market your skills to the entire campus.</p>
                        <a href="addservice.php" class="btn btn-link p-0 text-decoration-none fw-bold text-dark stretched-link">Start Hosting <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <i class="bi bi-rocket-takeoff-fill position-absolute end-0 bottom-0 mb-n2 me-n2 opacity-10" style="font-size: 5rem;"></i>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold mb-0">Recently Listed</h2>
            <p class="text-muted mb-0">Check out the latest finds from fellow Taga-West.</p>
        </div>
        <a href="products.php" class="btn btn-outline-primary rounded-pill">View All</a>
    </div>

    <?php
    // Fetch recent active listings (products & services)
    $recent = fetchAll(
        "SELECT l.*, c.name AS category_name, u.full_name AS owner_name, p.price, p.stock, s.rate, s.rate_type
         FROM listings l
         LEFT JOIN categories c ON l.category_id = c.category_id
         LEFT JOIN users u ON l.owner_id = u.user_id
         LEFT JOIN products p ON p.listing_id = l.listing_id
         LEFT JOIN services s ON s.listing_id = l.listing_id
        WHERE l.status = 'active'
         ORDER BY l.created_at DESC
         LIMIT 4"
    );
    ?>

    <div class="row g-4">
        <?php if (empty($recent)): ?>
            <div class="col-12">
                <div class="alert alert-info">No recent listings found.</div>
            </div>
        <?php else: ?>
            <?php foreach ($recent as $item):
                $img = $item['image_url'] ? $item['image_url'] : 'https://via.placeholder.com/600x400?text=No+Image';
                $badgeClass = $item['listing_type'] === 'service' ? 'bg-info text-white' : 'bg-white text-dark';
                $isSoldOut = ($item['listing_type'] === 'product' && intval($item['stock'] ?? 0) <= 0);
            ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm item-card <?= $isSoldOut ? 'sold-out' : '' ?>">
                    <div class="position-absolute top-0 start-0 m-2">
                        <span class="badge <?= $badgeClass ?> shadow-sm"><?= htmlspecialchars($item['category_name'] ?? ucfirst($item['listing_type'])) ?></span>
                    </div>
                    <?php if ($isSoldOut): ?>
                        <div class="position-absolute top-0 end-0 m-2">
                            <span class="badge bg-danger text-white shadow-sm">Sold out</span>
                        </div>
                    <?php endif; ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="card-img-top" alt="<?= htmlspecialchars($item['title']) ?>" style="height: 200px; object-fit: cover;">
                    <div class="card-body p-3">
                        <h6 class="card-title mb-1 text-truncate fw-bold"><?= htmlspecialchars($item['title']) ?></h6>
                        <div class="d-flex align-items-center mb-2">
                            <?php if ($item['listing_type'] === 'product'): ?>
                                <span class="text-primary fw-bolder">₱<?= number_format($item['price'] ?? 0, 2) ?></span>
                                <span class="ms-2 badge bg-light text-muted fw-normal" style="font-size: 0.7rem;">Stock <?= intval($item['stock'] ?? 0) ?></span>
                                <?php if ($isSoldOut): ?>
                                    <span class="ms-2 badge bg-danger text-white" style="font-size: 0.7rem;">Sold out</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-primary fw-bolder">₱<?= number_format($item['rate'] ?? 0, 2) ?><?php if (!empty($item['rate_type']) && $item['rate_type'] === 'per_hour') echo '/hr'; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center pt-2 border-top">
                            <div class="bg-secondary rounded-circle me-2" style="width: 20px; height: 20px;"></div>
                            <small class="text-muted">By <?= htmlspecialchars($item['owner_name'] ?? 'User') ?></small>
                        </div>
                        <?php if (!$isSoldOut): ?>
                            <a href="view-product.php?id=<?= intval($item['listing_id']) ?>" class="stretched-link"></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>



<style>
/* Modern styling for the cards */
.item-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
}
/* Ensure product titles don't break layout */
.text-truncate {
    display: block;
    width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Smooth Hover Effect */
.hover-lift {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.hover-lift:hover {
    transform: translateY(-8px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.1) !important;
}
.mt-n5 {
    margin-top: -3.5rem; /* Pushes the cards up into the Hero image */
}

body {
    background-color: #fafafa; /* Deep Navy Background */
    color: #131313;
}



</style>

<style>
/* Dim and disable interaction for sold-out cards */
.item-card.sold-out {
    opacity: 0.72;
    filter: grayscale(20%);
    cursor: default;
    pointer-events: none;
}
</style>



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
</body>
</html>
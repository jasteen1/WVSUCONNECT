<?php
require_once 'db_conn.php';
$q = trim((string) ($_GET['q'] ?? ''));
$categoryFilter = intval($_GET['category'] ?? 0);

$categories = fetchAll(
    "SELECT category_id, name
     FROM categories
     WHERE category_type IN ('service','both')
     ORDER BY name ASC"
);

$servicesSql = "SELECT l.listing_id, l.title, l.description, l.image_url, l.category_id, l.created_at,
                       c.name AS category_name, s.rate, s.rate_type
                FROM listings l
                JOIN services s ON s.listing_id = l.listing_id
                LEFT JOIN categories c ON c.category_id = l.category_id
                WHERE l.listing_type='service' AND l.status='active'";
$params = [];
if ($q !== '') {
    $servicesSql .= " AND (l.title LIKE CONCAT('%', ?, '%') OR l.description LIKE CONCAT('%', ?, '%'))";
    $params[] = $q;
    $params[] = $q;
}
if ($categoryFilter > 0) {
    $servicesSql .= " AND l.category_id = ?";
    $params[] = (string) $categoryFilter;
}
$servicesSql .= " ORDER BY l.created_at DESC";
$services = fetchAll($servicesSql, $params);
function wvsu_services_rate_text($rate, $rateType): string {
    $rt = (string) $rateType;
    if ($rt === 'negotiable') {
        return 'Negotiable';
    }
    $suffix = '';
    if ($rt === 'per_hour') $suffix = '/hr';
    if ($rt === 'per_task') $suffix = '/project';
    return '₱' . number_format((float) $rate, 2) . $suffix;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d4daa">
    <title>Services — WVSU CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-4 pb-5 wvsu-pan-soft" data-io-animate>
    <h1 class="h3 fw-bold mb-1">Services</h1>
    <p class="text-muted small mb-4">Tutors, creatives, tech help—student pros you can message and book.</p>

    <form class="card border-0 shadow-sm mb-4" method="get" action="services.php">
        <div class="card-body p-3 p-md-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Search services</label>
                    <input type="search" class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by service title or description">
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
                    <a href="services.php" class="text-decoration-none">Clear filters</a>
                </div>
            <?php endif; ?>
        </div>
    </form>
    <div class="row g-4 wvsu-stagger">
        <?php if (empty($services)): ?>
            <div class="col-12"><div class="alert alert-info">No services found for your current filters.</div></div>
        <?php else: ?>
            <?php foreach ($services as $s): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm service-card market-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <?php
                                $iu = isset($s['image_url']) ? (string) $s['image_url'] : '';
                                $isVidThumb = $iu !== '' && preg_match('/\.(mp4|webm|mov)$/i', $iu);
                                ?>
                                <?php if ($iu !== '' && !$isVidThumb): ?>
                                    <img src="<?= htmlspecialchars($iu) ?>" class="rounded-circle object-fit-cover" width="45" height="45" alt="">
                                <?php elseif ($isVidThumb): ?>
                                    <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:45px;height:45px"><i class="bi bi-camera-video-fill"></i></div>
                                <?php else: ?>
                                    <img src="<?= 'https://ui-avatars.com/api/?name=' . urlencode($s['title']) ?>" class="rounded-circle" width="45" height="45" alt="">
                                <?php endif; ?>
                                <div class="ms-3">
                                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($s['title']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars((string) ($s['category_name'] ?? 'Service')) ?></small>
                                </div>
                            </div>
                            <p class="text-muted small mb-4"><?= htmlspecialchars($s['description']) ?></p>
                            <div class="d-flex align-items-center justify-content-between mt-auto pt-3 border-top">
                                <div>
                                    <span class="text-dark small d-block mb-0">Starting at</span>
                                    <span class="h5 fw-bold text-primary mb-0"><?= htmlspecialchars(wvsu_services_rate_text($s['rate'], $s['rate_type'])) ?></span>
                                </div>
                                <a href="view-service.php?id=<?= intval($s['listing_id']) ?>" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">View Gig</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <section id="service-tips" class="mt-5">
        <h3 class="h6 fw-bold text-uppercase text-muted mb-3">Freelancer best practices</h3>
        <div class="row g-3 wvsu-stagger">
            <div class="col-md-4"><div class="card border-0 shadow-sm market-card"><div class="card-body"><h4 class="h6 fw-bold">Show proof of work</h4><p class="small text-muted mb-0">Use the portfolio layout to highlight before/after output and short demo clips.</p></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm market-card"><div class="card-body"><h4 class="h6 fw-bold">Price by deliverable</h4><p class="small text-muted mb-0">Add package rows so buyers know exactly what each output includes.</p></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm market-card"><div class="card-body"><h4 class="h6 fw-bold">Stay safe on campus</h4><p class="small text-muted mb-0">Keep conversations on platform and report scammy behavior in one click.</p></div></div></div>
        </div>
    </section>

    <?php $offerHref = !empty($_SESSION['user_id']) ? 'addservice.php' : 'login.php?next=addservice.php'; ?>
    <div class="card border-0 shadow-sm mt-5 mb-4 market-card overflow-hidden" data-io-animate>
        <div class="card-body p-4 p-lg-5 d-flex flex-column flex-lg-row align-items-lg-center gap-3 justify-content-between">
            <div>
                <span class="badge text-bg-primary-subtle text-primary-emphasis mb-2">Freelancer hub</span>
                <h2 class="h5 fw-bold mb-1">Do you have a service to offer? Apply now!</h2>
                <p class="text-muted mb-0">Show your portfolio, set per-output pricing, get discovered by students who need your skills this week.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($offerHref) ?>" class="btn btn-primary rounded-pill px-4 fw-semibold">
                    <i class="bi bi-rocket-takeoff me-1"></i> Start offering
                </a>
                <a href="#service-tips" class="btn btn-outline-primary rounded-pill px-4 fw-semibold">Tips</a>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

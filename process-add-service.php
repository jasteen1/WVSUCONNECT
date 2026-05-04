<?php
require_once 'db_conn.php';
require_once __DIR__ . '/service_portfolio.inc.php';
require_once __DIR__ . '/service_pricing.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: addservice.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    die('You must be logged in to create a service. <a href="login.php">Login</a>');
}

$owner_id = (int) $_SESSION['user_id'];
$title = trim((string) ($_POST['service_title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));

$category_id = (int) ($_POST['category_id'] ?? 0);
$catRow = fetch_master(
    'SELECT category_id FROM categories WHERE category_id = ? AND category_type IN (\'service\', \'both\') LIMIT 1',
    [(string) $category_id]
);
if (! $catRow) {
    header('Location: addservice.php?error=bad_category');
    exit;
}

/** Use shared parser — floatval("1,500") becomes 1.0 in PHP, which wrongly fails validation */
$rate = wvsu_parse_money_string((string) ($_POST['rate'] ?? ''));

$unitRaw = (string) ($_POST['unit'] ?? 'per_output');
$rate_type = wvsu_service_mode_to_rate_type($unitRaw);
$priceItems = wvsu_collect_price_items(
    isset($_POST['price_item_label']) && is_array($_POST['price_item_label']) ? $_POST['price_item_label'] : [],
    isset($_POST['price_item_amount']) && is_array($_POST['price_item_amount']) ? $_POST['price_item_amount'] : []
);
$minListPrice = wvsu_min_price_from_items($priceItems);
/** Package rows sometimes have amounts but missed labels — still recover a minimum displayed price */
if ($minListPrice === null && ! empty($_POST['price_item_amount']) && is_array($_POST['price_item_amount'])) {
    foreach ($_POST['price_item_amount'] as $rawAmt) {
        $v = wvsu_parse_money_string(trim((string) $rawAmt));
        if ($v > 0 && ($minListPrice === null || $v < $minListPrice)) {
            $minListPrice = $v;
        }
    }
}

if ($rate <= 0 && $minListPrice !== null) {
    $rate = $minListPrice;
}
if ($rate_type === 'negotiable' && $rate <= 0) {
    $rate = 0;
}

$messages = [];
if ($title === '') {
    $messages[] = 'Please enter a title for your service.';
}
if ($rate <= 0 && $rate_type !== 'negotiable') {
    $messages[] =
        'Add a starting price above, fill in at least one package price (₱) in your price list, or choose '
        . '“Negotiable quote”. Numbers with commas (e.g. 1,500) are OK.';
}
if ($messages !== []) {
    $msgEsc = htmlspecialchars(implode(' ', $messages), ENT_QUOTES, 'UTF-8');
    die(
        '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fix service form</title></head><body style="font-family:sans-serif;max-width:32rem;margin:2rem auto;">'
        . '<p><strong>Something\'s missing</strong></p><p>'
        . $msgEsc
        . '</p><p><a href="addservice.php">← Back to add service</a></p></body></html>'
    );
}

$portfolioSpans = [];
if (!empty($_POST['portfolio_spans'])) {
    $dec = json_decode((string) $_POST['portfolio_spans'], true);
    if (is_array($dec)) {
        foreach ($dec as $v) {
            $portfolioSpans[] = max(1, min(2, (int) $v));
        }
    }
}

// Cover image (optional if portfolio has ≥1 item)
$image_url = null;
if (!empty($_FILES['service_image']) && $_FILES['service_image']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['service_image']['tmp_name'];
    $name = basename($_FILES['service_image']['name']);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed, true)) {
        die('Invalid cover image format.');
    }
    $targetDir = __DIR__ . '/uploads/services';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $newName = time() . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $name);
    $dest = $targetDir . '/' . $newName;
    if (!move_uploaded_file($tmp, $dest)) {
        die('Failed to save cover image.');
    }
    $image_url = 'uploads/services/' . $newName;
}

// Insert listing placeholder to get listing_id (portfolio needs id in path — we use temp naming without id in wvsu_save)
$insertListingSql = 'INSERT INTO listings (owner_id, category_id, listing_type, title, description, image_url) VALUES (?, ?, \'service\', ?, ?, ?)';
$listing_id = (int) insert(
    $insertListingSql,
    [(string) $owner_id, (string) $category_id, $title, $description, $image_url === null ? '' : (string) $image_url]
);

if (!$listing_id) {
    die('Failed to create listing.');
}

$portfolioFiles = $_FILES['portfolio_files'] ?? null;
$portfolioCount = 0;
$firstFromPortfolio = null;
if (
    is_array($portfolioFiles)
    && !empty($portfolioFiles['name'])
    && is_array($portfolioFiles['name'])
) {
    [$firstFromPortfolio, $portfolioCount] = wvsu_save_portfolio_uploads(
        $master_conn,
        $listing_id,
        $portfolioFiles,
        $portfolioSpans,
        0
    );
}

if ($image_url === null && $portfolioCount === 0) {
    $master_conn->query('DELETE FROM listings WHERE listing_id = ' . intval($listing_id));
    die('Add a cover photo or at least one portfolio photo/video. <a href="addservice.php">Go back</a>');
}

if ($image_url === null && $firstFromPortfolio !== null) {
    $image_url = $firstFromPortfolio;
    $stmt = $master_conn->prepare('UPDATE listings SET image_url = ? WHERE listing_id = ?');
    if ($stmt) {
        $stmt->bind_param('si', $image_url, $listing_id);
        $stmt->execute();
        $stmt->close();
    }
}

$insertServiceSql = 'INSERT INTO services (listing_id, rate, rate_type) VALUES (?, ?, ?)';
insert($insertServiceSql, [$listing_id, (string) $rate, $rate_type]);
wvsu_save_price_items($master_conn, $listing_id, $priceItems);

header('Location: view-service.php?id=' . $listing_id);
exit;

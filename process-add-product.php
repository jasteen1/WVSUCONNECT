<?php

declare(strict_types=1);

require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/product_categories.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: addproduct.php');
    exit;
}

if (empty($_SESSION['user_id'])) {
    die('You must be logged in to create a listing. <a href="login.php">Login</a>');
}

wvsu_ensure_extended_product_categories();

$owner_id = (int) $_SESSION['user_id'];
$title = trim((string) ($_POST['product_name'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$price = floatval($_POST['price'] ?? 0);
$stock = max(0, (int) ($_POST['stock'] ?? 0));
$category_id = (int) ($_POST['category_id'] ?? 0);
$other_detail = trim((string) ($_POST['other_category_detail'] ?? ''));

if ($owner_id <= 0) {
    header('Location: login.php?next=addproduct.php');
    exit;
}

if ($title === '' || $price <= 0) {
    header('Location: addproduct.php?error=invalid_basic');
    exit;
}

$cat = fetch_master(
    'SELECT category_id FROM categories WHERE category_id = ? AND category_type IN (\'product\', \'both\') LIMIT 1',
    [(string) $category_id]
);
if (! $cat) {
    header('Location: addproduct.php?error=bad_category');
    exit;
}

$productCatDropdownCached = wvsu_product_category_dropdown_rows();
$otherIds = wvsu_product_category_other_ids($productCatDropdownCached);
unset($productCatDropdownCached);
$needsOther = in_array($category_id, $otherIds, true);
if ($needsOther) {
    $detailLen = function_exists('mb_strlen')
        ? (int) mb_strlen($other_detail, 'UTF-8')
        : strlen($other_detail);
    if ($other_detail === '' || $detailLen < 2) {
        header('Location: addproduct.php?error=other_missing');
        exit;
    }
    $description = '**Others — ' . $other_detail . "**\n\n" . $description;
}

$image_url = null;
$fileErr = isset($_FILES['product_image']['error']) ? (int) $_FILES['product_image']['error'] : UPLOAD_ERR_NO_FILE;
if ($fileErr !== UPLOAD_ERR_NO_FILE && $fileErr !== UPLOAD_ERR_OK) {
    $errSlug = ($fileErr === UPLOAD_ERR_INI_SIZE || $fileErr === UPLOAD_ERR_FORM_SIZE)
        ? 'photo_too_large'
        : 'photo_upload_failed';
    header('Location: addproduct.php?error=' . rawurlencode($errSlug));
    exit;
}

if (! empty($_FILES['product_image']) && $fileErr === UPLOAD_ERR_OK) {
    $tmp = $_FILES['product_image']['tmp_name'];
    $name = basename((string) $_FILES['product_image']['name']);
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (! in_array(strtolower((string) $ext), $allowed, true)) {
        header('Location: addproduct.php?error=' . rawurlencode('photo_bad_type'));
        exit;
    }
    $targetDir = __DIR__ . '/uploads/products';
    if (! is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $newName = time() . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $name);
    $dest = $targetDir . '/' . $newName;
    if (! move_uploaded_file($tmp, $dest)) {
        header('Location: addproduct.php?error=' . rawurlencode('photo_save_failed'));
        exit;
    }
    $image_url = 'uploads/products/' . $newName;
}

$insertListingSql = 'INSERT INTO listings (owner_id, category_id, listing_type, title, description, image_url) VALUES (?, ?, \'product\', ?, ?, ?)';
$dbBindImage = $image_url === null ? '' : (string) $image_url;
$listing_id = (int) insert(
    $insertListingSql,
    [(string) $owner_id, (string) $category_id, $title, $description, $dbBindImage]
);

if ($listing_id <= 0) {
    header('Location: addproduct.php?error=no_listing_id');
    exit;
}

$insertProductSql = 'INSERT INTO products (listing_id, price, stock) VALUES (?, ?, ?)';
insert($insertProductSql, [(string) $listing_id, (string) $price, (string) $stock]);

header('Location: products.php');
exit;

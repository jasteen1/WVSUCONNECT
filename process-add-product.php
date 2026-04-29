<?php
require_once 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: addproduct.html');
    exit;
}

// Require user to be logged in
if (empty($_SESSION['user_id'])) {
    die('You must be logged in to create a listing. <a href="login.php">Login</a>');
}

$owner_id = $_SESSION['user_id'];
$title = trim($_POST['product_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$stock = intval($_POST['stock'] ?? 0);

if ($title === '' || $price <= 0) {
    die('Invalid product data. <a href="addproduct.html">Go back</a>');
}

// Handle image upload
$image_url = null;
if (!empty($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['product_image']['tmp_name'];
    $name = basename($_FILES['product_image']['name']);
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array(strtolower($ext), $allowed)) {
        die('Invalid image format.');
    }
    $targetDir = __DIR__ . '/uploads/products';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    $newName = time() . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $name);
    $dest = $targetDir . '/' . $newName;
    if (!move_uploaded_file($tmp, $dest)) {
        die('Failed to save uploaded image.');
    }
    $image_url = 'uploads/products/' . $newName;
}

// Insert basic listing (use category_id = 8 'Others' for now)
$insertListingSql = "INSERT INTO listings (owner_id, category_id, listing_type, title, description, image_url) VALUES (?, ?, 'product', ?, ?, ?)";
$listing_id = insert($insertListingSql, [$owner_id, 8, $title, $description, $image_url]);

if (!$listing_id) {
    die('Failed to create listing.');
}

// Insert into products table
$insertProductSql = "INSERT INTO products (listing_id, price, stock) VALUES (?, ?, ?)";
$prod_id = insert($insertProductSql, [$listing_id, (string)$price, (string)$stock]);

header('Location: products.php');
exit;

?>

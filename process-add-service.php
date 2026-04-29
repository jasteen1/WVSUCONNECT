<?php
require_once 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: addservice.html');
    exit;
}

if (empty($_SESSION['user_id'])) {
    die('You must be logged in to create a service. <a href="login.php">Login</a>');
}

$owner_id = $_SESSION['user_id'];
$title = trim($_POST['service_title'] ?? '');
$description = trim($_POST['description'] ?? '');
$rate = floatval($_POST['rate'] ?? 0);
$unit = $_POST['unit'] ?? 'fixed';

if ($title === '' || $rate <= 0) {
    die('Invalid service data. <a href="addservice.html">Go back</a>');
}

// Handle image upload
$image_url = null;
if (!empty($_FILES['service_image']) && $_FILES['service_image']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['service_image']['tmp_name'];
    $name = basename($_FILES['service_image']['name']);
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array(strtolower($ext), $allowed)) {
        die('Invalid image format.');
    }
    $targetDir = __DIR__ . '/uploads/services';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    $newName = time() . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $name);
    $dest = $targetDir . '/' . $newName;
    if (!move_uploaded_file($tmp, $dest)) {
        die('Failed to save uploaded image.');
    }
    $image_url = 'uploads/services/' . $newName;
}

// Insert listing with listing_type = 'service'
$insertListingSql = "INSERT INTO listings (owner_id, category_id, listing_type, title, description, image_url) VALUES (?, ?, 'service', ?, ?, ?)";
$listing_id = insert($insertListingSql, [$owner_id, 8, $title, $description, $image_url]);

if (!$listing_id) die('Failed to create listing.');

// Insert into services table
$insertServiceSql = "INSERT INTO services (listing_id, rate, rate_type) VALUES (?, ?, ?)";
$svc_id = insert($insertServiceSql, [$listing_id, (string)$rate, $unit]);

header('Location: services.php');
exit;

?>

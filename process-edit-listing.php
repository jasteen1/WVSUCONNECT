<?php
require_once 'db_conn.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = intval($_SESSION['user_id']);
$listing_id = intval($_POST['listing_id'] ?? 0);
if ($listing_id <= 0) { header('Location: your_listings.php'); exit; }

$owner = fetch("SELECT owner_id, listing_type FROM listings WHERE listing_id = ?", [$listing_id]);
if (!$owner || intval($owner['owner_id']) !== $uid) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

// gather inputs
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$category_id = intval($_POST['category_id'] ?? 0);
$status = $_POST['status'] ?? 'active';

// handle image upload if provided; otherwise preserve existing image URL
$existing = $_POST['existing_image_url'] ?? '';
$image_url = $existing;
if (!empty($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['product_image']['tmp_name'];
    $orig = basename($_FILES['product_image']['name']);
    $ext = pathinfo($orig, PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) {
        // ignore invalid type and keep existing
        $image_url = $existing;
    } else {
        $subdir = ($owner['listing_type'] === 'product') ? 'uploads/products' : 'uploads/services';
        if (!is_dir(__DIR__ . '/' . $subdir)) mkdir(__DIR__ . '/' . $subdir, 0755, true);
        $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetPath = __DIR__ . '/' . $subdir . '/' . $newName;
        if (move_uploaded_file($tmp, $targetPath)) {
            $image_url = $subdir . '/' . $newName;
        } else {
            $image_url = $existing;
        }
    }
}

// update listings row
$stmt = $master_conn->prepare("UPDATE listings SET title = ?, description = ?, category_id = ?, image_url = ?, status = ? WHERE listing_id = ?");
if ($stmt) {
    $stmt->bind_param('ssissi', $title, $description, $category_id, $image_url, $status, $listing_id);
    $stmt->execute();
    $stmt->close();
} else {
    // fallback: minimal safe update
    $qtitle = $master_conn->real_escape_string($title);
    $qdesc = $master_conn->real_escape_string($description);
    $qimage = $master_conn->real_escape_string($image_url);
    $master_conn->query("UPDATE listings SET title='".$qtitle."', description='".$qdesc."', category_id=".intval($category_id).", image_url='".$qimage."', status='".$master_conn->real_escape_string($status)."' WHERE listing_id=".intval($listing_id));
}

if ($owner['listing_type'] === 'product') {
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $s = $master_conn->prepare("UPDATE products SET price = ?, stock = ? WHERE listing_id = ?");
    $s->bind_param('dii', $price, $stock, $listing_id);
    $s->execute();
    // update listing status based on stock
    $new_status = $stock <= 0 ? 'sold_out' : $status;
    $u = $master_conn->prepare("UPDATE listings SET status = ? WHERE listing_id = ?");
    $u->bind_param('si', $new_status, $listing_id);
    $u->execute();
} else {
    $rate = floatval($_POST['rate'] ?? 0);
    $rate_type = $_POST['rate_type'] ?? 'fixed';
    $s = $master_conn->prepare("UPDATE services SET rate = ?, rate_type = ? WHERE listing_id = ?");
    $s->bind_param('dsi', $rate, $rate_type, $listing_id);
    $s->execute();
}

header('Location: your_listings.php');
exit;
?>

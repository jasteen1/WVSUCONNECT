<?php
require_once 'db_conn.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = intval($_SESSION['user_id']);
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: your_listings.php'); exit; }

// Fetch listing strictly from slave (port 3307).
$slave_only = @new mysqli('localhost', 'root', '', 'wvsudb', 3307);
if ($slave_only->connect_error) {
    header('HTTP/1.1 503 Service Unavailable');
    echo 'Read replica unavailable. Try again later.';
    exit;
}

$stmt = $slave_only->prepare("SELECT l.*, p.price, p.stock, s.rate, s.rate_type FROM listings l LEFT JOIN products p ON p.listing_id = l.listing_id LEFT JOIN services s ON s.listing_id = l.listing_id WHERE l.listing_id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row || intval($row['owner_id'] ?? 0) !== $uid) { 
    header('HTTP/1.1 403 Forbidden'); 
    echo 'Forbidden: You do not own this listing.'; 
    exit; 
}

// --- EXTRACTED SIMPLE VARIABLES ---
$listing_id   = $row['listing_id'] ?? $id;
$title        = $row['title'] ?? '';
$description  = $row['description'] ?? '';
$category_id  = intval($row['category_id'] ?? 0);
$status       = $row['status'] ?? 'active';
$listing_type = $row['listing_type'] ?? 'product';

// Products vs Services variables
$price        = floatval($row['price'] ?? 0);
$stock        = intval($row['stock'] ?? 0);
$rate         = floatval($row['rate'] ?? 0);
$rate_type    = $row['rate_type'] ?? 'fixed';

// Image variable
$image_url    = !empty($row['image_url']) ? $row['image_url'] : 'https://images.unsplash.com/photo-1555448248-2571daf6344b?q=80&w=300&auto=format&fit=crop';
// ----------------------------------

$catStmt = $slave_only->prepare("SELECT category_id, name FROM categories ORDER BY name");
$catStmt->execute();
$catRes = $catStmt->get_result();
$categories = [];
while ($c = $catRes->fetch_assoc()) $categories[] = $c;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Listing - WVSU Marketplace</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container mt-5 mb-5">
    <div class="d-flex align-items-center mb-4">
        <a href="your_listings.php" class="btn btn-outline-secondary rounded-circle me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="fw-bold mb-0">Edit Listing</h2>
    </div>

    <form action="process-edit-listing.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
        <input type="hidden" name="existing_image_url" value="<?php echo htmlspecialchars($row['image_url'] ?? ''); ?>">
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-pencil-square me-2"></i>Listing Details</h5>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Title</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="6" required><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center d-flex flex-column">
                        <h5 class="fw-bold mb-3 text-info text-start"><i class="bi bi-image me-2"></i>Image</h5>
                        <div class="border border-dashed rounded-3 py-5 bg-light flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                            <img id="preview" src="<?php echo htmlspecialchars($image_url); ?>" alt="Current Image" class="rounded-3 shadow-sm mb-3" style="height:120px; object-fit:cover;">

                            <input type="file" name="product_image" id="product_image" class="form-control w-75 mx-auto" accept="image/*">
                            <p class="mt-2 text-muted small px-3">Upload a new image to replace the current one.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-success"><i class="bi bi-tag me-2"></i>Category</h5>
                        <label class="form-label fw-semibold">Select Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="" disabled>Choose a category...</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo intval($c['category_id']); ?>" <?php if(intval($c['category_id']) === $category_id) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-4 p-3 bg-light rounded-3 small text-muted">
                            <i class="bi bi-info-circle me-1"></i> Choose the most appropriate category so buyers can find your post.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-warning"><i class="bi bi-cash-stack me-2"></i>Pricing & Stock</h5>
                        <div class="row g-3">
                            <?php if ($listing_type === 'product'): ?>
                                <div class="col-12 mb-2">
                                    <label class="form-label fw-semibold">Price (₱)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" name="price" class="form-control" step="0.01" value="<?php echo sprintf('%.2f', $price); ?>" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Quantity in Stock</label>
                                    <input type="number" name="stock" class="form-control" value="<?php echo $stock; ?>" required>
                                </div>
                            <?php else: ?>
                                <div class="col-12 mb-2">
                                    <label class="form-label fw-semibold">Rate (₱)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" name="rate" class="form-control" step="0.01" value="<?php echo sprintf('%.2f', $rate); ?>" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Rate Type</label>
                                    <select name="rate_type" class="form-select">
                                        <option value="fixed" <?php if($rate_type === 'fixed') echo 'selected'; ?>>Fixed</option>
                                        <option value="per_hour" <?php if($rate_type === 'per_hour') echo 'selected'; ?>>Per Hour</option>
                                        <option value="negotiable" <?php if($rate_type === 'negotiable') echo 'selected'; ?>>Negotiable</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary w-100 btn-lg fw-bold shadow-sm">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="your_listings.php" class="btn btn-secondary px-4 fw-bold">Cancel</a>
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label fw-bold mb-0 text-muted">Status:</label>
                        <select name="status" class="form-select w-auto fw-bold">
                            <option value="active" <?php if($status === 'active') echo 'selected'; ?>>Active (Visible)</option>
                            <option value="inactive" <?php if($status === 'inactive') echo 'selected'; ?>>Inactive (Hidden)</option>
                            <option value="sold_out" <?php if($status === 'sold_out') echo 'selected'; ?>>Sold Out</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<style>
.border-dashed { border-style: dashed !important; border-width: 2px !important; border-color: #dee2e6 !important; }
.card { border-radius: 1rem; }
.form-control:focus, .form-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.1); }
</style>

<script>
// Image preview logic
document.getElementById('product_image').addEventListener('change', function(e){
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(ev){ document.getElementById('preview').src = ev.target.result; };
  reader.readAsDataURL(file);
});
</script>

</body>
</html>
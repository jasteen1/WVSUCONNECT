<?php
require_once 'db_conn.php';
require_once __DIR__ . '/product_categories.inc.php';
wvsu_ensure_extended_product_categories();
$wvsuProductCategories = wvsu_product_category_dropdown_rows();
$wvsuOtherCategoryIds = wvsu_product_category_other_ids($wvsuProductCategories);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d4daa">
    <title>List a product — WVSU CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body>
    <?php include 'navbar.php'; ?>

<div class="container mt-5 mb-5 pb-5 wvsu-pan-soft" data-io-animate>
    <div class="d-flex align-items-center mb-4">
        <a href="products.php" class="btn btn-outline-secondary rounded-circle me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="fw-bold mb-0">Add New Product</h2>
    </div>

    <?php
    $wvsuAddErr = (string) ($_GET['error'] ?? '');
    $wvsuAddErrMsg = match ($wvsuAddErr) {
        'bad_category' => 'Please choose a valid product category.',
        'other_missing' => 'Under “Others”, please briefly say what kind of item it is.',
        'invalid_basic' => 'Add a product name and a price greater than zero.',
        'no_listing_id' => 'The server could not save this listing (database ID issue). Ensure `listings.listing_id` uses AUTO_INCREMENT — check `db_conn_debug.log` after a page load, or re-import `wvsudb.sql`.',
        'photo_too_large' => 'Photo is too large for the server limit. In XAMPP, raise upload_max_filesize and post_max_size in php.ini, then restart Apache.',
        'photo_upload_failed' => 'Photo upload didn’t finish. Try again or use a smaller file.',
        'photo_bad_type' => 'Please use JPG, PNG, WebP, or GIF for the product photo.',
        'photo_save_failed' => 'Could not save the image on disk. Make sure uploads/products is writable (e.g. chmod 777 uploads/products).',
        default => '',
    };
    ?>
    <?php if ($wvsuAddErrMsg !== ''): ?>
        <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4"><?= htmlspecialchars($wvsuAddErrMsg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form action="process-add-product.php" method="POST" enctype="multipart/form-data" id="wvsuAddProductForm">
        <div class="row g-4">
            
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-pencil-square me-2"></i>Product Description</h5>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Product Name</label>
                            <input type="text" name="product_name" class="form-control" placeholder="What are you selling?" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Details</label>
                            <textarea name="description" class="form-control" rows="5" placeholder="Condition, size, etc." required></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 text-center d-flex flex-column">
                        <h5 class="fw-bold mb-3 text-info text-start"><i class="bi bi-image me-2"></i>Product Image</h5>
                        <div class="border border-dashed rounded-3 py-5 bg-light flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-cloud-arrow-up display-4 text-muted mb-3"></i>
                            <input type="file" name="product_image" class="form-control w-75 mx-auto" accept="image/*" required>
                            <p class="mt-2 text-muted small px-3">High-quality photos help sell your items faster!</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-success"><i class="bi bi-tag me-2"></i>Category</h5>
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" id="wvsuAddProductCategory" class="form-select" required>
                            <option value="" selected disabled>Choose a category...</option>
                            <?php foreach ($wvsuProductCategories as $wc):
                                $cid = (int) ($wc['category_id'] ?? 0);
                                if ($cid <= 0) {
                                    continue;
                                }
                                $pname = trim((string) ($wc['parent_name'] ?? ''));
                                $cname = trim((string) ($wc['name'] ?? ''));
                                $shown = $pname !== ''
                                    ? htmlspecialchars($pname . ' — ' . $cname, ENT_QUOTES, 'UTF-8')
                                    : htmlspecialchars($cname, ENT_QUOTES, 'UTF-8');
                                $io = (int) ($wc['is_others'] ?? 0);
                                ?>
                                <option value="<?= $cid ?>" <?= $io === 1 ? 'data-wvsu-other="1"' : '' ?>><?= $shown ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="wvsuOtherCategoryWrap" class="mt-3" style="display:none">
                            <label class="form-label fw-semibold" for="wvsu_other_category_detail">What product is this? <span class="text-muted fw-normal">(Others)</span></label>
                            <input type="text" name="other_category_detail" id="wvsu_other_category_detail" class="form-control" maxlength="200" autocomplete="off" placeholder="e.g. Scientific calculator, ID lace, gaming mouse…">
                            <div class="form-text">Required when you choose <strong>Others</strong> so shoppers know what they’re browsing.</div>
                        </div>
                        <div class="mt-4 p-3 bg-light rounded-3 small text-muted">
                            <i class="bi bi-info-circle me-1"></i> Categories match the marketplace filters. Pick the closest fit, or choose <strong>Others</strong> and describe the item.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-warning"><i class="bi bi-cash-stack me-2"></i>Pricing & Stock</h5>
                        <div class="row g-3">
                            <div class="col-12 mb-2">
                                <label class="form-label fw-semibold">Price (₱)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="price" class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Quantity in Stock</label>
                                <input type="number" name="stock" class="form-control" placeholder="How many do you have?" required>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" name="submit" class="btn btn-primary w-100 btn-lg fw-bold shadow-sm">
                                Create Listing
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div> </form>
</div>

<style>
.border-dashed {
    border: 2px dashed #dee2e6 !important;
}
.card {
    border-radius: 1rem;
}
</style>
<style>
/* Custom dashed border for the image upload area */
.border-dashed {
    border-style: dashed !important;
    border-width: 2px !important;
    border-color: #dee2e6 !important;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
}
</style>


<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var sel = document.getElementById('wvsuAddProductCategory');
    var wrap = document.getElementById('wvsuOtherCategoryWrap');
    var inp = document.getElementById('wvsu_other_category_detail');
    var form = document.getElementById('wvsuAddProductForm');
    if (!sel || !wrap || !inp || !form) return;
    function toggle() {
        var opt = sel.options[sel.selectedIndex];
        var isOther = opt && opt.getAttribute('data-wvsu-other') === '1';
        wrap.style.display = isOther ? '' : 'none';
        inp.required = !!isOther;
        if (!isOther) inp.value = '';
    }
    sel.addEventListener('change', toggle);
    form.addEventListener('submit', function () { toggle(); });
    toggle();
})();
</script>
</body>
</html>

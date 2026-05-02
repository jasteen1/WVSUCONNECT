<?php
require_once 'db_conn.php';
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

    <form action="process-add-product.php" method="POST" enctype="multipart/form-data">
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
                        <label class="form-label fw-semibold">Select Listing Type</label>
                        <select name="category" class="form-select" required>
                            <option value="" selected disabled>Choose a category...</option>
                            <option value="books">Books & References</option>
                            <option value="uniforms">Uniforms</option>
                            <option value="gadgets">Electronics</option>
                            <option value="supplies">School Supplies</option>
                        </select>
                        <div class="mt-4 p-3 bg-light rounded-3 small text-muted">
                            <i class="bi bi-info-circle me-1"></i> Make sure to select the correct category so students can find your post in the main marketplace.
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
</body>
</html>

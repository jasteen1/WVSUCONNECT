<?php
require_once 'db_conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - WVSU Marketplace</title>
    <!-- Latest compiled and minified CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Latest compiled JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
</head>
<body>
    <?php include 'navbar.php'; ?>

<div class="container mt-5 mb-5">
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
                    <li class="mb-2"><a href="index.html" class="text-decoration-none text-muted">Home</a></li>
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

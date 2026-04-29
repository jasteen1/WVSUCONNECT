<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WVSU Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card border-0 shadow-lg p-4" style="max-width: 400px; width: 100%; border-radius: 20px;">
            <div class="text-center mb-4">
                <h2 class="fw-bold text-primary">WVSU Connect</h2>
                <p class="text-muted">Marketplace & Services</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="process-login.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">WVSU Email</label>
                    <input type="email" name="email" class="form-control rounded-pill px-3" placeholder="student.name@wvsu.edu.ph" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control rounded-pill px-3" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
            </form>
            
            <div class="text-center mt-4">
                <p class="small text-muted">
                    Don't have an account? 
                    <a href="register.php" class="text-decoration-none fw-bold text-primary">Register here</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
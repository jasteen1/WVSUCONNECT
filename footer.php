<?php
$fLogged =
    session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_id']);
$footerSellProduct = $fLogged ? 'addproduct.php' : 'login.php?next=' . rawurlencode('addproduct.php');
$footerSellService = $fLogged ? 'addservice.php' : 'login.php?next=' . rawurlencode('addservice.php');
?>
<footer class="wvsu-footer mt-5" role="contentinfo">
    <div class="wvsu-footer__ribbon" aria-hidden="true"></div>

    <div class="container position-relative px-4 px-lg-3">
        <div class="wvsu-footer__inner py-5 py-lg-5">

            <div class="row g-5 g-xl-5 align-items-start justify-content-between">
                <div class="col-lg-6 col-xl-5">
                    <p class="wvsu-footer__eyebrow mb-2">Taga-West · Student marketplace</p>
                    <a href="index.php" class="wvsu-footer__brand d-inline-block text-decoration-none mb-3">
                        <img src="assets/logowithtext.png" alt="WVSU CONNECT" class="footer-brand-mark" loading="lazy">
                    </a>
                    <p class="wvsu-footer__tagline mb-2">Shop, sell, and book talent—without leaving campus.</p>
                    <p class="wvsu-footer__desc text-muted mb-4 mb-lg-5">
                        On-platform messaging, fair meetups, and listings from people you actually recognize.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="products.php" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                            Browse products
                        </a>
                        <a href="services.php" class="btn btn-outline-primary rounded-pill px-4 fw-semibold wvsu-footer__btn-soft">
                            Explore services
                        </a>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3 col-xl-2">
                    <nav class="wvsu-footer__nav" aria-labelledby="wvsu-footer-explore">
                        <h2 id="wvsu-footer-explore" class="wvsu-footer__label">Explore</h2>
                        <ul class="list-unstyled wvsu-footer__list mb-0">
                            <li><a href="index.php">Home</a></li>
                            <li><a href="products.php">Products</a></li>
                            <li><a href="services.php">Services</a></li>
                            <li><a href="safety.php">Safety &amp; meetups</a></li>
                            <?php if ($fLogged): ?>
                                <li><a href="your_listings.php">Your listings</a></li>
                                <li><a href="messages.php">Messages</a></li>
                            <?php else: ?>
                                <li><a href="login.php">Log in</a></li>
                                <li><a href="register.php">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>

                <div class="col-sm-6 col-lg-3 col-xl-2">
                    <nav class="wvsu-footer__nav" aria-labelledby="wvsu-footer-sell">
                        <h2 id="wvsu-footer-sell" class="wvsu-footer__label">Sell</h2>
                        <ul class="list-unstyled wvsu-footer__list mb-0">
                            <li><a href="<?= htmlspecialchars($footerSellProduct) ?>">List a product</a></li>
                            <li><a href="<?= htmlspecialchars($footerSellService) ?>">Offer a service</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="col-lg-12 col-xl-3">
                    <aside class="wvsu-footer__aside rounded-4 h-100">
                        <p class="wvsu-footer__aside-title mb-3">Trade with care</p>
                        <ul class="wvsu-footer__aside-list mb-4">
                            <li>Chat and pay through the platform when you can.</li>
                            <li>Meet in open, public campus spots in daylight.</li>
                            <li>Report anything that feels off—we review quickly.</li>
                        </ul>
                        <div class="d-flex flex-wrap align-items-center gap-2 gap-sm-3">
                            <a href="safety.php" class="btn btn-sm btn-light rounded-pill fw-semibold border">Safety tips</a>
                            <a href="contact.php" class="btn btn-sm btn-outline-light rounded-pill fw-semibold border border-white border-opacity-25">Contact listing</a>
                            <a href="index.php#get-started" class="wvsu-footer__text-link small fw-semibold">Why WVSU Connect <i class="bi bi-arrow-right-short fs-5 align-middle" aria-hidden="true"></i></a>
                        </div>
                        <div class="wvsu-footer__aside-mark pt-4 mt-2">
                            <img src="assets/textonly.png" alt="" class="wvsu-footer__aside-logo" loading="lazy">
                        </div>
                    </aside>
                </div>
            </div>

            <div class="wvsu-footer__bottom row align-items-center g-3 pt-5 mt-2 mt-lg-4">
                <div class="col-md-6 d-flex align-items-center gap-2 justify-content-md-start justify-content-center text-md-start text-center">
                    <img src="assets/wvsuconnectlogo.png" alt="" width="28" height="28" loading="lazy" class="wvsu-footer__mark flex-shrink-0">
                    <span class="small text-secondary">&copy; <?= date('Y'); ?> WVSU CONNECT</span>
                </div>
                <div class="col-md-6 text-md-end text-center">
                    <span class="small text-secondary">For campus · Stay safe · Trade fair</span>
                </div>
            </div>
        </div>
    </div>
</footer>

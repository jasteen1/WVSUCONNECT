<?php
// Ensure DB + session are available for unread counts
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/messaging_schema.inc.php';

$unreadCount = 0;
$messagesNavHref = 'messages.php';
if (! empty($_SESSION['user_id'])) {
    $navMsgs = wvsu_user_messages_nav_state((int) $_SESSION['user_id']);
    $unreadCount = $navMsgs['count'];
    $messagesNavHref = $navMsgs['inbox_href'];
}

$navActive = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isAdmin = !empty($_SESSION['role_id']) && intval($_SESSION['role_id']) === 1;
$navProductSell = !empty($_SESSION['user_id'])
    ? 'addproduct.php'
    : 'login.php?next=' . rawurlencode('addproduct.php');
$navServiceSell = !empty($_SESSION['user_id'])
    ? 'addservice.php'
    : 'login.php?next=' . rawurlencode('addservice.php');
$navSellProductClick = ! empty($_SESSION['user_id'])
    ? ' onclick="return confirm(\'Go to the form to add a new product listing?\');"'
    : '';
$navSellServiceClick = ! empty($_SESSION['user_id'])
    ? ' onclick="return confirm(\'Go to the form to add a new service listing?\');"'
    : '';
/** Sticky nav scrolls page content underneath it; messages is a full-height inbox where that hides the chat header. */
$navStickyClass = ($navActive === 'messages.php') ? '' : ' sticky-top';

include __DIR__ . '/entry_loader.inc.php';

?>
<nav class="navbar navbar-expand-lg navbar-wvsu py-2<?= $navStickyClass ?>">
    <div class="container">
        <a class="navbar-brand brand-wvsu py-2" href="index.php" aria-label="WVSU Connect home">
            <img src="assets/wvsuconnectlogo.png" alt="" class="brand-wvsu__mark d-lg-none" height="42" decoding="async">
            <span class="d-none d-lg-inline-flex align-items-center">
                <img src="assets/logowithtext.png" alt="WVSU CONNECT" class="brand-wvsu__wordmark" height="38" decoding="async">
            </span>
        </a>

        <button class="navbar-toggler rounded-3 border-secondary-subtle" type="button" data-bs-toggle="collapse" data-bs-target="#navCenter" aria-controls="navCenter" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navCenter">
            <ul class="navbar-nav mx-auto mt-3 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link px-lg-3<?= $navActive === 'index.php' ? ' text-primary fw-bold' : '' ?>" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-lg-3<?= $navActive === 'products.php' ? ' text-primary fw-bold' : '' ?>" href="products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-lg-3<?= $navActive === 'services.php' ? ' text-primary fw-bold' : '' ?>" href="services.php">Services</a>
                </li>
                <li class="nav-item dropdown py-1 py-lg-0 my-1 my-lg-0 px-lg-1 align-self-lg-center">
                    <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill px-3 py-2 shadow-sm dropdown-toggle navbar-wvsu__shortcuts-btn w-100 w-lg-auto border-0" id="navbarShortcutsMenu" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true" aria-controls="navbarShortcutsPanels">
                        <span class="d-inline-flex align-items-center gap-1"><i class="bi bi-grid-3x2-gap-fill" aria-hidden="true"></i> Shortcuts</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-lg-start navbar-wvsu__shortcuts-menu mt-2 p-2 shadow-lg border-0 rounded-4 w-100 w-lg-auto" id="navbarShortcutsPanels" aria-labelledby="navbarShortcutsMenu" style="min-width: min(17.5rem, 94vw);">
                        <li class="px-1 pb-1"><span class="navbar-wvsu__shortcut-heading">Browse</span></li>
                        <li>
                            <a class="dropdown-item navbar-wvsu__shortcut-item rounded-3 py-2 d-flex align-items-center gap-2" href="products.php">
                                <span class="navbar-wvsu__shortcut-ico" aria-hidden="true"><i class="bi bi-bag-heart-fill"></i></span>
                                <span class="flex-grow-1"><span class="d-block fw-bold">Products</span><span class="d-block small text-muted fw-normal">Shop listings</span></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item navbar-wvsu__shortcut-item rounded-3 py-2 d-flex align-items-center gap-2" href="services.php">
                                <span class="navbar-wvsu__shortcut-ico" aria-hidden="true"><i class="bi bi-palette2"></i></span>
                                <span class="flex-grow-1"><span class="d-block fw-bold">Services</span><span class="d-block small text-muted fw-normal">Book skills &amp; help</span></span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li class="px-1 pb-1"><span class="navbar-wvsu__shortcut-heading">Sell</span></li>
                        <li>
                            <a class="dropdown-item navbar-wvsu__shortcut-item rounded-3 py-2 d-flex align-items-center gap-2" href="<?= htmlspecialchars($navProductSell) ?>"<?= $navSellProductClick ?>>
                                <span class="navbar-wvsu__shortcut-ico navbar-wvsu__shortcut-ico--gold" aria-hidden="true"><i class="bi bi-plus-lg"></i></span>
                                <span class="flex-grow-1"><span class="d-block fw-bold">List a product</span><span class="d-block small text-muted fw-normal">Stuff, gear &amp; goods</span></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item navbar-wvsu__shortcut-item rounded-3 py-2 d-flex align-items-center gap-2" href="<?= htmlspecialchars($navServiceSell) ?>"<?= $navSellServiceClick ?>>
                                <span class="navbar-wvsu__shortcut-ico navbar-wvsu__shortcut-ico--gold" aria-hidden="true"><i class="bi bi-stars"></i></span>
                                <span class="flex-grow-1"><span class="d-block fw-bold">Offer a service</span><span class="d-block small text-muted fw-normal">Rates &amp; portfolio</span></span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li>
                            <a class="dropdown-item navbar-wvsu__shortcut-item rounded-3 py-2 d-flex align-items-center gap-2" href="safety.php">
                                <span class="navbar-wvsu__shortcut-ico navbar-wvsu__shortcut-ico--muted" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                                <span class="flex-grow-1"><span class="d-block fw-bold">Safety &amp; meetups</span><span class="d-block small text-muted fw-normal">Campus trade reminders</span></span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <li class="nav-item d-lg-none">
                        <a class="nav-link px-lg-3" href="your_listings.php">Your listings</a>
                    </li>
                    <?php if ($isAdmin): ?>
                        <li class="nav-item d-lg-none">
                            <a class="nav-link px-lg-3<?= $navActive === 'admin_dashboard.php' ? ' text-primary fw-bold' : '' ?>" href="admin_dashboard.php">Admin</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <div class="d-lg-none w-100 px-2 px-sm-0 mt-2 mb-1" role="region" aria-label="Quick shortcuts">
                <p class="text-uppercase small fw-bold text-secondary mb-2 px-1">Quick shortcuts</p>
                <div class="row g-2">
                    <div class="col-6">
                        <a href="products.php" class="btn btn-primary btn-sm w-100 rounded-pill fw-semibold py-2"><i class="bi bi-bag-heart me-1" aria-hidden="true"></i>Products</a>
                    </div>
                    <div class="col-6">
                        <a href="services.php" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-semibold py-2"><i class="bi bi-palette2 me-1" aria-hidden="true"></i>Services</a>
                    </div>
                    <div class="col-6">
                        <a href="<?= htmlspecialchars($navProductSell) ?>" class="btn btn-warning btn-sm w-100 rounded-pill fw-bold text-dark py-2"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>List item</a>
                    </div>
                    <div class="col-6">
                        <a href="<?= htmlspecialchars($navServiceSell) ?>" class="btn btn-outline-dark btn-sm w-100 rounded-pill fw-semibold py-2 border-2"><i class="bi bi-stars me-1" aria-hidden="true"></i>Offer skill</a>
                    </div>
                    <div class="col-12 mt-1">
                        <a href="safety.php" class="btn btn-outline-secondary btn-sm w-100 rounded-pill fw-semibold py-2"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Safety &amp; meetups</a>
                    </div>
                </div>
            </div>

            <div class="navbar-nav align-items-lg-center flex-row gap-2 ms-lg-3 pt-3 pb-2 pb-lg-0 pt-lg-0 justify-content-between border-top border-lg-0">
                <a href="<?= htmlspecialchars($messagesNavHref) ?>" class="nav-link position-relative p-2 rounded-3 <?= $navActive === 'messages.php' ? ' text-primary fw-bold' : '' ?>" aria-label="Messages">
                    <i class="bi bi-chat-dots-fill fs-5"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger fw-semibold px-2" style="font-size: 0.65rem;">
                            <?= $unreadCount > 99 ? '99+' : htmlspecialchars((string) $unreadCount) ?>
                        </span>
                    <?php endif; ?>
                </a>
                <div class="dropdown">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center rounded-3 px-2" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 me-lg-2"></i>
                        <span class="d-none d-md-inline fw-semibold small"><?php echo isset($_SESSION['full_name'])
                            ? htmlspecialchars((string) $_SESSION['full_name'])
                            : 'Account'; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 py-3">
                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <li><a class="dropdown-item px-3 py-2 rounded-2" href="profile.php?id=<?= intval($_SESSION['user_id']) ?>"><i class="bi bi-person-badge me-2 text-primary"></i>My profile</a></li>
                            <li><a class="dropdown-item px-3 py-2 rounded-2" href="edit_profile.php"><i class="bi bi-pencil-square me-2 text-secondary"></i>Edit profile</a></li>
                            <li><a class="dropdown-item px-3 py-2 rounded-2" href="your_listings.php"><i class="bi bi-shop me-2 text-primary"></i>Your listings</a></li>
                            <?php if ($isAdmin): ?>
                                <li><a class="dropdown-item px-3 py-2 rounded-2" href="admin_dashboard.php"><i class="bi bi-shield-check me-2 text-warning"></i>Admin dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item px-3 py-2 rounded-2 text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item px-3 py-2 rounded-2 fw-semibold text-primary" href="login.php">Login</a></li>
                            <li><a class="dropdown-item px-3 py-2 rounded-2" href="register.php">Create account</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<?php
// Ensure DB + session are available for unread counts
require_once __DIR__ . '/db_conn.php';

// Compute unread messages count for current user
$unreadCount = 0;
if (!empty($_SESSION['user_id'])) {
    $me = intval($_SESSION['user_id']);
    $row = fetch("SELECT COUNT(*) AS cnt FROM messages m JOIN conversations c ON m.conversation_id = c.conversation_id WHERE m.is_read = 0 AND m.sender_id != ? AND (c.participant_a = ? OR c.participant_b = ?) LIMIT 1", [$me, $me, $me]);
    if ($row && isset($row['cnt'])) $unreadCount = intval($row['cnt']);
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="logo.png" alt="Logo" width="35" class="me-2">
            <span class="fw-bold">WVSU <span class="text-primary">Market</span></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navCenter">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navCenter">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link px-3" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="products.php">Products</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="services.php">Services</a></li>
            </ul>

            <div class="navbar-nav align-items-center flex-row gap-2">
                <a href="messages.php" class="nav-link position-relative p-2">
                    <i class="bi bi-chat-left-text fs-5"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;"><?php echo ($unreadCount > 99) ? '99+' : htmlspecialchars($unreadCount); ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5 me-1"></i>
                        <span class="d-none d-md-inline"><?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Account'; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="your_listings.php">Your Listings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="login.php">Login</a></li>
                            <li><a class="dropdown-item" href="register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<?php
require_once 'db_conn.php';
require_once __DIR__ . '/moderation.inc.php';

$adminId = intval($_SESSION['user_id'] ?? 0);
if (!wvsu_user_is_admin($master_conn, $adminId)) {
    header('Location: index.php');
    exit;
}
wvsu_moderation_ensure_tables($master_conn);

$stats = fetch(
    "SELECT
      (SELECT COUNT(*) FROM listings) AS total_listings,
      (SELECT COUNT(*) FROM listings WHERE status='active') AS active_listings,
      (SELECT COUNT(*) FROM users WHERE is_active = 0) AS blocked_users,
      (SELECT COUNT(*) FROM user_reports WHERE status IN ('pending','reviewing')) AS open_reports"
);

$listings = fetchAll(
    "SELECT l.listing_id, l.title, l.listing_type, l.status, l.created_at,
            u.user_id, u.full_name, u.email, u.is_active
     FROM listings l
     JOIN users u ON u.user_id = l.owner_id
     ORDER BY l.created_at DESC
     LIMIT 120"
);

$reports = fetchAll(
    "SELECT r.*, 
            reporter.full_name AS reporter_name,
            target.full_name AS target_name,
            target.email AS target_email,
            l.title AS listing_title,
            rv.full_name AS resolver_name
     FROM user_reports r
     JOIN users reporter ON reporter.user_id = r.reporter_id
     JOIN users target ON target.user_id = r.target_user_id
     LEFT JOIN listings l ON l.listing_id = r.listing_id
     LEFT JOIN users rv ON rv.user_id = r.resolved_by
     ORDER BY (r.status='pending') DESC, r.created_at DESC
     LIMIT 200"
);

$recentActions = fetchAll(
    "SELECT a.*, u.full_name AS admin_name
     FROM admin_actions a
     LEFT JOIN users u ON u.user_id = a.admin_id
     ORDER BY a.performed_at DESC
     LIMIT 30"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d4daa">
    <title>Admin Dashboard — WVSU CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
</head>
<body>
<?php include 'navbar.php'; ?>
<main class="container mt-4 mb-5 pb-5 wvsu-pan-soft" data-io-animate>
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-shield-lock me-2 text-primary"></i>Admin moderation center</h1>
            <p class="text-muted mb-0">Monitor listings, review scam reports, and remove or block bad actors quickly.</p>
        </div>
        <?php if (!empty($_GET['ok'])): ?>
            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle p-2">Action saved</span>
        <?php endif; ?>
    </div>

    <div class="row g-3 mb-4 wvsu-stagger">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm market-card"><div class="card-body"><div class="small text-muted">Total listings</div><div class="fs-4 fw-bold"><?= intval($stats['total_listings'] ?? 0) ?></div></div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm market-card"><div class="card-body"><div class="small text-muted">Active listings</div><div class="fs-4 fw-bold text-primary"><?= intval($stats['active_listings'] ?? 0) ?></div></div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm market-card"><div class="card-body"><div class="small text-muted">Open reports</div><div class="fs-4 fw-bold text-warning"><?= intval($stats['open_reports'] ?? 0) ?></div></div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm market-card"><div class="card-body"><div class="small text-muted">Blocked users</div><div class="fs-4 fw-bold text-danger"><?= intval($stats['blocked_users'] ?? 0) ?></div></div></div>
        </div>
    </div>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 fw-bold mb-3">User reports</h2>
            <div class="table-responsive">
                <table class="table align-middle table-sm">
                    <thead>
                    <tr>
                        <th>When</th>
                        <th>Reporter</th>
                        <th>Target</th>
                        <th>Reason</th>
                        <th>Context</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="7" class="text-muted">No reports yet.</td></tr>
                    <?php else: foreach ($reports as $r): ?>
                        <tr>
                            <td class="small text-muted"><?= htmlspecialchars(date('M j, g:i A', strtotime((string) $r['created_at']))) ?></td>
                            <td><div class="fw-semibold small"><?= htmlspecialchars((string) $r['reporter_name']) ?></div></td>
                            <td>
                                <div class="fw-semibold small"><?= htmlspecialchars((string) $r['target_name']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) $r['target_email']) ?></div>
                            </td>
                            <td><span class="badge text-bg-warning-subtle text-warning-emphasis"><?= htmlspecialchars((string) $r['reason_type']) ?></span></td>
                            <td class="small text-muted">
                                <?php if (!empty($r['listing_title'])): ?>
                                    Listing: <?= htmlspecialchars((string) $r['listing_title']) ?><br>
                                <?php endif; ?>
                                <?= htmlspecialchars(mb_strimwidth((string) ($r['details'] ?? ''), 0, 80, '...')) ?>
                            </td>
                            <td>
                                <span class="badge <?= in_array($r['status'], ['pending','reviewing'], true) ? 'text-bg-danger' : 'text-bg-secondary' ?>">
                                    <?= htmlspecialchars((string) $r['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <form method="post" action="process-admin-action.php" class="d-flex gap-1">
                                        <input type="hidden" name="action" value="resolve_report">
                                        <input type="hidden" name="report_id" value="<?= intval($r['report_id']) ?>">
                                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Resolution note">
                                        <button class="btn btn-sm btn-success">Resolve</button>
                                    </form>
                                    <form method="post" action="process-admin-action.php" class="d-flex gap-1">
                                        <input type="hidden" name="action" value="dismiss_report">
                                        <input type="hidden" name="report_id" value="<?= intval($r['report_id']) ?>">
                                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Dismiss note">
                                        <button class="btn btn-sm btn-outline-secondary">Dismiss</button>
                                    </form>
                                    <form method="post" action="process-admin-action.php" class="d-flex gap-1">
                                        <input type="hidden" name="action" value="block_user">
                                        <input type="hidden" name="user_id" value="<?= intval($r['target_user_id']) ?>">
                                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Block note">
                                        <button class="btn btn-sm btn-danger">Block user</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 fw-bold mb-3">All listings</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Listing</th>
                        <th>Owner</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($listings as $l): ?>
                        <tr>
                            <td><?= intval($l['listing_id']) ?></td>
                            <td>
                                <div class="fw-semibold small"><?= htmlspecialchars((string) $l['title']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars(date('M j, Y', strtotime((string) $l['created_at']))) ?></div>
                            </td>
                            <td>
                                <div class="small"><?= htmlspecialchars((string) $l['full_name']) ?></div>
                                <span class="badge <?= intval($l['is_active']) === 1 ? 'text-bg-success' : 'text-bg-danger' ?>"><?= intval($l['is_active']) === 1 ? 'active' : 'blocked' ?></span>
                            </td>
                            <td><span class="badge text-bg-info"><?= htmlspecialchars((string) $l['listing_type']) ?></span></td>
                            <td><span class="badge text-bg-secondary"><?= htmlspecialchars((string) $l['status']) ?></span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <form method="post" action="process-admin-action.php">
                                        <input type="hidden" name="action" value="remove_listing">
                                        <input type="hidden" name="listing_id" value="<?= intval($l['listing_id']) ?>">
                                        <input type="hidden" name="notes" value="Removed from admin dashboard">
                                        <button class="btn btn-sm btn-outline-danger">Remove listing</button>
                                    </form>
                                    <?php if (intval($l['is_active']) === 1): ?>
                                        <form method="post" action="process-admin-action.php">
                                            <input type="hidden" name="action" value="block_user">
                                            <input type="hidden" name="user_id" value="<?= intval($l['user_id']) ?>">
                                            <input type="hidden" name="notes" value="Blocked via listing moderation">
                                            <button class="btn btn-sm btn-danger">Block seller</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="process-admin-action.php">
                                            <input type="hidden" name="action" value="unblock_user">
                                            <input type="hidden" name="user_id" value="<?= intval($l['user_id']) ?>">
                                            <input type="hidden" name="notes" value="Unblocked via listing moderation">
                                            <button class="btn btn-sm btn-success">Unblock</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 fw-bold mb-3">Recent admin actions</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Entity</th><th>Notes</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentActions as $a): ?>
                        <tr>
                            <td class="small text-muted"><?= htmlspecialchars(date('M j, g:i A', strtotime((string) $a['performed_at']))) ?></td>
                            <td class="small"><?= htmlspecialchars((string) ($a['admin_name'] ?? 'Admin')) ?></td>
                            <td><span class="badge text-bg-dark"><?= htmlspecialchars((string) $a['action_type']) ?></span></td>
                            <td class="small"><?= htmlspecialchars((string) $a['entity_type']) ?> #<?= intval($a['target_entity_id']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string) ($a['notes'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

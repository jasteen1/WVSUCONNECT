<?php 
require_once 'db_conn.php';

if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = intval($_SESSION['user_id']);
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: your_listings.php'); exit; }

// Ownership and editor must hit master so replica lag never shows “Forbidden” after a fresh create/sync.
$row = fetch_master(
    'SELECT l.*, p.price, p.stock, s.rate, s.rate_type FROM listings l
     LEFT JOIN products p ON p.listing_id = l.listing_id
     LEFT JOIN services s ON s.listing_id = l.listing_id
     WHERE l.listing_id = ? LIMIT 1',
    [(string) $id]
);

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

$categories = fetchAll('SELECT category_id, name FROM categories ORDER BY name');

$portfolioItems = [];
$pricingItems = [];
if ($listing_type === 'service') {
    $portfolioItems = fetchAll_master(
        'SELECT portfolio_id, media_type, file_path, grid_span FROM service_portfolio_items WHERE listing_id = ? ORDER BY sort_order ASC, portfolio_id ASC',
        [(string) $listing_id]
    );
    $pricingItems = fetchAll_master(
        'SELECT item_name, amount FROM service_pricing_items WHERE listing_id = ? ORDER BY sort_order ASC, price_item_id ASC',
        [(string) $listing_id]
    );
}
$portfolioOrderJson = json_encode(array_map('intval', array_column($portfolioItems, 'portfolio_id')));
$portfolioSpanMap = [];
foreach ($portfolioItems as $p) {
    $portfolioSpanMap[(int) $p['portfolio_id']] = max(1, min(2, (int) ($p['grid_span'] ?? 1)));
}
$portfolioSpansJson = json_encode($portfolioSpanMap);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0d4daa">
  <title>Edit listing — WVSU CONNECT</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php include __DIR__ . '/head_assets.php'; ?>
  <?php if ($listing_type === 'service'): ?>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
  <style>
    #editPfSortable .pf-row { cursor: grab; }
    #editPfSortable .pf-row:active { cursor: grabbing; }
    .pf-thumb { width: 64px; height: 64px; object-fit: cover; border-radius: .45rem; }
    .pf-sortable-ghost { opacity: .5; }
  </style>
  <?php endif; ?>
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container mt-5 mb-5 pb-5 wvsu-pan-soft" data-io-animate>
    <div class="d-flex align-items-center mb-4">
        <a href="your_listings.php" class="btn btn-outline-secondary rounded-circle me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h2 class="fw-bold mb-0">Edit Listing</h2>
    </div>

    <form action="process-edit-listing.php" method="POST" enctype="multipart/form-data"
          data-wvsu-confirm="<?php echo htmlspecialchars(
              ($listing_type === 'service')
                  ? 'Are you sure you want to save these changes to your service listing?'
                  : 'Are you sure you want to save these changes to your product listing?',
              ENT_QUOTES,
              'UTF-8'
          ); ?>">
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
                            <img id="preview" src="<?php echo htmlspecialchars(wvsu_listing_media_href((string) $image_url)); ?>" alt="Current Image" class="rounded-3 shadow-sm mb-3" style="height:120px; object-fit:cover;">

                            <input type="file" name="product_image" id="product_image" class="form-control w-75 mx-auto" accept="image/*">
                            <p class="mt-2 text-muted small px-3">Upload a new image to replace the current one.</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($listing_type === 'service'): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-2"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Portfolio</h5>
                        <p class="small text-muted">Drag to reorder. “Full width” matches the public layout. Remove checks items to delete when you save.</p>

                        <input type="hidden" name="portfolio_existing_order" id="pf_order" value='<?= htmlspecialchars($portfolioOrderJson, ENT_QUOTES, 'UTF-8') ?>'>
                        <input type="hidden" name="portfolio_spans_existing" id="pf_spans_existing" value='<?= htmlspecialchars($portfolioSpansJson, ENT_QUOTES, 'UTF-8') ?>'>
                        <input type="hidden" name="portfolio_spans_new" id="pf_spans_new" value="[]">

                        <ul id="editPfSortable" class="list-unstyled mb-3">
                            <?php foreach ($portfolioItems as $p):
                                $pid = (int) $p['portfolio_id'];
                                $isVid = ($p['media_type'] === 'video');
                                $sp = max(1, min(2, (int) ($p['grid_span'] ?? 1)));
                                ?>
                                <li class="pf-row list-group-item d-flex flex-wrap align-items-center gap-2 mb-2 rounded-3 border" data-pid="<?= $pid ?>">
                                    <span class="portfolio-drag-handle text-muted fs-5 px-1"><i class="bi bi-grip-vertical"></i></span>
                                    <?php if ($isVid): ?>
                                        <div class="pf-thumb bg-dark d-flex align-items-center justify-content-center text-white"><i class="bi bi-play-fill"></i></div>
                                    <?php else: ?>
                                        <img class="pf-thumb" src="<?= htmlspecialchars(wvsu_listing_media_href((string) $p['file_path']), ENT_QUOTES, 'UTF-8') ?>" alt="">
                                    <?php endif; ?>
                                    <span class="small text-truncate flex-grow-1" style="max-width:180px;">#<?= $pid ?> <?= $isVid ? 'Video' : 'Image' ?></span>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input pf-span" type="checkbox" data-pid="<?= $pid ?>" <?= $sp === 2 ? 'checked' : '' ?> id="pfs<?= $pid ?>">
                                        <label class="form-check-label small" for="pfs<?= $pid ?>">Full width</label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" name="portfolio_delete[]" value="<?= $pid ?>" id="pfd<?= $pid ?>">
                                        <label class="form-check-label small text-danger" for="pfd<?= $pid ?>">Delete</label>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (empty($portfolioItems)): ?>
                            <p class="small text-muted fst-italic">No portfolio items yet — add some below.</p>
                        <?php endif; ?>

                        <div class="border-top pt-3 mt-2">
                            <label class="form-label fw-semibold small">Add photos / videos</label>
                            <input type="file" class="form-control" id="pf_new_picker" name="portfolio_new_files[]" multiple accept="image/*,video/mp4,video/webm,video/quicktime">
                            <ul id="pfNewList" class="list-unstyled mt-2 small text-muted"></ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
                                    <label class="form-label fw-semibold">Starting price (₱)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" name="rate" class="form-control" step="0.01" value="<?php echo sprintf('%.2f', $rate); ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Pricing mode</label>
                                    <select name="rate_type" class="form-select">
                                        <option value="per_task" <?php if($rate_type === 'per_task') echo 'selected'; ?>>Per output / project</option>
                                        <option value="per_hour" <?php if($rate_type === 'per_hour') echo 'selected'; ?>>Per hour</option>
                                        <option value="fixed" <?php if($rate_type === 'fixed') echo 'selected'; ?>>Per package / fixed</option>
                                        <option value="negotiable" <?php if($rate_type === 'negotiable') echo 'selected'; ?>>Negotiable</option>
                                    </select>
                                </div>
                                <div class="col-12 border-top pt-3 mt-1">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-semibold mb-0">Price list rows</label>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="addPriceRowBtn">Add row</button>
                                    </div>
                                    <div id="priceRowsEdit" class="d-flex flex-column gap-2">
                                        <?php if (!empty($pricingItems)): ?>
                                            <?php foreach ($pricingItems as $pi): ?>
                                                <div class="row g-2 align-items-center price-row-edit">
                                                    <div class="col-md-7">
                                                        <input type="text" class="form-control" name="price_item_label[]" value="<?= htmlspecialchars((string) $pi['item_name']) ?>" placeholder="Output / package name">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="input-group">
                                                            <span class="input-group-text">₱</span>
                                                            <input type="number" class="form-control" step="0.01" min="0" name="price_item_amount[]" value="<?= $pi['amount'] === null ? '' : htmlspecialchars((string) $pi['amount']) ?>" placeholder="Optional">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1">
                                                        <button type="button" class="btn btn-outline-danger w-100 remove-price-row"><i class="bi bi-x-lg"></i></button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="row g-2 align-items-center price-row-edit">
                                                <div class="col-md-7">
                                                    <input type="text" class="form-control" name="price_item_label[]" placeholder="Output / package name">
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="input-group">
                                                        <span class="input-group-text">₱</span>
                                                        <input type="number" class="form-control" step="0.01" min="0" name="price_item_amount[]" placeholder="Optional">
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-outline-danger w-100 remove-price-row"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <template id="priceRowTplEdit">
                                        <div class="row g-2 align-items-center price-row-edit">
                                            <div class="col-md-7">
                                                <input type="text" class="form-control" name="price_item_label[]" placeholder="Output / package name">
                                            </div>
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <span class="input-group-text">₱</span>
                                                    <input type="number" class="form-control" step="0.01" min="0" name="price_item_amount[]" placeholder="Optional">
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-outline-danger w-100 remove-price-row"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                        </div>
                                    </template>
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
(function () {
  const pi = document.getElementById('product_image');
  if (pi) {
    pi.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function (ev) { document.getElementById('preview').src = ev.target.result; };
      reader.readAsDataURL(file);
    });
  }
})();
</script>
<?php if ($listing_type === 'service'): ?>
<script>
(function () {
  const orderEl = document.getElementById('pf_order');
  const spanExEl = document.getElementById('pf_spans_existing');
  const spanNewEl = document.getElementById('pf_spans_new');
  const list = document.getElementById('editPfSortable');
  const newPicker = document.getElementById('pf_new_picker');
  const newList = document.getElementById('pfNewList');
  let newItems = [];
  let spanMap = {};
  try { spanMap = JSON.parse(spanExEl.value || '{}'); } catch (e) { spanMap = {}; }

  function syncOrder() {
    const ids = [];
    list.querySelectorAll('.pf-row').forEach((row) => ids.push(parseInt(row.dataset.pid, 10)));
    orderEl.value = JSON.stringify(ids);
  }
  function syncSpans() {
    list.querySelectorAll('.pf-span').forEach((cb) => {
      const id = cb.getAttribute('data-pid');
      spanMap[id] = cb.checked ? 2 : 1;
    });
    spanExEl.value = JSON.stringify(spanMap);
  }
  list.querySelectorAll('.pf-span').forEach((cb) => {
    cb.addEventListener('change', () => { syncSpans(); });
  });
  Sortable.create(list, {
    animation: 150,
    handle: '.portfolio-drag-handle',
    ghostClass: 'pf-sortable-ghost',
    onEnd() { syncOrder(); syncSpans(); }
  });
  syncOrder();
  syncSpans();

  newPicker.addEventListener('change', () => {
    newItems = Array.from(newPicker.files || []).map((f) => ({ file: f, span: 1 }));
    newList.innerHTML = '';
    newItems.forEach((x, i) => {
      const li = document.createElement('li');
      li.className = 'mb-1 d-flex align-items-center gap-2';
      li.innerHTML = `<span class="text-truncate">${x.file.name}</span>
        <div class="form-check form-switch ms-auto">
          <input class="form-check-input nspan" type="checkbox" data-ni="${i}" id="ns${i}">
          <label class="form-check-label small" for="ns${i}">Full width</label>
        </div>`;
      li.querySelector('.nspan').addEventListener('change', (e) => {
        newItems[parseInt(e.target.getAttribute('data-ni'), 10)].span = e.target.checked ? 2 : 1;
        spanNewEl.value = JSON.stringify(newItems.map((z) => z.span));
      });
      newList.appendChild(li);
    });
    spanNewEl.value = JSON.stringify(newItems.map(() => 1));
  });
  document.querySelector('form[action="process-edit-listing.php"]')?.addEventListener('submit', () => {
    if (newItems.length) spanNewEl.value = JSON.stringify(newItems.map((z) => z.span));
    syncOrder();
    syncSpans();
  });
})();
</script>
<?php endif; ?>
<?php if ($listing_type === 'service'): ?>
<script>
(function () {
  const wrap = document.getElementById('priceRowsEdit');
  const tpl = document.getElementById('priceRowTplEdit');
  const addBtn = document.getElementById('addPriceRowBtn');
  if (!wrap || !tpl || !addBtn) return;
  function wireRemove(scope) {
    scope.querySelectorAll('.remove-price-row').forEach((btn) => {
      btn.addEventListener('click', () => {
        const row = btn.closest('.price-row-edit');
        if (!row) return;
        if (wrap.querySelectorAll('.price-row-edit').length <= 1) {
          row.querySelectorAll('input').forEach((i) => (i.value = ''));
          return;
        }
        row.remove();
      });
    });
  }
  addBtn.addEventListener('click', () => {
    const node = tpl.content.cloneNode(true);
    wrap.appendChild(node);
    wireRemove(wrap);
  });
  wireRemove(wrap);
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/wvsu-form-confirm.js"></script>
</body>
</html>
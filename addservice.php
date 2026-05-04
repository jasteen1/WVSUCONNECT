<?php
require_once 'db_conn.php';

$serviceCategories = fetchAll_master(
    "SELECT category_id, name
     FROM categories
     WHERE category_type IN ('service', 'both')
     ORDER BY name ASC",
    []
);
$addSvcErr = (string) ($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d4daa">
    <title>Offer a service — WVSU CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include __DIR__ . '/head_assets.php'; ?>
    <style>
        #portfolioSortable .portfolio-row { cursor: grab; transition: transform 0.15s ease; }
        #portfolioSortable .portfolio-row:active { cursor: grabbing; }
        #portfolioSortable .sort-handle { opacity: .5; }
        .portfolio-thumb { width: 72px; height: 72px; object-fit: cover; border-radius: .5rem; }
        .portfolio-sortable-ghost { opacity: .45; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
<div class="container mt-5 mb-5 pb-5 wvsu-pan-soft" data-io-animate>
    <div class="d-flex align-items-center mb-4">
        <a href="services.php" class="btn btn-outline-secondary rounded-circle me-3" aria-label="Back">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h2 class="fw-bold mb-0 text-primary">Offer a service</h2>
            <p class="text-muted mb-0">Portfolio: add photos &amp; clips, reorder, and choose wide or half-width tiles.</p>
        </div>
    </div>

    <?php if ($addSvcErr === 'bad_category'): ?>
        <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4" role="alert">
            Please choose a valid category from the list (same set as on the Services page).
        </div>
    <?php endif; ?>

    <form id="svcForm" action="process-add-service.php" method="POST" enctype="multipart/form-data"
          data-wvsu-confirm="Are you sure you want to publish this service listing?">
        <div class="row g-4">

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-briefcase me-2"></i>Service details</h5>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Title</label>
                            <input type="text" name="service_title" class="form-control rounded-3" placeholder="e.g. Commission portraits, calculus tutoring..." required maxlength="200">
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control rounded-3" rows="6" placeholder="What you deliver, typical turnaround, what to prepare..." required></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <h5 class="fw-bold mb-2 text-info"><i class="bi bi-image me-2"></i>Cover (optional)</h5>
                        <p class="small text-muted">Shown in listings. If you skip it, the first portfolio <strong>image</strong> becomes the cover.</p>
                        <div class="border border-dashed rounded-3 py-4 bg-light text-center mt-auto">
                            <input type="file" name="service_image" class="form-control" accept="image/*">
                            <small class="text-muted d-block mt-2">JPG, PNG, WEBP • max ~20&nbsp;MB typical host limit</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-grid-3x3-gap me-2"></i>Portfolio layout</h5>
                                <p class="text-muted small mb-0">Upload multiple files, drag to reorder (left-to-top = first). Toggle <strong>Full width</strong> for a hero-style row.</p>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" id="btnPickPortfolio">
                                <i class="bi bi-cloud-arrow-up"></i> Add photos / videos
                            </button>
                        </div>

                        <input type="file" id="portfolioPicker" name="portfolio_files[]" multiple
                               accept="image/*,video/mp4,video/webm,video/quicktime" class="d-none">

                        <p class="small text-muted mb-2"><i class="bi bi-info-circle"></i> Images: JPG / PNG / WEBP / GIF. Videos: MP4 / WebM / MOV (short reels work best).</p>

                        <ul id="portfolioSortable" class="list-unstyled mb-0"></ul>
                        <div id="portfolioEmpty" class="text-muted small border border-dashed rounded-3 p-4 text-center bg-light">No portfolio items yet — buyers love seeing samples.</div>

                        <input type="hidden" name="portfolio_spans" id="portfolioSpansHidden" value="[]">
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-success"><i class="bi bi-mortarboard me-2"></i>Category</h5>
                        <label class="form-label fw-semibold" for="addservice_category_id">Marketplace category</label>
                        <select name="category_id" id="addservice_category_id" class="form-select rounded-3" required>
                            <option value="" selected disabled>Choose a category…</option>
                            <?php foreach ($serviceCategories as $sc): ?>
                                <?php
                                $scid = (int) ($sc['category_id'] ?? 0);
                                if ($scid <= 0) {
                                    continue;
                                }
                                ?>
                                <option value="<?= $scid ?>"><?= htmlspecialchars((string) ($sc['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="mt-3 small text-muted mb-0">Same categories as the <a href="services.php">Services</a> page filters so buyers can find you consistently.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-warning"><i class="bi bi-cash-coin me-2"></i>Freelancer pricing</h5>
                        <label class="form-label fw-semibold">Pricing mode</label>
                        <select name="unit" id="pricingMode" class="form-select rounded-3 mb-3">
                            <option value="per_output" selected>Per output / project</option>
                            <option value="hour">Per hour</option>
                            <option value="session">Per package / session</option>
                            <option value="negotiable">Negotiable quote</option>
                        </select>
                        <label class="form-label fw-semibold">Starting price (₱)</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="rate" id="baseRateInput" class="form-control rounded-end" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <p class="small text-muted mb-0">Tip: if you provide a price list below, starting price can auto-use the cheapest item.</p>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Price list (freelancer packages)</h6>
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" id="btnAddPriceRow">
                                <i class="bi bi-plus-circle me-1"></i>Add row
                            </button>
                        </div>
                        <p class="small text-muted">Examples: “Logo only”, “Logo + revisions”, “Thesis formatting package”. Amount can be blank for negotiable items.</p>
                        <div id="priceRows" class="d-flex flex-column gap-2"></div>
                        <template id="priceRowTemplate">
                            <div class="row g-2 align-items-center price-row">
                                <div class="col-md-7">
                                    <input type="text" name="price_item_label[]" class="form-control" placeholder="Output / package name">
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" min="0" step="0.01" name="price_item_amount[]" class="form-control" placeholder="Optional">
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger w-100 remove-price-row" aria-label="Remove row"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                        </template>
                        <button type="submit" name="submit" class="btn btn-primary w-100 btn-lg fw-bold shadow-sm py-3 mt-4 rounded-3">
                            Publish service
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/wvsu-form-confirm.js"></script>
<script>
(function () {
    const picker = document.getElementById('portfolioPicker');
    const list = document.getElementById('portfolioSortable');
    const empty = document.getElementById('portfolioEmpty');
    const spansHidden = document.getElementById('portfolioSpansHidden');
    const form = document.getElementById('svcForm');
    document.getElementById('btnPickPortfolio').addEventListener('click', () => picker.click());

    let nextId = 1;
    /** @type {{id: number, file: File, span: number, url: string}[]} */
    let items = [];

    function render() {
        list.innerHTML = '';
        empty.style.display = items.length ? 'none' : 'block';
        items.forEach((entry) => {
            const li = document.createElement('li');
            li.className = 'portfolio-row list-group-item d-flex align-items-center gap-3 mb-2 rounded-3 border shadow-sm';
            li.dataset.itemId = String(entry.id);
            const isVid = entry.file.type.indexOf('video') === 0 || /\.(mp4|webm|mov)$/i.test(entry.file.name);
            li.innerHTML = `
                <span class="sort-handle text-muted fs-4 px-1"><i class="bi bi-grip-vertical"></i></span>
                ${isVid
                    ? `<div class="portfolio-thumb bg-dark d-flex align-items-center justify-content-center text-white"><i class="bi bi-play-circle fs-2"></i></div>`
                    : `<img class="portfolio-thumb" src="${entry.url}" alt="">`}
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate small">${entry.file.name}</div>
                    <div class="text-muted text-truncate" style="font-size:.75rem;">${isVid ? 'Video' : 'Image'} • ${(entry.file.size / (1024*1024)).toFixed(2)} MB</div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" data-span-toggle ${entry.span === 2 ? 'checked' : ''} id="sp${entry.id}">
                    <label class="form-check-label small" for="sp${entry.id}">Full width</label>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-remove><i class="bi bi-x-lg"></i></button>
            `;
            li.querySelector('[data-span-toggle]').addEventListener('change', (e) => {
                entry.span = e.target.checked ? 2 : 1;
                syncHidden();
            });
            li.querySelector('[data-remove]').addEventListener('click', () => {
                URL.revokeObjectURL(entry.url);
                items = items.filter((x) => x.id !== entry.id);
                render();
                applyFilesToInput();
            });
            list.appendChild(li);
        });
        syncHidden();
    }

    function syncHidden() {
        spansHidden.value = JSON.stringify(items.map((x) => x.span));
    }

    function applyFilesToInput() {
        const dt = new DataTransfer();
        items.forEach((x) => dt.items.add(x.file));
        picker.files = dt.files;
    }

    picker.addEventListener('change', () => {
        const incoming = Array.from(picker.files || []);
        incoming.forEach((file) => {
            const url = URL.createObjectURL(file);
            items.push({ id: nextId++, file, span: 1, url });
        });
        picker.value = '';
        render();
        applyFilesToInput();
    });

    Sortable.create(list, {
        animation: 160,
        handle: '.sort-handle',
        ghostClass: 'portfolio-sortable-ghost',
        onEnd() {
            const ordered = [];
            list.querySelectorAll('.portfolio-row').forEach((row) => {
                const id = parseInt(row.dataset.itemId, 10);
                const hit = items.find((x) => x.id === id);
                if (hit) ordered.push(hit);
            });
            if (ordered.length === items.length) {
                items = ordered;
                syncHidden();
                applyFilesToInput();
            }
        }
    });

    form.addEventListener('submit', () => {
        applyFilesToInput();
        syncHidden();
    });
})();

(function () {
    const rowsWrap = document.getElementById('priceRows');
    const tpl = document.getElementById('priceRowTemplate');
    const addBtn = document.getElementById('btnAddPriceRow');
    const mode = document.getElementById('pricingMode');
    const baseRate = document.getElementById('baseRateInput');

    function addRow(name, amount) {
        const clone = tpl.content.cloneNode(true);
        const row = clone.querySelector('.price-row');
        const n = clone.querySelector('input[name="price_item_label[]"]');
        const a = clone.querySelector('input[name="price_item_amount[]"]');
        n.value = name || '';
        a.value = amount || '';
        clone.querySelector('.remove-price-row').addEventListener('click', () => {
            row.remove();
        });
        rowsWrap.appendChild(clone);
    }

    function applyModeState() {
        const m = mode.value;
        if (m === 'hour' || m === 'session') {
            baseRate.required = true;
            baseRate.placeholder = 'e.g. 350.00';
        } else if (m === 'per_output') {
            baseRate.required = false;
            baseRate.placeholder = 'Optional if you fill package rows';
        } else {
            baseRate.required = false;
            baseRate.placeholder = 'Optional';
        }
    }

    addBtn.addEventListener('click', () => addRow('', ''));
    mode.addEventListener('change', applyModeState);
    addRow('', '');
    addRow('', '');
    applyModeState();
})();
</script>
</body>
</html>

<?php
require_once 'db_conn.php';
require_once __DIR__ . '/service_pricing.inc.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$uid = intval($_SESSION['user_id']);
$listing_id = intval($_POST['listing_id'] ?? 0);
if ($listing_id <= 0) { header('Location: your_listings.php'); exit; }

$owner = fetch_master('SELECT owner_id, listing_type FROM listings WHERE listing_id = ?', [(string) $listing_id]);
if (!$owner || intval($owner['owner_id']) !== $uid) { header('HTTP/1.1 403 Forbidden'); echo 'Forbidden'; exit; }

// gather inputs
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$category_id = intval($_POST['category_id'] ?? 0);
$status = $_POST['status'] ?? 'active';

// handle image upload if provided; otherwise preserve existing image URL
$existing = $_POST['existing_image_url'] ?? '';
$image_url = $existing;
if (!empty($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['product_image']['tmp_name'];
    $orig = basename($_FILES['product_image']['name']);
    $ext = pathinfo($orig, PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) {
        // ignore invalid type and keep existing
        $image_url = $existing;
    } else {
        $subdir = ($owner['listing_type'] === 'product') ? 'uploads/products' : 'uploads/services';
        if (!is_dir(__DIR__ . '/' . $subdir)) mkdir(__DIR__ . '/' . $subdir, 0755, true);
        $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetPath = __DIR__ . '/' . $subdir . '/' . $newName;
        if (move_uploaded_file($tmp, $targetPath)) {
            $image_url = $subdir . '/' . $newName;
        } else {
            $image_url = $existing;
        }
    }
}

// update listings row
$stmt = $master_conn->prepare("UPDATE listings SET title = ?, description = ?, category_id = ?, image_url = ?, status = ? WHERE listing_id = ?");
if ($stmt) {
    $stmt->bind_param('ssissi', $title, $description, $category_id, $image_url, $status, $listing_id);
    $stmt->execute();
    $stmt->close();
} else {
    // fallback: minimal safe update
    $qtitle = $master_conn->real_escape_string($title);
    $qdesc = $master_conn->real_escape_string($description);
    $qimage = $master_conn->real_escape_string($image_url);
    $master_conn->query("UPDATE listings SET title='".$qtitle."', description='".$qdesc."', category_id=".intval($category_id).", image_url='".$qimage."', status='".$master_conn->real_escape_string($status)."' WHERE listing_id=".intval($listing_id));
}

if ($owner['listing_type'] === 'product') {
    $price = wvsu_parse_money_string((string) ($_POST['price'] ?? ''));
    $stock = intval($_POST['stock'] ?? 0);
    $s = $master_conn->prepare("UPDATE products SET price = ?, stock = ? WHERE listing_id = ?");
    $s->bind_param('dii', $price, $stock, $listing_id);
    $s->execute();
    // update listing status based on stock
    $new_status = $stock <= 0 ? 'sold_out' : $status;
    $u = $master_conn->prepare("UPDATE listings SET status = ? WHERE listing_id = ?");
    $u->bind_param('si', $new_status, $listing_id);
    $u->execute();
} else {
    $rate = wvsu_parse_money_string((string) ($_POST['rate'] ?? ''));
    $rate_type = $_POST['rate_type'] ?? 'fixed';
    $s = $master_conn->prepare("UPDATE services SET rate = ?, rate_type = ? WHERE listing_id = ?");
    $s->bind_param('dsi', $rate, $rate_type, $listing_id);
    $s->execute();
}

if ($owner['listing_type'] === 'service') {
    require_once __DIR__ . '/service_portfolio.inc.php';
    wvsu_service_pricing_ensure_table($master_conn);
    wvsu_service_portfolio_ensure_table($master_conn);

    if (!empty($_POST['portfolio_delete']) && is_array($_POST['portfolio_delete'])) {
        foreach ($_POST['portfolio_delete'] as $delRaw) {
            $pid = intval($delRaw);
            if ($pid <= 0) {
                continue;
            }
            $res = $master_conn->query(
                'SELECT file_path FROM service_portfolio_items WHERE portfolio_id=' . intval($pid)
                    . ' AND listing_id=' . intval($listing_id) . ' LIMIT 1'
            );
            $prow = $res ? $res->fetch_assoc() : null;
            if ($prow && !empty($prow['file_path'])) {
                $abs = __DIR__ . '/' . $prow['file_path'];
                if (is_file($abs)) {
                    @unlink($abs);
                }
            }
            $master_conn->query(
                'DELETE FROM service_portfolio_items WHERE portfolio_id=' . intval($pid)
                    . ' AND listing_id=' . intval($listing_id)
            );
        }
    }

    $order = json_decode($_POST['portfolio_existing_order'] ?? '[]', true);
    $spansMap = json_decode($_POST['portfolio_spans_existing'] ?? '{}', true);
    if (is_array($order)) {
        $uSt = $master_conn->prepare(
            'UPDATE service_portfolio_items SET sort_order = ?, grid_span = ? WHERE portfolio_id = ? AND listing_id = ?'
        );
        foreach ($order as $pos => $pidRaw) {
            $pid = intval($pidRaw);
            if ($pid <= 0) {
                continue;
            }
            $sp = 1;
            if (isset($spansMap[$pid])) {
                $sp = max(1, min(2, (int) $spansMap[$pid]));
            } elseif (isset($spansMap[(string) $pid])) {
                $sp = max(1, min(2, (int) $spansMap[(string) $pid]));
            }
            if ($uSt) {
                $uSt->bind_param('iiii', $pos, $sp, $pid, $listing_id);
                $uSt->execute();
            }
        }
        if ($uSt) {
            $uSt->close();
        }
    }

    $newFiles = $_FILES['portfolio_new_files'] ?? null;
    $newSpanArr = json_decode($_POST['portfolio_spans_new'] ?? '[]', true);
    if (!is_array($newSpanArr)) {
        $newSpanArr = [];
    }
    $newSpanArr = array_values(array_map(function ($v) {
        return max(1, min(2, (int) $v));
    }, $newSpanArr));

    $maxRow = fetch_master('SELECT COALESCE(MAX(sort_order), -1) AS m FROM service_portfolio_items WHERE listing_id = ?', [(string) $listing_id]);
    $nextSort = intval($maxRow['m'] ?? -1) + 1;

    if (
        is_array($newFiles)
        && !empty($newFiles['name'])
        && is_array($newFiles['name'])
    ) {
        wvsu_save_portfolio_uploads($master_conn, $listing_id, $newFiles, $newSpanArr, $nextSort);
    }

    $priceItems = wvsu_collect_price_items(
        isset($_POST['price_item_label']) && is_array($_POST['price_item_label']) ? $_POST['price_item_label'] : [],
        isset($_POST['price_item_amount']) && is_array($_POST['price_item_amount']) ? $_POST['price_item_amount'] : []
    );
    wvsu_save_price_items($master_conn, $listing_id, $priceItems);

    $minP = wvsu_min_price_from_items($priceItems);
    if (($rate_type === 'negotiable') && $rate <= 0 && $minP === null) {
        $rate = 0;
    } elseif ($rate <= 0 && $minP !== null) {
        $rate = $minP;
    }
    $s2 = $master_conn->prepare("UPDATE services SET rate = ?, rate_type = ? WHERE listing_id = ?");
    if ($s2) {
        $s2->bind_param('dsi', $rate, $rate_type, $listing_id);
        $s2->execute();
        $s2->close();
    }
}

header('Location: your_listings.php');
exit;
?>

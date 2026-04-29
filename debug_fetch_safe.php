<?php
// Safe debug script that does NOT include db_conn.php (to avoid side effects)
$mysqli = new mysqli('localhost', 'root', '', 'wvsudb', 3306);
if ($mysqli->connect_error) {
    echo json_encode(['error' => 'connect', 'msg' => $mysqli->connect_error]);
    exit;
}
$id = 7;
$stmt = $mysqli->prepare("SELECT l.*, p.price, p.stock, s.rate, s.rate_type FROM listings l LEFT JOIN products p ON p.listing_id = l.listing_id LEFT JOIN services s ON s.listing_id = l.listing_id WHERE l.listing_id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

$cats = [];
$r2 = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
while ($c = $r2->fetch_assoc()) $cats[] = $c;

echo json_encode(['listing'=>$row, 'categories'=>$cats], JSON_PRETTY_PRINT);
?>
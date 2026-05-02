<?php
// Safe debug script that does NOT include db_conn.php (to avoid side effects)
$cfg = [];
if (is_file(__DIR__ . '/db_config.local.php')) {
    $inc = include __DIR__ . '/db_config.local.php';
    $cfg = is_array($inc) ? $inc : [];
}
$host = $cfg['host'] ?? getenv('WVSU_DB_HOST') ?: '127.0.0.1';
$user = $cfg['user'] ?? getenv('WVSU_DB_USER') ?: 'root';
$pass = $cfg['password'] ?? getenv('WVSU_DB_PASSWORD') ?: '';
$db = $cfg['database'] ?? getenv('WVSU_DB_NAME') ?: 'wvsudb';
$port = (int) ($cfg['master_port'] ?? getenv('WVSU_DB_MASTER_PORT') ?: 3306);
mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = new mysqli($host, $user, $pass, $db, $port);
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
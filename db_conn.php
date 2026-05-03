<?php
// db_conn.php
// Optional overrides: db_config.local.php — see db_config.local.example.php

// Single-server setups (typical XAMPP): omit WVSU_DB_SLAVE_PORT so reads use the same port as writes.
// Use a replica only when MySQL replication is configured and slave_port differs.
$defaultMasterPort = (int) (getenv('WVSU_DB_MASTER_PORT') ?: 3306);
$envSlaveRaw = getenv('WVSU_DB_SLAVE_PORT');
$defaultSlavePort = ($envSlaveRaw !== false && $envSlaveRaw !== '')
    ? (int) $envSlaveRaw
    : $defaultMasterPort;
$config = [
    'host' => getenv('WVSU_DB_HOST') ?: '127.0.0.1',
    'user' => getenv('WVSU_DB_USER') ?: 'root',
    'password' => getenv('WVSU_DB_PASSWORD') ?: '',
    'database' => getenv('WVSU_DB_NAME') ?: 'wvsudb',
    'master_port' => $defaultMasterPort,
    'slave_port' => $defaultSlavePort,
];

$localFile = __DIR__ . '/db_config.local.php';
if (is_file($localFile)) {
    $local = include $localFile;
    if (is_array($local)) {
        $config = array_merge($config, $local);
        $config['master_port'] = (int) $config['master_port'];
        $config['slave_port'] = (int) $config['slave_port'];
    }
}

mysqli_report(MYSQLI_REPORT_OFF);
session_start();

// ==========================================
// 1. ESTABLISH CONNECTIONS
// ==========================================

// MASTER DB (Port 3306) - STRICTLY FOR WRITING (Insert, Update, Delete)
$master_conn = new mysqli(
    $config['host'],
    $config['user'],
    $config['password'],
    $config['database'],
    $config['master_port']
);
if ($master_conn->connect_error) {
    http_response_code(500);
    exit(
        '<!DOCTYPE html><html><meta charset="utf-8"><title>Database setup</title><body style="font-family:sans-serif;max-width:640px;margin:2rem auto;line-height:1.5">' .
        '<h1>Cannot connect to MySQL</h1><p>WVSU Connect could not connect to the database (<code>'
        . htmlspecialchars($config['database'], ENT_QUOTES, 'UTF-8')
        . '</code>).</p>'
        . '<p><strong>Details:</strong> ' . htmlspecialchars($master_conn->connect_error, ENT_QUOTES, 'UTF-8')
        . '</p><p>If you use a password for MySQL root, create '
        . '<code>db_config.local.php</code> in the project root (copy '
        . '<code>db_config.local.example.php</code>) and set '
        . '<code>password</code> to match phpMyAdmin. Start MySQL in XAMPP and import '
        . '<code>wvsudb.sql</code> if the database is missing.</p></body></html>'
    );
}

// SLAVE DB (Port 3307) - STRICTLY FOR READING (Select)
// We handle the fallback manually if port is down
$slave_conn = @new mysqli(
    $config['host'],
    $config['user'],
    $config['password'],
    $config['database'],
    $config['slave_port']
);
if ($slave_conn->connect_error) {
    $slave_conn = $master_conn; 
}

// ==========================================
// 3. ENSURE HELPER TABLES EXIST (idempotent)
// ==========================================
// Some pages expect a lightweight mapping table between conversations and listings.
$create_conv_listings = "CREATE TABLE IF NOT EXISTS conversation_listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    listing_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(conversation_id),
    INDEX(listing_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!$master_conn->query($create_conv_listings)) {
    die("Failed creating helper table conversation_listings: " . $master_conn->error);
}

// Drop legacy unique constraint preventing multiple conversations between same participants.
// Some DB dumps create `uq_conversation` on (participant_a, participant_b); remove if exists.
$res = $master_conn->query("ALTER TABLE conversations DROP INDEX uq_conversation");
@file_put_contents(__DIR__ . '/db_conn_debug.log', "drop_uq_conversation_result=" . ($res ? 'ok' : 'fail') . ",errno=" . intval($master_conn->errno) . ",err=" . $master_conn->error . "\n", FILE_APPEND);

// Ensure `conversation_id` uses AUTO_INCREMENT so inserts without explicit id return an insert_id.
// Some older dumps create the table without AUTO_INCREMENT which prevents proper inserts.
$res2 = $master_conn->query("ALTER TABLE conversations MODIFY conversation_id INT UNSIGNED NOT NULL AUTO_INCREMENT");
@file_put_contents(__DIR__ . '/db_conn_debug.log', "alter_conv_autoinc_result=" . ($res2 ? 'ok' : 'fail') . ",errno=" . intval($master_conn->errno) . ",err=" . $master_conn->error . "\n", FILE_APPEND);

// Add-product / add-service need a real insert_id. Some imported dumps define listing_id / product_id without AUTO_INCREMENT.
$resLai = @$master_conn->query('ALTER TABLE listings MODIFY listing_id INT UNSIGNED NOT NULL AUTO_INCREMENT');
@file_put_contents(__DIR__ . '/db_conn_debug.log', 'alter_listings_autoinc=' . ($resLai ? 'ok' : 'fail') . ',errno=' . intval($master_conn->errno) . ',err=' . $master_conn->error . "\n", FILE_APPEND);
$resPai = @$master_conn->query('ALTER TABLE products MODIFY product_id INT UNSIGNED NOT NULL AUTO_INCREMENT');
@file_put_contents(__DIR__ . '/db_conn_debug.log', 'alter_products_autoinc=' . ($resPai ? 'ok' : 'fail') . ',errno=' . intval($master_conn->errno) . ',err=' . $master_conn->error . "\n", FILE_APPEND);
$resSai = @$master_conn->query('ALTER TABLE services MODIFY service_id INT UNSIGNED NOT NULL AUTO_INCREMENT');
@file_put_contents(__DIR__ . '/db_conn_debug.log', 'alter_services_autoinc=' . ($resSai ? 'ok' : 'fail') . ',errno=' . intval($master_conn->errno) . ',err=' . $master_conn->error . "\n", FILE_APPEND);

// Service portfolio media (photos / videos); safe to omit FK if migrations differ.
@include_once __DIR__ . '/service_portfolio.inc.php';
if (function_exists('wvsu_service_portfolio_ensure_table')) {
    wvsu_service_portfolio_ensure_table($master_conn);
}

// Optional freelancer pricing list items for services.
@include_once __DIR__ . '/service_pricing.inc.php';
if (function_exists('wvsu_service_pricing_ensure_table')) {
    wvsu_service_pricing_ensure_table($master_conn);
}

@include_once __DIR__ . '/profiles_reviews.inc.php';
if (function_exists('wvsu_user_profiles_ensure_columns')) {
    wvsu_user_profiles_ensure_columns($master_conn);
}
if (function_exists('wvsu_user_college_year_ensure_columns')) {
    wvsu_user_college_year_ensure_columns($master_conn);
}
if (function_exists('wvsu_user_reviews_ensure_table')) {
    wvsu_user_reviews_ensure_table($master_conn);
}
if (function_exists('wvsu_user_reviews_ensure_photo_and_indexes')) {
    wvsu_user_reviews_ensure_photo_and_indexes($master_conn);
}
if (function_exists('wvsu_user_reviews_ensure_seller_reply_columns')) {
    wvsu_user_reviews_ensure_seller_reply_columns($master_conn);
}

@include_once __DIR__ . '/messaging_schema.inc.php';
if (function_exists('wvsu_messaging_ensure_schema')) {
    wvsu_messaging_ensure_schema($master_conn);
}

// ==========================================
// 2. HELPER FUNCTIONS (With Aggressive Debugging)
// ==========================================

/**
 * mysqli_stmt::bind_param() requires VARIABLE REFERENCES — unpacking an array literal
 * (e.g. bind_param($t, ...$params)) binds wrong/empty values on many PHP setups and breaks logins.
 */
function wvsu_mysqli_bind_params(mysqli_stmt $stmt, array $params): void
{
    if ($params === []) {
        return;
    }
    $types = str_repeat('s', count($params));
    $refs = [$types];
    foreach (array_keys($params) as $i) {
        $refs[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

/**
 * FETCH SINGLE ROW (Reads from Slave)
 */
function fetch($sql, $params = []) {
    global $slave_conn;
    $stmt = $slave_conn->prepare($sql);
    if (!$stmt) die("Slave Prepare Error: " . $slave_conn->error . " | SQL: " . $sql);
    
    if (!empty($params)) {
        wvsu_mysqli_bind_params($stmt, $params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        die('Database driver error: mysqli_stmt::get_result() failed. Enable the mysqlnd driver for PHP.');
    }
    return $result->fetch_assoc();
}

/**
 * FETCH MULTIPLE ROWS (Reads from Slave)
 */
function fetchAll($sql, $params = []) {
    global $slave_conn;
    $stmt = $slave_conn->prepare($sql);
    if (!$stmt) die("Slave Prepare Error: " . $slave_conn->error . " | SQL: " . $sql);
    
    if (!empty($params)) {
        wvsu_mysqli_bind_params($stmt, $params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        die('Database driver error: mysqli_stmt::get_result() failed. Enable the mysqlnd driver for PHP.');
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * FETCH SINGLE ROW (Reads from Master — same data your writes committed to.)
 * Use for pages that must show updates immediately while a separate read replica exists.
 */
function fetch_master($sql, $params = []) {
    global $master_conn;
    $stmt = $master_conn->prepare($sql);
    if (!$stmt) {
        die("Master Prepare Error: " . $master_conn->error . " | SQL: " . $sql);
    }
    if (!empty($params)) {
        wvsu_mysqli_bind_params($stmt, $params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        die('Database driver error: mysqli_stmt::get_result() failed. Enable the mysqlnd driver for PHP.');
    }
    return $result->fetch_assoc();
}

/**
 * FETCH MULTIPLE ROWS (Reads from Master)
 */
function fetchAll_master($sql, $params = []) {
    global $master_conn;
    $stmt = $master_conn->prepare($sql);
    if (!$stmt) {
        die("Master Prepare Error: " . $master_conn->error . " | SQL: " . $sql);
    }
    if (!empty($params)) {
        wvsu_mysqli_bind_params($stmt, $params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        die('Database driver error: mysqli_stmt::get_result() failed. Enable the mysqlnd driver for PHP.');
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * INSERT DATA (Writes to Master)
 */
function insert($sql, $params = []) {
    global $master_conn;
    $stmt = $master_conn->prepare($sql);
    if (!$stmt) die("Master Prepare Error: " . $master_conn->error . " | SQL: " . $sql);
    
    if (!empty($params)) {
        wvsu_mysqli_bind_params($stmt, $params);
    }
    
    if (!$stmt->execute()) die("Master Execute Error: " . $stmt->error);
    return $master_conn->insert_id;
}
?>
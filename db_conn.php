<?php
// db_conn.php
session_start();

// ==========================================
// 1. ESTABLISH CONNECTIONS
// ==========================================

// MASTER DB (Port 3306) - STRICTLY FOR WRITING (Insert, Update, Delete)
$master_conn = new mysqli('localhost', 'root', '', 'wvsudb', 3306);
if ($master_conn->connect_error) {
    die("MASTER Connection Failed: " . $master_conn->connect_error);
}

// SLAVE DB (Port 3307) - STRICTLY FOR READING (Select)
// We use '@' to suppress standard warnings, and handle the fallback manually
$slave_conn = @new mysqli('localhost', 'root', '', 'wvsudb', 3307);
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

// ==========================================
// 2. HELPER FUNCTIONS (With Aggressive Debugging)
// ==========================================

/**
 * FETCH SINGLE ROW (Reads from Slave)
 */
function fetch($sql, $params = []) {
    global $slave_conn;
    $stmt = $slave_conn->prepare($sql);
    if (!$stmt) die("Slave Prepare Error: " . $slave_conn->error . " | SQL: " . $sql);
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // Safely treat all params as strings
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
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
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
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
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) die("Master Execute Error: " . $stmt->error);
    return $master_conn->insert_id;
}
?>
<?php
/**
 * Copy this file to db_config.local.php and set your real MySQL password.
 * db_config.local.php is gitignored — do not commit it.
 *
 * XAMPP: if MySQL rejects root with empty password, set password here
 * to match whatever you use in phpMyAdmin (often the password you chose for root).
 *
 * Usage: rename or duplicate:
 *   cp db_config.local.example.php db_config.local.php
 */

return [
    'host' => '127.0.0.1',
    'user' => 'root',
    'password' => '',
    'database' => 'wvsudb',
    'master_port' => 3306,
    /** Keep identical to master_port unless you really use a MySQL replica on another port */
    'slave_port' => 3306,
];

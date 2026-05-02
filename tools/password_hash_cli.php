#!/usr/bin/env php
<?php

/**
 * Prints a bcrypt/argon-compatible hash for use in UPDATE users.password in SQL.
 *
 * Usage: php tools/password_hash_cli.php YourPasswordHere
 */
declare(strict_types=1);

$plain = $argv[1] ?? '';
if ($plain === '') {
    fwrite(STDERR, "Usage: php tools/password_hash_cli.php <password>\n");
    exit(1);
}

echo password_hash($plain, PASSWORD_DEFAULT), "\n";

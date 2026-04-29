<?php
// process-login.php
require_once 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Uses the Slave connection automatically via our fetch() helper
    $sql = "SELECT user_id, full_name, password FROM users WHERE email = ? LIMIT 1";
    $user = fetch($sql, [$email]);

    if ($user) {
        // Verify the password hash
        if (password_verify($password, $user['password'])) {
            // SUCCESS
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            
            header("Location: index.php");
            exit;
        } else {
            die("Error: Incorrect password. <a href='login.php'>Try again</a>");
        }
    } else {
        die("Error: Email not found. <a href='login.php'>Try again</a>");
    }
} else {
    header("Location: login.php");
    exit;
}
?>
<?php
// process-register.php
require_once 'db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Simple validation: passwords match
    if ($password !== $confirm) {
        header("Location: register.php?error=Passwords do not match.");
        exit;
    }

    // 1. Check if email exists (Reads from Slave)
    $check_sql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
    $existing = fetch($check_sql, [$email]);
    
    if ($existing) {
        die("Email is already registered. <a href='login.php'>Login here</a>");
    }

    // 2. Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

   // 3. Insert new user (Writes to Master)
    // ADDED: role_id column to the query
    $insert_sql = "INSERT INTO users (full_name, email, password, role_id) VALUES (?, ?, ?, ?)";
    
    $new_user_id = insert($insert_sql, [$full_name, $email, $password_hash, 3]);

    if ($new_user_id) {
        // Redirect back to login with a success message
        header("Location: login.php?success=Account created successfully! Please login.");
        exit;
    } else {
        header("Location: register.php?error=Registration failed.");
        exit;
    }
}
?>
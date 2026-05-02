<?php

declare(strict_types=1);

// process-register.php
require_once __DIR__ . '/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim((string) $_POST['full_name']) : '';
    $email = isset($_POST['email']) ? strtolower(trim((string) $_POST['email'])) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $confirm = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';

    // Simple validation: passwords match
    if ($password !== $confirm) {
        header("Location: register.php?error=Passwords do not match.");
        exit;
    }

    // Same DB as INSERT — avoids false “available” email when slave lags or is another instance.
    $check_sql = 'SELECT user_id FROM users WHERE LOWER(TRIM(email)) = ? LIMIT 1';
    $existing = fetch_master($check_sql, [$email]);
    
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
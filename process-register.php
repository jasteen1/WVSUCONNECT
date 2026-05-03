<?php

declare(strict_types=1);

// process-register.php
require_once __DIR__ . '/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim((string) $_POST['full_name']) : '';
    $email = isset($_POST['email']) ? strtolower(trim((string) $_POST['email'])) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $confirm = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';
    $college = wvsu_sanitize_college_code($_POST['college'] ?? '');
    $year_level = wvsu_sanitize_year_level($_POST['year_level'] ?? null);
    $course = wvsu_sanitize_course_text($_POST['course'] ?? '');

    if ($full_name === '' || $email === '') {
        header('Location: register.php?error=' . rawurlencode('Please fill in your name and campus email.'));
        exit;
    }
    if ($college === null) {
        header('Location: register.php?error=' . rawurlencode('Please select your college or unit.'));
        exit;
    }
    if ($year_level === null) {
        header('Location: register.php?error=' . rawurlencode('Please select your year level (1st–4th year).'));
        exit;
    }

    // Simple validation: passwords match
    if ($password !== $confirm) {
        header("Location: register.php?error=Passwords do not match.");
        exit;
    }

    // Same DB as INSERT — avoids false “available” email when slave lags or is another instance.
    $check_sql = 'SELECT user_id FROM users WHERE LOWER(TRIM(email)) = ? LIMIT 1';
    $existing = fetch_master($check_sql, [$email]);
    
    if ($existing) {
        header(
            'Location: login.php?' . http_build_query([
                'error' => 'email_registered',
                'email' => $email,
            ])
        );
        exit;
    }

    // 2. Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

   // 3. Insert new user (Writes to Master)
    // ADDED: role_id column to the query
    $insert_sql = 'INSERT INTO users (full_name, email, password, role_id, college, year_level, course) VALUES (?, ?, ?, ?, ?, ?, ?)';

    $new_user_id = insert($insert_sql, [
        $full_name,
        $email,
        $password_hash,
        '3',
        $college,
        (string) $year_level,
        $course,
    ]);

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
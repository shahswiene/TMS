<?php
// process_login.php

// Include configuration file and utilities
require_once 'config.php';
require_once 'auth_middleware.php';

// Secure database connection using PDO
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
} catch (Exception $e) {
    error_log($e->getMessage());
    $error_message = 'Database connection error.';
    return;
}

// Retrieve and sanitize input
$email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    $error_message = 'Please enter both email and password.';
    return;
}

// Prepare statement to prevent SQL injection
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
$stmt->bindParam(':email', $email, PDO::PARAM_STR);

$stmt->execute();
$user = $stmt->fetch();

if ($user) {
    // Check user status and handle accordingly
    switch ($user['is_active']) {
        case 'inactive':
            $error_message = 'Account is inactive. Please contact administrator.';
            return;

        case 'pending':
        case 'active':
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Get user's IP address
                $ip_address = $_SERVER['REMOTE_ADDR'];
                if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
                    $forwarded_for = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                    $ip_address = array_pop($forwarded_for);
                }

                // Reset failed login attempts, update last login info, and set user online
                $resetStmt = $pdo->prepare('UPDATE users SET failed_login_attempts = 0, last_login = NOW(), last_ip_address = :ip_address, is_online = 1 WHERE user_id = :user_id');
                $resetStmt->execute([
                    ':ip_address' => $ip_address,
                    ':user_id'    => $user['user_id'],
                ]);

                // Start the session if not already started
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }

                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Store user data in session
                $_SESSION['user_id']  = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['ip_address'] = $ip_address;

                // Redirect based on user status and role
                if ($user['is_active'] === 'pending' || !$user['two_factor_enabled'] || !$user['security_question']) {
                    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/first_time_login.php');
                } else {
                    redirect_to_dashboard($user['role']);
                }
                exit;
            } else {
                // Increment failed login attempts
                $failedAttempts = $user['failed_login_attempts'] + 1;

                if ($failedAttempts >= 3) {
                    // Deactivate account after 3 failed attempts
                    $deactivateStmt = $pdo->prepare('UPDATE users SET failed_login_attempts = :attempts, is_active = "inactive" WHERE user_id = :user_id');
                    $deactivateStmt->execute([
                        ':attempts' => $failedAttempts,
                        ':user_id'  => $user['user_id'],
                    ]);
                    $error_message = 'Account has been deactivated due to too many failed login attempts. Please contact administrator.';
                } else {
                    // Update failed login attempts
                    $updateStmt = $pdo->prepare('UPDATE users SET failed_login_attempts = :attempts WHERE user_id = :user_id');
                    $updateStmt->execute([
                        ':attempts' => $failedAttempts,
                        ':user_id'  => $user['user_id'],
                    ]);
                    $error_message = 'Invalid email or password. Attempts remaining: ' . (3 - $failedAttempts);
                }
            }
            break;

        default:
            $error_message = 'Invalid account status. Please contact administrator.';
    }
} else {
    // User not found
    $error_message = 'Invalid email or password.';
}

// If we've reached this point, login was unsuccessful
return;
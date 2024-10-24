<?php
// auth_middleware.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

function check_auth_and_redirect($perform_redirect = true)
{
    global $pdo;

    // Check for session timeout
    $timeout_duration = 30 * 60; // 30 minutes
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
        // Last request was more than 30 minutes ago
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

    // If user is not logged in, return false
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Fetch user data
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // User not found in database, clear session and return false
        session_unset();
        session_destroy();
        return false;
    }

    // Check if user is still active and online
    if ($user['is_active'] !== 'active' || !$user['is_online']) {
        session_unset();
        session_destroy();
        return false;
    }

    // Check if first-time login is completed
    if ($user['is_active'] === 'pending' || !$user['two_factor_enabled'] || !$user['security_question']) {
        if ($perform_redirect && basename($_SERVER['PHP_SELF']) !== 'first_time_login.php') {
            safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/first_time_login.php');
        }
        return true;
    }

    // Check user role for appropriate access
    $allowed_roles = get_allowed_roles();
    if (!in_array($user['role'], $allowed_roles)) {
        if ($perform_redirect) {
            redirect_to_dashboard($user['role']);
        }
        return true;
    }

    return true;
}

function get_allowed_roles()
{
    $current_page = basename($_SERVER['PHP_SELF']);
    switch ($current_page) {
        case 'superadmin_dashboard.php':
            return ['super'];
        case 'admin_dashboard.php':
            return ['admin'];
        case 'user_dashboard.php':
            return ['user'];
        default:
            return ['super', 'admin', 'user'];
    }
}

function redirect_to_dashboard($role)
{
    switch ($role) {
        case 'super':
            safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/superadmin_dashboard.php');
            break;
        case 'admin':
            safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/admin_dashboard.php');
            break;
        case 'user':
            safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/user_dashboard.php');
            break;
        default:
            safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/dashboard.php');
            break;
    }
}

function safe_redirect($url)
{
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . $url . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
        echo '</noscript>';
        exit;
    }
}

function get_user_role()
{
    return $_SESSION['role'] ?? null;
}

function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token)
{
    if (!isset($_SESSION['csrf_token'])) {
        // If there's no CSRF token in the session, generate one
        generate_csrf_token();
        // Since we just generated the token, the submitted one can't be valid
        return false;
    }

    if (!is_string($token)) {
        // If the submitted token is not a string, it's invalid
        return false;
    }

    // Use hash_equals to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Establish database connection
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
} catch (Exception $e) {
    error_log($e->getMessage());
    die('Database connection error.');
}

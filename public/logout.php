<?php
// logout.php

require_once 'config.php';
require_once 'auth_middleware.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function perform_logout($pdo, $user_id)
{
    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Update user's online status to offline
        $stmt = $pdo->prepare('UPDATE users SET is_online = 0 WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $user_id]);

        // Commit transaction
        $pdo->commit();

        // Clear session
        $_SESSION = array();

        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroy session
        session_destroy();

        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log('Logout Error: ' . $e->getMessage());
        return false;
    }
}

// Determine if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check CSRF token for both AJAX and regular requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit;
        } else {
            safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
            exit;
        }
    }
}

if (isset($_SESSION['user_id'])) {
    $success = perform_logout($pdo, $_SESSION['user_id']);

    if ($isAjax) {
        echo json_encode(['success' => $success]);
        exit;
    }
}

// For non-AJAX requests, always redirect to login
if (!$isAjax) {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
}

<?php
// dashboard.php

// Include authentication middleware and configuration
require_once 'auth_middleware.php';
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$errorMessage = "";

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

// Fetch user data
try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        $errorMessage = "User not found in the database. Please try logging in again.";
        error_log("Dashboard Error: User not found for ID " . $_SESSION['user_id']);
    } elseif ($user['is_active'] !== 'active') {
        $errorMessage = "Your account is not active. Please contact the administrator.";
        error_log("Dashboard Error: Inactive account for user ID " . $_SESSION['user_id']);
    } elseif (!in_array($user['role'], ['super', 'admin', 'user'])) {
        $errorMessage = "Invalid user role. Please contact the administrator.";
        error_log("Dashboard Error: Invalid role for user ID " . $_SESSION['user_id']);
    } else {
        // Redirect to the appropriate dashboard based on user role
        switch ($user['role']) {
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
                $errorMessage = "An unexpected error occurred. Please try logging in again.";
                error_log("Dashboard Error: Unexpected role '" . $user['role'] . "' for user ID " . $_SESSION['user_id']);
        }
    }
} catch (PDOException $e) {
    $errorMessage = "A database error occurred. Please try again later or contact the administrator.";
    error_log("Dashboard Error: " . $e->getMessage());
}

// If we reach here, it means there was an error
if (empty($errorMessage)) {
    $errorMessage = "An unexpected error occurred. Please try logging in again or contact the administrator.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AICS-TMS - Dashboard Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }

        .error-container {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="error-container">
        <h1 class="mb-4">Dashboard Error</h1>
        <p class="lead"><?php echo htmlspecialchars($errorMessage); ?></p>
        <a href="/logout.php" class="btn btn-primary mt-3">Logout</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
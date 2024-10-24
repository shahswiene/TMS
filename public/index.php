<?php
// index.php

// Display errors (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include authentication middleware and configuration
require_once 'auth_middleware.php';
require_once 'config.php';

// Check if the user is already authenticated
if (check_auth_and_redirect(false)) {
    // User is authenticated, redirect to dashboard
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/dashboard.php');
} else {
    // User is not authenticated, redirect to login page
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
}

// If we somehow get here, display an error
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AICS-TMS - Error</title>
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
        <h1 class="mb-4">Unexpected Error</h1>
        <p class="lead">An unexpected error occurred. Please try again or contact the administrator.</p>
        <a href="/login.php" class="btn btn-primary mt-3">Go to Login</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
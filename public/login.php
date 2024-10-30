<?php
// At the beginning of login.php, before session_start()

// Configure session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Set session gc maxlifetime
ini_set('session.gc_maxlifetime', 1800); // 30 minutes

// Set the session save handler if needed
// session_set_save_handler(...);

// Now start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Include configuration file and CSRF utility
require_once 'config.php';
require_once 'auth_middleware.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/dashboard.php');
    exit;
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_message = 'Invalid request. Please try again.';
    } else {
        // Include the login processing script
        require_once 'process_login.php';
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AICS-TMS - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body>
    <div class="container-fluid h-100">
        <div class="row h-100">
            <!-- Left Section -->
            <div class="left-section col-md-6 d-flex flex-column justify-content-center align-items-center">
                <div id="carouselExampleInterval" class="carousel slide w-75" data-bs-ride="carousel"
                    data-bs-interval="5000">
                    <div class="carousel-inner">
                        <!-- First Slide: Logo and App Name -->
                        <div class="carousel-item active">
                            <div class="d-flex flex-column justify-content-center align-items-center">
                                <img src="assets/images/AICs.png" alt="App Logo">
                                <h3 class="mt-3">Threat Management System</h3>
                            </div>
                        </div>
                        <!-- Second Slide: Contact and Version -->
                        <div class="carousel-item">
                            <div class="d-flex flex-column justify-content-center align-items-center">
                                <div class="icon-container">
                                    <i class="bi bi-envelope-fill"></i>
                                </div>
                                <p class="contact-info">Any queries? Feel free to contact:</p>
                                <p class="contact-info">
                                    <a href="mailto:shahswiene_suthas@msu.edu.my" style="text-decoration: none; color: inherit;">
                                        shahswiene_suthas@msu.edu.my
                                    </a>
                                </p>
                                <p class="version-info mt-2">V1.0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Section -->
            <div class="right-section col-md-6 d-flex justify-content-center align-items-center">
                <div class="login-form">
                    <h2>LOGIN</h2>
                    <?php
                    if (isset($error_message)) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
                    }
                    ?>
                    <form method="POST" action="https://<?php echo $_SERVER['HTTP_HOST']; ?>/login.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="login-btn">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
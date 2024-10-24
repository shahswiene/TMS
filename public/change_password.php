<?php
// change_password.php

session_start();
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);

require_once 'config.php';
require_once 'csrf_util.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

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
    die('Database connection error.');
}

$current_password_attempts = $_SESSION['current_password_attempts'] ?? 0;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    verify_csrf_token($_POST['csrf_token']);

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match.';
    } elseif (strlen($new_password) < 12) {
        $error_message = 'New password must be at least 12 characters long.';
    } else {
        // Verify current password
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (password_verify($current_password, $user['password_hash'])) {
            // Update password
            $update_stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash, password_last_changed = NOW() WHERE user_id = :user_id');
            $update_stmt->execute([
                ':password_hash' => password_hash($new_password, PASSWORD_DEFAULT),
                ':user_id' => $_SESSION['user_id']
            ]);

            // Reset attempts and remove force change flag
            unset($_SESSION['current_password_attempts']);
            unset($_SESSION['force_password_change']);

            $success_message = 'Password changed successfully.';
            
            // Redirect to dashboard after 3 seconds
            header('Refresh: 3; URL=https://' . $_SERVER['HTTP_HOST'] . '/dashboard.php');
        } else {
            $current_password_attempts++;
            $_SESSION['current_password_attempts'] = $current_password_attempts;

            if ($current_password_attempts >= 3) {
                // Lock account after 3 failed attempts
                $lockStmt = $pdo->prepare('UPDATE users SET account_locked = 1 WHERE user_id = :user_id');
                $lockStmt->execute([':user_id' => $_SESSION['user_id']]);
                
                session_destroy();
                $error_message = 'Account locked due to too many failed attempts. Please contact administrator.';
                header('Refresh: 3; URL=https://' . $_SERVER['HTTP_HOST'] . '/login.php');
            } else {
                $error_message = 'Invalid current password. Attempts remaining: ' . (3 - $current_password_attempts);
            }
        }
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
    <title>Change Password - AICS-TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 500px;
            width: 100%;
            margin: auto;
        }
        .strength-indicator {
            height: 5px;
        }
    </style>
</head>
<body>
    <div class="container form-container mt-5">
        <div class="card shadow border-0">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Change Password</h2>
                <?php
                if (isset($error_message)) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
                }
                if (isset($success_message)) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
                }
                ?>
                <form method="POST" action="https://<?php echo $_SERVER['HTTP_HOST']; ?>/change_password.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="strength-indicator mt-2" id="strengthIndicator"></div>
                        <small id="passwordHelpBlock" class="form-text text-muted">
                            Your password must be at least 12 characters long.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/password_strength.js"></script>

</body>
</html>

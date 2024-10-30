<?php
// first_time_login.php

require_once 'config.php';
require_once 'auth_middleware.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
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

// Fetch user information including username and current password hash
$stmt = $pdo->prepare('SELECT username, password_hash, two_factor_enabled, security_question, role, is_active FROM users WHERE user_id = :user_id');
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['is_active'] === 'active' && $user['two_factor_enabled'] && $user['security_question']) {
    // Account is already configured, redirect based on user role
    redirect_to_dashboard($user['role']);
    exit;
}

$error_message = '';
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    verify_csrf_token($_POST['csrf_token']);

    $username = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $security_question = trim($_POST['security_question'] ?? '');
    $security_answer = trim($_POST['security_answer'] ?? '');

    // Validate input
    if (empty($username) || empty($first_name) || empty($last_name) || empty($phone_number) || empty($new_password) || empty($confirm_password) || empty($security_question) || empty($security_answer)) {
        $error_message = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($new_password) < 12) {
        $error_message = 'Password must be at least 12 characters long.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};:\"\\|,.<>\/?])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};:\"\\|,.<>\/?]{12,}$/', $new_password)) {
        $error_message = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
    } elseif (password_verify($new_password, $user['password_hash'])) {
        $error_message = 'New password must be different from the current password.';
    } else {
        try {
            // Check if username already exists
            $check_stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username AND user_id != :user_id');
            $check_stmt->execute([':username' => $username, ':user_id' => $_SESSION['user_id']]);
            if ($check_stmt->fetchColumn() > 0) {
                $error_message = 'Username already exists. Please choose a different username.';
            } else {
                // Update user information
                $update_stmt = $pdo->prepare('UPDATE users SET 
                    username = :username,
                    first_name = :first_name, 
                    last_name = :last_name, 
                    phone_number = :phone_number, 
                    password_hash = :password_hash, 
                    security_question = :security_question, 
                    security_answer_hash = :security_answer_hash, 
                    password_last_changed = NOW(), 
                    two_factor_enabled = 1,
                    is_active = "active"
                    WHERE user_id = :user_id');

                $update_stmt->execute([
                    ':username' => $username,
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':phone_number' => $phone_number,
                    ':password_hash' => password_hash($new_password, PASSWORD_DEFAULT),
                    ':security_question' => $security_question,
                    ':security_answer_hash' => password_hash($security_answer, PASSWORD_DEFAULT),
                    ':user_id' => $_SESSION['user_id']
                ]);

                $success_message = 'Account updated successfully. Redirecting to dashboard...';
                header('Refresh: 3; URL=https://' . $_SERVER['HTTP_HOST'] . '/dashboard.php');
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error_message = 'An error occurred while updating your account. Please try again.';
        }
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();

// List of security questions
$security_questions = [
    "What was the name of your first pet?",
    "In what city were you born?",
    "What is your mother's maiden name?",
    "What was the name of your elementary school?",
    "What was your childhood nickname?",
    "What is the name of your favorite childhood friend?",
    "What street did you live on in third grade?",
    "What is the middle name of your oldest child?",
    "What is your oldest sibling's middle name?",
    "What school did you attend for sixth grade?",
    "What is your oldest cousin's first and last name?",
    "What was the name of your first stuffed animal?",
    "In what city or town did your mother and father meet?",
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First Time Login - AICS-TMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
    <link rel="stylesheet" href="/assets/css/first_time_login.css">
    <style>
        .iti {
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card">
            <div class="card-header text-center py-3">
                <h2 class="mb-0" style="font-size: 1.5rem;">First Time Login</h2>
            </div>
            <div class="card-body">
                <h5 class="card-title text-center mb-4" style="font-size: 1.2rem;">Account Configuration</h5>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" style="font-size: 0.9rem;"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="alert alert-success" style="font-size: 0.9rem;"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>
                <form method="POST" action="https://<?= $_SERVER['HTTP_HOST'] ?>/first_time_login.php" id="firstTimeLoginForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="mb-2">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control form-control-sm" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required readonly>
                    </div>
                    <div class="mb-2">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control form-control-sm" id="first_name" name="first_name" required>
                    </div>
                    <div class="mb-2">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control form-control-sm" id="last_name" name="last_name" required>
                    </div>
                    <div class="mb-2">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control form-control-sm" id="phone_number" name="phone_number" required>
                    </div>
                    <div class="mb-2">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control form-control-sm" id="new_password" name="new_password" required>
                        <div class="strength-indicator mt-1" id="strengthIndicator"></div>
                        <small id="passwordHelpBlock" class="form-text text-muted" style="font-size: 0.75rem;">
                            Password must contain:<br>
                            • At least 12 characters<br>
                            • At least 1 uppercase letter<br>
                            • At least 1 lowercase letter<br>
                            • At least 1 number<br>
                            • At least 1 special character (!@#$%^&*()_+-=[]{}:"|,./<>?)
                        </small>
                    </div>
                    <div class="mb-2">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control form-control-sm" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="mb-2">
                        <label for="security_question" class="form-label">Security Question</label>
                        <select class="form-select form-select-sm" id="security_question" name="security_question" required>
                            <option value="" disabled selected>Choose a security question</option>
                            <?php foreach ($security_questions as $question): ?>
                                <option value="<?= htmlspecialchars($question) ?>"><?= htmlspecialchars($question) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="security_answer" class="form-label">Security Answer</label>
                        <input type="text" class="form-control form-control-sm" id="security_answer" name="security_answer" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-sm" id="submitButton">Update Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script src="/assets/js/password_strength.js"></script>
    <script src="/assets/js/first_time_login.js"></script>
</body>

</html>
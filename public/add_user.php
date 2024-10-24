<?php
// add_user.php

require_once 'auth_middleware.php';
require_once 'config.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$department_id = $_POST['department_id'] ?? null;
$position_id = $_POST['position_id'] ?? null;
$role = $_POST['role'] ?? 'user';

// Validate input
if (empty($username) || empty($email) || empty($password) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

// Validate role
$valid_roles = ['super', 'admin', 'user'];
if (!in_array($role, $valid_roles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role specified']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if username or email already exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Username or email already exists');
    }

    // Insert new user
    $stmt = $pdo->prepare('
        INSERT INTO users (
            username, 
            email, 
            password_hash, 
            department_id, 
            position_id, 
            role, 
            is_active,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, "pending", NOW())
    ');

    $stmt->execute([
        $username,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $department_id,
        $position_id,
        $role
    ]);

    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'User added successfully. They will need to complete first-time login setup.'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage() === 'Username or email already exists' 
            ? $e->getMessage() 
            : 'An error occurred while adding the user'
    ]);
}
?>
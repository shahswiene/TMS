<?php
// update_user.php

require_once 'auth_middleware.php';
require_once 'config.php';

// Ensure proper authentication
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

// Get and sanitize input
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$department_id = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT) ?: null;
$position_id = filter_input(INPUT_POST, 'position_id', FILTER_VALIDATE_INT) ?: null;
$role = trim($_POST['role'] ?? '');
$status = trim($_POST['is_active'] ?? '');

// Validate required fields
if (!$user_id || empty($username) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'User ID, username, and email are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate role
$valid_roles = ['super', 'admin', 'user'];
if (!empty($role) && !in_array($role, $valid_roles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role specified']);
    exit;
}

// Validate status
$valid_statuses = ['active', 'inactive', 'pending'];
if (!empty($status) && !in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status specified']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if username or email already exists for other users
    $stmt = $pdo->prepare('
        SELECT COUNT(*) 
        FROM users 
        WHERE (username = ? OR email = ?) 
        AND user_id != ?
    ');
    $stmt->execute([$username, $email, $user_id]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Username or email already exists');
    }

    // Get current user data to check if it's a super admin
    $stmt = $pdo->prepare('SELECT role FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $currentUser = $stmt->fetch();

    // Prevent role change for the last super admin
    if ($currentUser['role'] === 'super' && $role !== 'super') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = "super"');
        $stmt->execute();
        if ($stmt->fetchColumn() <= 1) {
            throw new Exception('Cannot change role: This is the last super admin');
        }
    }

    // Build update query
    $updates = [];
    $params = [];

    // Basic updates
    $updates[] = 'username = ?';
    $params[] = $username;
    $updates[] = 'email = ?';
    $params[] = $email;
    $updates[] = 'department_id = ?';
    $params[] = $department_id;
    $updates[] = 'position_id = ?';
    $params[] = $position_id;

    // Role update
    if (!empty($role)) {
        $updates[] = 'role = ?';
        $params[] = $role;
    }

    // Status update
    if (!empty($status)) {
        $updates[] = 'is_active = ?';
        $params[] = $status;
        
        // If setting to inactive, force logout
        if ($status === 'inactive') {
            $updates[] = 'is_online = 0';
        }
    }

    // Password update
    if (!empty($password)) {
        // Validate password strength
        if (strlen($password) < 12) {
            throw new Exception('Password must be at least 12 characters long');
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/', $password)) {
            throw new Exception('Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character');
        }
        
        $updates[] = 'password_hash = ?';
        $params[] = password_hash($password, PASSWORD_DEFAULT);
        $updates[] = 'password_last_changed = NOW()';
    }

    // Add user_id to params
    $params[] = $user_id;

    // Execute update
    $sql = 'UPDATE users SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE user_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'User updated successfully'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage() === 'Username or email already exists' || 
                    $e->getMessage() === 'Cannot change role: This is the last super admin' ||
                    strpos($e->getMessage(), 'Password must') === 0
            ? $e->getMessage() 
            : 'An error occurred while updating the user'
    ]);
}
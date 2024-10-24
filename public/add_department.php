<?php
require_once 'auth_middleware.php';
require_once 'config.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
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

$department_name = $_POST['department_name'] ?? '';

if (empty($department_name)) {
    echo json_encode(['success' => false, 'message' => 'Department name is required']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO departments (department_name, added_by, created_ip) VALUES (?, ?, ?)');
    $stmt->execute([$department_name, $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
    
    echo json_encode(['success' => true, 'message' => 'Department added successfully']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while adding the department']);
}
?>
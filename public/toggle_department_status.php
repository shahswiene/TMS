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

$data = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!isset($data['csrf_token']) || !verify_csrf_token($data['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$department_id = $data['department_id'] ?? '';
$new_status = $data['status'] ?? '';

if (empty($department_id) || empty($new_status)) {
    echo json_encode(['success' => false, 'message' => 'Department ID and new status are required']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE departments SET status = ? WHERE department_id = ?');
    $stmt->execute([$new_status, $department_id]);
    
    echo json_encode(['success' => true, 'message' => 'Department status updated successfully']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating department status']);
}
?>
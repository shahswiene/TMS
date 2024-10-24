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

$position_id = $_POST['position_id'] ?? '';
$position_name = $_POST['position_name'] ?? '';

if (empty($position_id) || empty($position_name)) {
    echo json_encode(['success' => false, 'message' => 'Position ID and name are required']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE positions SET position_name = ? WHERE position_id = ?');
    $stmt->execute([$position_name, $position_id]);
    
    echo json_encode(['success' => true, 'message' => 'Position updated successfully']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating the position']);
}
?>
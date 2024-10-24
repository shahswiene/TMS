<?php
// delete_agent.php

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

$agent_id = $data['agent_id'] ?? '';

if (empty($agent_id)) {
    echo json_encode(['success' => false, 'message' => 'Agent ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM agents WHERE agent_id = ?');
    $stmt->execute([$agent_id]);
    
    echo json_encode(['success' => true, 'message' => 'Agent deleted successfully']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the agent']);
}
?>
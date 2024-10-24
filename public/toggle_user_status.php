<?php
// toggle_user_status.php

require_once 'auth_middleware.php';
require_once 'config.php';

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

$user_id = $data['user_id'] ?? '';
$current_status = $data['current_status'] ?? '';

if (empty($user_id) || empty($current_status)) {
    echo json_encode(['success' => false, 'message' => 'User ID and current status are required']);
    exit;
}

// Determine new status
$new_status = $current_status === 'active' ? 'inactive' : 'active';

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Update user status
    $stmt = $pdo->prepare('UPDATE users SET is_active = :new_status WHERE user_id = :user_id');
    $stmt->execute([
        ':new_status' => $new_status,
        ':user_id' => $user_id
    ]);

    // If deactivating, force logout
    if ($new_status === 'inactive') {
        $stmt = $pdo->prepare('UPDATE users SET is_online = 0 WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $user_id]);
    }

    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "User successfully " . ($new_status === 'active' ? 'activated' : 'deactivated'),
        'new_status' => $new_status
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating user status']);
}
<?php
// add_comment.php

require_once 'auth_middleware.php';
require_once 'config.php';

header('Content-Type: application/json');

if (!check_auth_and_redirect()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

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

// Validate input
if (empty($data['ticket_id']) || empty($data['comment'])) {
    echo json_encode(['success' => false, 'message' => 'Ticket ID and comment are required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO ticket_comments (
            ticket_id, user_id, comment
        ) VALUES (
            :ticket_id, :user_id, :comment
        )
    ");

    $stmt->execute([
        ':ticket_id' => $data['ticket_id'],
        ':user_id' => $_SESSION['user_id'],
        ':comment' => $data['comment']
    ]);

    // Add history entry
    $stmt = $pdo->prepare("
        INSERT INTO ticket_history (
            ticket_id, user_id, action_type,
            new_value
        ) VALUES (
            :ticket_id, :user_id, 'comment_added',
            :comment
        )
    ");

    $stmt->execute([
        ':ticket_id' => $data['ticket_id'],
        ':user_id' => $_SESSION['user_id'],
        ':comment' => substr($data['comment'], 0, 100) . (strlen($data['comment']) > 100 ? '...' : '')
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Comment added successfully'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while adding the comment'
    ]);
}

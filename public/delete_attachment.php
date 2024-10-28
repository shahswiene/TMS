<?php
// delete_attachment.php

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

$attachmentId = $data['attachment_id'] ?? '';
if (empty($attachmentId)) {
    echo json_encode(['success' => false, 'message' => 'Attachment ID is required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get attachment details
    $stmt = $pdo->prepare("
        SELECT a.*, t.created_by, t.assigned_agent_id 
        FROM ticket_attachments a
        JOIN tickets t ON a.ticket_id = t.ticket_id
        WHERE a.attachment_id = ?
    ");
    $stmt->execute([$attachmentId]);
    $attachment = $stmt->fetch();

    if (!$attachment) {
        throw new Exception('Attachment not found');
    }

    // Check if user has permission to delete
    if (
        $_SESSION['user_id'] != $attachment['user_id'] &&
        $_SESSION['user_id'] != $attachment['created_by'] &&
        $_SESSION['user_id'] != $attachment['assigned_agent_id'] &&
        $_SESSION['role'] != 'super'
    ) {
        throw new Exception('You do not have permission to delete this attachment');
    }

    // Delete file from filesystem
    if (file_exists($attachment['file_path'])) {
        if (!unlink($attachment['file_path'])) {
            throw new Exception('Failed to delete file from server');
        }
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM ticket_attachments WHERE attachment_id = ?");
    $stmt->execute([$attachmentId]);

    // Add history entry
    $stmt = $pdo->prepare("
        INSERT INTO ticket_history (
            ticket_id, user_id, action_type,
            old_value
        ) VALUES (
            :ticket_id, :user_id, 'attachment_deleted',
            :file_name
        )
    ");

    $stmt->execute([
        ':ticket_id' => $attachment['ticket_id'],
        ':user_id' => $_SESSION['user_id'],
        ':file_name' => $attachment['file_name']
    ]);

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Attachment deleted successfully'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

<?php
// delete_attachment.php

require_once 'auth_middleware.php';
require_once 'config.php';

check_auth_and_redirect();

header('Content-Type: application/json');

function handleError($message)
{
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    handleError('Method Not Allowed');
}

$data = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!isset($data['csrf_token']) || !verify_csrf_token($data['csrf_token'])) {
    http_response_code(403);
    handleError('Invalid CSRF token');
}

$attachment_id = $data['attachment_id'] ?? '';

if (empty($attachment_id)) {
    handleError('Attachment ID is required');
}

try {
    $pdo->beginTransaction();

    // Fetch attachment details
    $stmt = $pdo->prepare('SELECT ta.*, t.created_by, t.assigned_to, t.ticket_id 
                           FROM ticket_attachments ta
                           JOIN tickets t ON ta.ticket_id = t.ticket_id
                           WHERE ta.attachment_id = ?');
    $stmt->execute([$attachment_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$attachment) {
        throw new Exception('Attachment not found');
    }

    // Check if the user is allowed to delete this attachment
    $current_user_id = get_user_id();
    if (
        $current_user_id != $attachment['uploaded_by'] &&
        $current_user_id != $attachment['created_by'] &&
        $current_user_id != $attachment['assigned_to']
    ) {
        throw new Exception('You are not authorized to delete this attachment');
    }

    // Delete the file
    if (file_exists($attachment['file_path'])) {
        if (!unlink($attachment['file_path'])) {
            throw new Exception('Failed to delete the file from the server');
        }
    }

    // Delete the attachment record
    $delete_stmt = $pdo->prepare('DELETE FROM ticket_attachments WHERE attachment_id = ?');
    if (!$delete_stmt->execute([$attachment_id])) {
        throw new Exception('Failed to delete the attachment record from the database');
    }

    // Add entry to ticket history
    $history_stmt = $pdo->prepare('INSERT INTO ticket_history (ticket_id, user_id, action, details) VALUES (?, ?, ?, ?)');
    $history_stmt->execute([
        $attachment['ticket_id'],
        $current_user_id,
        'updated',
        "Attachment deleted: {$attachment['file_name']}"
    ]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Attachment deleted successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    handleError('An error occurred while deleting the attachment: ' . $e->getMessage());
}
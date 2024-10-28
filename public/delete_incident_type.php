<?php
// delete_incident_type.php

require_once 'auth_middleware.php';
require_once 'config.php';

header('Content-Type: application/json');

// Ensure user is authenticated and has super admin role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Get and decode JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!isset($data['csrf_token']) || !verify_csrf_token($data['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$type_id = $data['incident_type_id'] ?? '';

if (empty($type_id)) {
    echo json_encode(['success' => false, 'message' => 'Incident type ID is required']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if incident type is being used
    $stmt = $pdo->prepare('
        SELECT COUNT(*) 
        FROM tickets 
        WHERE incident_type_id = ?
    ');
    $stmt->execute([$type_id]);
    $ticket_count = $stmt->fetchColumn();

    if ($ticket_count > 0) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete this incident type as it is being used by existing tickets'
        ]);
        exit;
    }

    // Get incident type details for logging
    $stmt = $pdo->prepare('SELECT * FROM incident_types WHERE incident_type_id = ?');
    $stmt->execute([$type_id]);
    $type = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$type) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Incident type not found'
        ]);
        exit;
    }

    // Delete incident type
    $stmt = $pdo->prepare('DELETE FROM incident_types WHERE incident_type_id = ?');
    $stmt->execute([$type_id]);

    // Log the deletion
    $stmt = $pdo->prepare('
        INSERT INTO system_logs (
            user_id,
            action,
            details,
            ip_address,
            created_at
        ) VALUES (
            :user_id,
            "delete_incident_type",
            :details,
            :ip_address,
            NOW()
        )
    ');

    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':details' => json_encode([
            'incident_type_id' => $type_id,
            'type_name' => $type['type_name'],
            'description' => $type['description']
        ]),
        ':ip_address' => $_SERVER['REMOTE_ADDR']
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Incident type deleted successfully'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting the incident type'
    ]);
}

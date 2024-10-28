<?php
// toggle_incident_type_status.php

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
$current_status = $data['current_status'] ?? '';

if (empty($type_id) || empty($current_status)) {
    echo json_encode(['success' => false, 'message' => 'Incident type ID and current status are required']);
    exit;
}

try {
    // Get type details and check if it's in use
    $stmt = $pdo->prepare('
        SELECT COUNT(t.ticket_id) as ticket_count
        FROM incident_types it
        LEFT JOIN tickets t ON it.incident_type_id = t.incident_type_id
        WHERE it.incident_type_id = ?
        GROUP BY it.incident_type_id
    ');
    $stmt->execute([$type_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // If trying to deactivate and type is in use, prevent deactivation
    if ($current_status === 'active' && $result && $result['ticket_count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot deactivate this incident type as it is being used by existing tickets'
        ]);
        exit;
    }

    // Toggle status
    $new_status = $current_status === 'active' ? 'inactive' : 'active';
    $stmt = $pdo->prepare('
        UPDATE incident_types 
        SET status = :status, updated_at = NOW() 
        WHERE incident_type_id = :type_id
    ');

    $stmt->execute([
        ':status' => $new_status,
        ':type_id' => $type_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Incident type ' . ($new_status === 'active' ? 'activated' : 'deactivated') . ' successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Incident type not found or no changes made'
        ]);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating the incident type status'
    ]);
}

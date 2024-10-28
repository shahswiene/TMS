<?php
// get_incident_type_details.php

require_once 'auth_middleware.php';
require_once 'config.php';

header('Content-Type: application/json');

// Ensure user is authenticated and has super admin role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$type_id = $_GET['id'] ?? '';
if (empty($type_id)) {
    echo json_encode(['success' => false, 'message' => 'Incident type ID is required']);
    exit;
}

try {
    // Get incident type details with usage count
    $stmt = $pdo->prepare('
        SELECT 
            it.*,
            COUNT(t.ticket_id) as ticket_count
        FROM incident_types it
        LEFT JOIN tickets t ON it.incident_type_id = t.incident_type_id
        WHERE it.incident_type_id = ?
        GROUP BY it.incident_type_id
    ');

    $stmt->execute([$type_id]);
    $type = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($type) {
        echo json_encode([
            'success' => true,
            'data' => $type
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Incident type not found'
        ]);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching incident type details'
    ]);
}

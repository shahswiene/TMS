<?php
// update_incident_type.php

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

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Validate required fields
if (empty($_POST['incident_type_id'])) {
    echo json_encode(['success' => false, 'message' => 'Incident type ID is required']);
    exit;
}

$required_fields = ['type_name', 'description', 'default_priority', 'default_sla_hours', 'status'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
        exit;
    }
}

// Validate default_priority
$valid_priorities = ['low', 'medium', 'high', 'critical'];
if (!in_array($_POST['default_priority'], $valid_priorities)) {
    echo json_encode(['success' => false, 'message' => 'Invalid priority level']);
    exit;
}

// Validate status
$valid_statuses = ['active', 'inactive'];
if (!in_array($_POST['status'], $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Validate SLA hours
$sla_hours = intval($_POST['default_sla_hours']);
if ($sla_hours < 1 || $sla_hours > 168) {
    echo json_encode(['success' => false, 'message' => 'SLA hours must be between 1 and 168']);
    exit;
}

try {
    // Check if type name already exists for other types
    $stmt = $pdo->prepare('
        SELECT COUNT(*) 
        FROM incident_types 
        WHERE type_name = ? 
        AND incident_type_id != ?
    ');
    $stmt->execute([$_POST['type_name'], $_POST['incident_type_id']]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'An incident type with this name already exists']);
        exit;
    }

    // Update incident type
    $stmt = $pdo->prepare('
        UPDATE incident_types 
        SET 
            type_name = :type_name,
            description = :description,
            default_priority = :default_priority,
            default_sla_hours = :default_sla_hours,
            status = :status,
            updated_at = NOW()
        WHERE incident_type_id = :incident_type_id
    ');

    $stmt->execute([
        ':incident_type_id' => $_POST['incident_type_id'],
        ':type_name' => $_POST['type_name'],
        ':description' => $_POST['description'],
        ':default_priority' => $_POST['default_priority'],
        ':default_sla_hours' => $sla_hours,
        ':status' => $_POST['status']
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Incident type updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No changes were made or incident type not found'
        ]);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating the incident type'
    ]);
}

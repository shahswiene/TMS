<?php
// create_incident_type.php

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
$required_fields = ['type_name', 'description', 'default_priority', 'default_sla_hours'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
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

// Validate SLA hours
$sla_hours = intval($_POST['default_sla_hours']);
if ($sla_hours < 1 || $sla_hours > 168) {
    echo json_encode(['success' => false, 'message' => 'SLA hours must be between 1 and 168']);
    exit;
}

try {
    // Check if type name already exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM incident_types WHERE type_name = ?');
    $stmt->execute([$_POST['type_name']]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'An incident type with this name already exists']);
        exit;
    }

    // Insert new incident type
    $stmt = $pdo->prepare('
        INSERT INTO incident_types (
            type_name, description, default_priority,
            default_sla_hours, status, created_at,
            updated_at
        ) VALUES (
            :type_name, :description, :default_priority,
            :default_sla_hours, "active", NOW(),
            NOW()
        )
    ');

    $stmt->execute([
        ':type_name' => $_POST['type_name'],
        ':description' => $_POST['description'],
        ':default_priority' => $_POST['default_priority'],
        ':default_sla_hours' => $sla_hours
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Incident type created successfully',
        'incident_type_id' => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating the incident type'
    ]);
}

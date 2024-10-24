<?php
// update_agent.php

require_once 'auth_middleware.php';
require_once 'config.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}


header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method Not Allowed');
    }

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }

    $agent_id = $_POST['agent_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $ip_address = $_POST['ip_address'] ?? '';
    $agent_group = $_POST['agent_group'] ?? '';
    $operating_system = $_POST['operating_system'] ?? '';
    $cluster_node = $_POST['cluster_node'] ?? '';
    $version = $_POST['version'] ?? '';

    if (empty($agent_id) || empty($name) || empty($ip_address)) {
        throw new Exception('Agent ID, name, and IP address are required');
    }

    $stmt = $pdo->prepare('UPDATE agents SET name = ?, ip_address = ?, agent_group = ?, operating_system = ?, cluster_node = ?, version = ? WHERE agent_id = ?');
    $stmt->execute([$name, $ip_address, $agent_group, $operating_system, $cluster_node, $version, $agent_id]);
    
    echo json_encode(['success' => true, 'message' => 'Agent updated successfully']);
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
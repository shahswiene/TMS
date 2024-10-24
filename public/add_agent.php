<?php
// add_agent.php

require_once 'auth_middleware.php';
require_once 'config.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}


header('Content-Type: application/json');

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

$name = $_POST['name'] ?? '';
$ip_address = $_POST['ip_address'] ?? '';
$agent_group = $_POST['agent_group'] ?? 'default';
$operating_system = $_POST['operating_system'] ?? '';
$cluster_node = $_POST['cluster_node'] ?? '';
$version = $_POST['version'] ?? '';

if (empty($name) || empty($ip_address)) {
    echo json_encode(['success' => false, 'message' => 'Name and IP address are required']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO agents (name, ip_address, agent_group, operating_system, cluster_node, version) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $ip_address, $agent_group, $operating_system, $cluster_node, $version]);
    
    echo json_encode(['success' => true, 'message' => 'Agent added successfully']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while adding the agent']);
}
?>
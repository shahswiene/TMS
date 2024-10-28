<?php
// get_agent_details.php

require_once 'auth_middleware.php';
require_once 'config.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$agent_id = $_GET['id'] ?? '';

if (empty($agent_id)) {
    echo json_encode(['success' => false, 'message' => 'Agent ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM wazuh_agents WHERE agent_id = ?');
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($agent) {
        echo json_encode(['success' => true, 'data' => $agent]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Agent not found']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching agent details']);
}
?>
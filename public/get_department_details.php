<?php
//get_department_details.php

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

$department_id = $_GET['id'] ?? '';

if (empty($department_id)) {
    echo json_encode(['success' => false, 'message' => 'Department ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT department_id, department_name, status FROM departments WHERE department_id = ?');
    $stmt->execute([$department_id]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($department) {
        echo json_encode(['success' => true, 'data' => $department]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Department not found']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching department details']);
}
?>
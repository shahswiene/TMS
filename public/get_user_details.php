<?php
// get_user_details.php

require_once 'auth_middleware.php';
require_once 'config.php';

header('Content-Type: application/json');

// Ensure proper authentication
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    $stmt = $pdo->prepare('
        SELECT 
            u.user_id,
            u.username,
            u.email,
            u.department_id,
            u.position_id,
            u.role,
            u.is_active,
            u.is_online,
            u.last_login,
            u.first_name,
            u.last_name,
            u.phone_number,
            u.two_factor_enabled,
            d.department_name,
            p.position_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.department_id
        LEFT JOIN positions p ON u.position_id = p.position_id
        WHERE u.user_id = ?
    ');

    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Remove sensitive information
        unset($user['password_hash']);
        unset($user['security_question']);
        unset($user['security_answer_hash']);

        echo json_encode([
            'success' => true,
            'data' => $user
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching user details'
    ]);
}

<?php
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

$position_id = $_GET['id'] ?? '';

if (empty($position_id)) {
    echo json_encode(['success' => false, 'message' => 'Position ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT position_id, position_name, status FROM positions WHERE position_id = ?');
    $stmt->execute([$position_id]);
    $position = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($position) {
        echo json_encode(['success' => true, 'data' => $position]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Position not found']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching position details']);
}
?>
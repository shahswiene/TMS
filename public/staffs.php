<?php
// staffs.php

require_once 'auth_middleware.php';
require_once 'superadmin_common_components.php';
require_once 'staffs_components.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Fetch total number of staffs
$total_staffs = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn();

// Fetch staffs data with pagination
$stmt = $pdo->prepare('SELECT u.*, d.department_name, p.position_name FROM users u 
                       LEFT JOIN departments d ON u.department_id = d.department_id 
                       LEFT JOIN positions p ON u.position_id = p.position_id 
                       WHERE u.role = "user"
                       LIMIT :offset, :perPage');
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$staffs = $stmt->fetchAll();

// Fetch departments and positions for dropdowns
$departments = $pdo->query('SELECT department_id, department_name FROM departments WHERE status = "active" ORDER BY department_name')->fetchAll();
$positions = $pdo->query('SELECT position_id, position_name FROM positions WHERE status = "active" ORDER BY position_name')->fetchAll();

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Prepare the content for the staffs page
$content = '<h1 class="h3 mb-4">Staffs</h1>';
$content .= render_staffs_table($staffs);
$content .= render_add_staff_modal($departments, $positions);
$content .= render_edit_staff_modal($departments, $positions);
$content .= '<script src="/assets/js/staffs.js"></script>';

echo render_page('Staffs', $content, 'Staffs');
?>
<?php
// administrators.php

require_once 'auth_middleware.php';
require_once 'superadmin_common_components.php';
require_once 'administrators_components.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

// Fetch administrators data
$stmt = $pdo->prepare('
    SELECT 
        u.*,
        d.department_name,
        p.position_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.department_id 
    LEFT JOIN positions p ON u.position_id = p.position_id 
    WHERE u.role = "admin"
    ORDER BY u.created_at DESC'
);
$stmt->execute();
$administrators = $stmt->fetchAll();

// Fetch departments and positions for dropdowns
$departments = $pdo->query('SELECT department_id, department_name FROM departments WHERE status = "active" ORDER BY department_name')->fetchAll();
$positions = $pdo->query('SELECT position_id, position_name FROM positions WHERE status = "active" ORDER BY position_name')->fetchAll();

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Prepare the content for the administrators page
$content = '<h1 class="h3 mb-4">Administrators</h1>';
$content .= render_administrators_table($administrators);
$content .= render_add_administrator_modal($departments, $positions);
$content .= render_edit_administrator_modal($departments, $positions);
$content .= '<script src="/assets/js/administrators.js"></script>';

echo render_page('Administrators', $content, 'Administrators');
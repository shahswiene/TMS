<?php
// departments.php

require_once 'auth_middleware.php';
require_once 'superadmin_common_components.php';
require_once 'departments_components.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

// Fetch all departments data
$stmt = $pdo->prepare('SELECT * FROM departments');
$stmt->execute();
$departments = $stmt->fetchAll();

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Prepare the content for the departments page
$content = '<h1 class="h3 mb-4">Departments</h1>';
$content .= render_departments_table($departments);
$content .= render_add_department_modal();
$content .= render_edit_department_modal();
$content .= '<script src="/assets/js/departments.js"></script>';

echo render_page('Departments', $content, 'Departments');
?>
<?php
// superadmin_dashboard.php

require_once 'auth_middleware.php';
require_once 'superadmin_common_components.php';
require_once 'superadmin_dashboard_components.php';

// Fetch all users data
$stmt_all = $pdo->prepare('SELECT u.*, d.department_name, p.position_name FROM users u 
                           LEFT JOIN departments d ON u.department_id = d.department_id 
                           LEFT JOIN positions p ON u.position_id = p.position_id');
$stmt_all->execute();
$users = $stmt_all->fetchAll();

// Fetch other statistics
$total_number_users = count($users);
$total_users = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn();
$total_admins = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "admin"')->fetchColumn();
$total_online_users = $pdo->query('SELECT COUNT(*) FROM users WHERE is_online = 1')->fetchColumn();
$total_agents = $pdo->query('SELECT COUNT(*) FROM wazuh_agent')->fetchColumn();
$total_active_agents = $pdo->query('SELECT COUNT(*) FROM wazuh_agent WHERE status = "active"')->fetchColumn();

// Fetch departments and positions for dropdowns
$departments = $pdo->query('SELECT department_id, department_name FROM departments WHERE status = "active" ORDER BY department_name')->fetchAll();
$positions = $pdo->query('SELECT position_id, position_name FROM positions WHERE status = "active" ORDER BY position_name')->fetchAll();

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Prepare the content for the dashboard
$content = '<h1 class="h3 mb-4">Superadmin Dashboard</h1>';
$content .= render_dashboard_stats($total_number_users, $total_admins, $total_users, $total_online_users, $total_agents, $total_active_agents);
$content .= render_users_table($users, $departments, $positions);
$content .= render_add_user_modal($departments, $positions);
$content .= '<script src="/assets/js/superadmin_dashboard.js"></script>';
$content .= '<script src="/assets/js/password_strength.js"></script>';  

// Render the page
echo render_page('Superadmin Dashboard', $content, 'Dashboard');

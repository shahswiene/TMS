<?php
// agents.php

require_once 'auth_middleware.php';
require_once 'superadmin_common_components.php';
require_once 'agents_components.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

// Fetch all agents data
$stmt = $pdo->prepare('SELECT * FROM wazuh_agents');
$stmt->execute();
$agents = $stmt->fetchAll();

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Prepare the content for the agents page
$content = '<h1 class="h3 mb-4">Agents</h1>';
$content .= render_agents_table($agents);
$content .= render_add_agent_modal();
$content .= render_edit_agent_modal();
$content .= '<script src="/assets/js/agents.js"></script>';

echo render_page('Agents', $content, 'Agents');
?>
<?php
// positions.php

require_once 'auth_middleware.php';
require_once 'superadmin_common_components.php';
require_once 'positions_components.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

// Fetch all positions data
$stmt = $pdo->prepare('SELECT * FROM positions');
$stmt->execute();
$positions = $stmt->fetchAll();

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Prepare the content for the positions page
$content = '<h1 class="h3 mb-4">Positions</h1>';
$content .= render_positions_table($positions);
$content .= render_add_position_modal();
$content .= render_edit_position_modal();
$content .= '<script src="/assets/js/positions.js"></script>';

echo render_page('Positions', $content, 'Positions');
?>
<?php
// incident_types.php

require_once 'auth_middleware.php';
require_once 'superadmin_common_components.php';
require_once 'incident_types_components.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

// Fetch all incident types
$stmt = $pdo->prepare('
    SELECT 
        it.*,
        COUNT(t.ticket_id) as ticket_count 
    FROM incident_types it 
    LEFT JOIN tickets t ON it.incident_type_id = t.incident_type_id 
    GROUP BY it.incident_type_id
    ORDER BY it.created_at DESC
');
$stmt->execute();
$incident_types = $stmt->fetchAll();

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Prepare the content for the incident types page
$content = '<h1 class="h3 mb-4">Incident Types</h1>';
$content .= render_incident_types_stats($pdo);
$content .= render_incident_types_table($incident_types);
$content .= render_add_incident_type_modal();
$content .= render_edit_incident_type_modal();
$content .= '<script src="/assets/js/incident_types.js"></script>';

echo render_page('Incident Types', $content, 'Incident Types');

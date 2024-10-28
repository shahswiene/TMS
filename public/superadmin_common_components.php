<?php
// superadmin_common_components.php

// Include the auth_middleware and check_admin_and_redirect functions
require_once 'auth_middleware.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

// Don't let the user access this file directly
if (basename($_SERVER['PHP_SELF']) === 'superadmin_common_components.php') {
    die('This script cannot be accessed directly.');
}

function render_sidebar($active_page = '')
{
    $menu_items = [
        ['icon' => 'house-door', 'text' => 'Dashboard', 'link' => '/superadmin_dashboard.php'],
        ['icon' => 'shield-lock', 'text' => 'Administrators', 'link' => 'administrators.php'],
        ['icon' => 'people', 'text' => 'Staffs', 'link' => 'staffs.php'],
        ['icon' => 'hdd-network', 'text' => 'Agents', 'link' => 'agents.php'],
        ['icon' => 'building', 'text' => 'Departments', 'link' => 'departments.php'],
        ['icon' => 'person-badge', 'text' => 'Positions', 'link' => 'positions.php'],
        ['icon' => 'file-earmark-text', 'text' => 'Incident Types', 'link' => 'incident_types.php'],
        ['icon' => 'ticket', 'text' => 'Tickets', 'link' => 'tickets.php'],
        ['icon' => 'file-earmark-text', 'text' => 'Reports', 'link' => 'reports.php'],
        ['icon' => 'clock-history', 'text' => 'Ticket Timeline', 'link' => '/ticket_timeline.php'],
    ];

    $sidebar = '<div class="sidebar" id="sidebar">';
    $sidebar .= '<div class="logo-container">';
    $sidebar .= '<a href="/dashboard.php" class="text-decoration-none">';
    $sidebar .= '<img src="/assets/images/AICs.png" alt="AICS Logo" height="64">';
    $sidebar .= '<div class="logo-text text-white">AICS-TMS</div>';
    $sidebar .= '</a>';
    $sidebar .= '</div>';
    $sidebar .= '<ul class="nav nav-pills flex-column mb-auto">';

    foreach ($menu_items as $item) {
        $is_active = $active_page === $item['text'] ? 'active' : '';
        $sidebar .= '<li class="nav-item">';
        $sidebar .= "<a href=\"{$item['link']}\" class=\"nav-link {$is_active} text-white\">";
        $sidebar .= "<i class=\"bi bi-{$item['icon']} me-2\"></i>{$item['text']}</a>";
        $sidebar .= '</li>';
    }

    $sidebar .= '</ul>';
    $sidebar .= '</div>';

    return $sidebar;
}

function render_topnav($user)
{
    $topnav = '<nav class="navbar navbar-expand-lg navbar-light">';
    $topnav .= '<div class="container-fluid">';
    $topnav .= '<button class="btn btn-purple" id="sidebarToggle">';
    $topnav .= '<i class="bi bi-list"></i>';
    $topnav .= '</button>';
    $topnav .= '<div class="d-flex align-items-center">';
    $topnav .= '<div class="position-relative me-3">';
    $topnav .= '<i class="bi bi-bell fs-5"></i>';
    $topnav .= '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3<span class="visually-hidden">unread messages</span></span>';
    $topnav .= '</div>';
    $topnav .= '<div class="dropdown">';
    $topnav .= '<a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">';
    $topnav .= '<div class="profile-picture me-2">' . strtoupper(substr($user['username'], 0, 1)) . '</div>';
    $topnav .= '<strong>' . htmlspecialchars($user['username']) . '</strong>';
    $topnav .= '</a>';
    $topnav .= '<ul class="dropdown-menu dropdown-menu-end text-small shadow" aria-labelledby="dropdownUser1">';
    $topnav .= '<li><a class="dropdown-item" href="#">Profile</a></li>';
    $topnav .= '<li><a class="dropdown-item" href="#">Settings</a></li>';
    $topnav .= '<li><hr class="dropdown-divider"></li>';
    $topnav .= '<li>';
    $topnav .= '<form action="https://' . $_SERVER['HTTP_HOST'] . '/logout.php" method="POST">';
    $topnav .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
    $topnav .= '<button type="submit" class="dropdown-item">Logout</button>';
    $topnav .= '</form>';
    $topnav .= '</li>';
    $topnav .= '</ul>';
    $topnav .= '</div>';
    $topnav .= '</div>';
    $topnav .= '</div>';
    $topnav .= '</nav>';

    return $topnav;
}

function render_page($title, $content, $active_page = '')
{
    // Fetch user data (assuming it's stored in the session)
    $user = [
        'username' => $_SESSION['username'] ?? 'Unknown User',
    ];

    $page = '<!DOCTYPE html>';
    $page .= '<html lang="en">';
    $page .= '<head>';
    $page .= '<meta charset="UTF-8">';
    $page .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    $page .= '<meta name="csrf-token" content="' . htmlspecialchars(generate_csrf_token()) . '">';
    $page .= '<title>' . htmlspecialchars($title) . ' - AICS-TMS</title>';
    $page .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">';
    $page .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">';
    $page .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">';
    $page .= '<link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap5.min.css">';
    $page .= '<link rel="stylesheet" href="/assets/css/admin.css">';
    $page .= '</head>';
    $page .= '<body>';
    $page .= render_sidebar($active_page);
    $page .= '<div class="main-content" id="main-content">';
    $page .= render_topnav($user);
    $page .= '<div class="container-fluid py-4">';
    $page .= $content;
    $page .= '</div>'; // container-fluid
    $page .= '</div>'; // main-content
    $page .= '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>';
    $page .= '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
    $page .= '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
    $page .= '<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>';
    $page .= '<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap5.min.js"></script>';
    $page .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js"></script>';
    $page .= '<script src="/assets/js/admin.js"></script>';
    $page .= '<script src="/assets/js/idle_logout.js"></script>';
    $page .= '</body>';
    $page .= '</html>';

    return $page;
}

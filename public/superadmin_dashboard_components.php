<?php
// superdashboard_components.php

// Include auth middleware and check if user is an admin
require_once 'auth_middleware.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

function render_dashboard_stats($total_number_users, $total_admins, $total_users, $total_online_users, $total_agents, $total_active_agents)
{
    $content = '<div class="row mb-4">';
    $content .= stat_card('Total Users', $total_number_users, 'bg-primary');
    $content .= stat_card('Total Admins', $total_admins, 'bg-success');
    $content .= stat_card('Total Staffs', $total_users, 'bg-info');
    $content .= stat_card('Total Online Users', $total_online_users, 'bg-secondary');
    $content .= stat_card('Total Agents', $total_agents, 'bg-warning');
    $content .= stat_card('Total Active Agents', $total_active_agents, 'bg-danger');
    $content .= '</div>';
    return $content;
}

function stat_card($title, $value, $bg_class)
{
    return "<div class='col-md-4 mb-4'>
                <div class='card {$bg_class} text-white h-100'>
                    <div class='card-body'>
                        <h5 class='card-title'>{$title}</h5>
                        <p class='card-text display-4'>{$value}</p>
                    </div>
                </div>
            </div>";
}

function render_users_table($users, $departments, $positions)
{
    $content = '<div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">All Users</h6>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Add New User</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Account Status</th>
                            <th>Online Status</th>
                            <th>First-Time Login</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($users as $usr) {
        $content .= render_user_row($usr);
    }

    $content .= '</tbody></table></div></div></div>';
    $content .= render_edit_user_modal($departments, $positions);
    $content .= render_add_user_modal($departments, $positions);

    return $content;
}

function get_status_badge($status) {
    switch ($status) {
        case 'active':
            return '<span class="badge bg-success">Active</span>';
        case 'inactive':
            return '<span class="badge bg-danger">Inactive</span>';
        case 'pending':
            return '<span class="badge bg-warning">Pending</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

function render_user_row($usr)
{
    // Check if this is the only super admin
    $isSoleSuper = false;
    $isCurrentUserSuper = isset($_SESSION['role']) && $_SESSION['role'] === 'super';
    
    if ($usr['role'] === 'super') {
        global $pdo;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = "super"');
        $stmt->execute();
        $isSoleSuper = ($stmt->fetchColumn() <= 1);
    }

    $content = '<tr>';
    $content .= '<td>' . htmlspecialchars($usr['user_id']) . '</td>';
    $content .= '<td>' . htmlspecialchars($usr['username']) . '</td>';
    $content .= '<td>' . htmlspecialchars($usr['email']) . '</td>';
    $content .= '<td>' . htmlspecialchars(ucfirst($usr['role'])) . '</td>';
    $content .= '<td>' . htmlspecialchars($usr['department_name'] ?? 'N/A') . '</td>';
    $content .= '<td>' . htmlspecialchars($usr['position_name'] ?? 'N/A') . '</td>';
    $content .= '<td>' . get_status_badge($usr['is_active']) . '</td>';
    $content .= '<td>' . ($usr['is_online'] ? '<span class="badge bg-success">Online</span>' : '<span class="badge bg-danger">Offline</span>') . '</td>';
    $content .= '<td>';
    $content .= $usr['two_factor_enabled'] && $usr['security_question'] ? 
               '<span class="badge bg-success">Completed</span>' : 
               '<span class="badge bg-warning">Pending</span>';
    $content .= '</td>';
    $content .= '<td>' . (isset($usr['last_login']) ? htmlspecialchars($usr['last_login']) : 'N/A') . '</td>';
    $content .= '<td>';
    
    // Action buttons
    $content .= '<div class="btn-group" role="group">';
    
    // Edit button - shown for all users
    $content .= '<button class="btn btn-sm btn-primary me-1 edit-user-btn" ' .
                'data-user-id="' . $usr['user_id'] . '" ' .
                'data-is-super="' . ($usr['role'] === 'super' ? 'true' : 'false') . '" ' .
                'data-is-sole-super="' . ($isSoleSuper ? 'true' : 'false') . '">' .
                'Edit</button>';
    
    // Only show toggle and delete buttons if:
    // 1. Current user is super admin AND
    // 2. Target user is not the sole super admin
    if ($isCurrentUserSuper && !$isSoleSuper) {
        // Status toggle button
        $content .= '<button class="btn btn-sm ' . 
                   ($usr['is_active'] === 'active' ? 'btn-warning' : 'btn-success') . 
                   ' me-1 toggle-status-btn" ' .
                   'data-user-id="' . $usr['user_id'] . '" ' .
                   'data-current-status="' . $usr['is_active'] . '">' .
                   ($usr['is_active'] === 'active' ? 'Deactivate' : 'Activate') .
                   '</button>';
        
        // Delete button - don't show for sole super admin
        if ($usr['role'] !== 'super' || !$isSoleSuper) {
            $content .= '<button class="btn btn-sm btn-danger delete-user-btn" ' .
                       'data-user-id="' . $usr['user_id'] . '" ' .
                       'data-username="' . htmlspecialchars($usr['username']) . '">' .
                       'Delete</button>';
        }
    }
    
    $content .= '</div>';
    $content .= '</td></tr>';
    return $content;
}
function render_edit_user_modal($departments, $positions)
{
    $content = '
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="user_id">
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                                <option value="super">Super Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editDepartment" class="form-label">Department</label>
                            <select class="form-select" id="editDepartment" name="department_id">
                                <option value="">Select Department</option>';
    
    foreach ($departments as $dept) {
        $content .= '<option value="' . $dept['department_id'] . '">' . 
                   htmlspecialchars($dept['department_name']) . '</option>';
    }

    $content .= '
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editPosition" class="form-label">Position</label>
                            <select class="form-select" id="editPosition" name="position_id">
                                <option value="">Select Position</option>';

    foreach ($positions as $pos) {
        $content .= '<option value="' . $pos['position_id'] . '">' . 
                   htmlspecialchars($pos['position_name']) . '</option>';
    }

    $content .= '
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="is_active">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="editPassword" name="password">
                            <div id="editStrengthIndicator" class="progress" style="height: 5px; margin-top: 5px;">
                                <div class="progress-bar" role="progressbar" style="width: 0%; transition: width 0.3s ease"></div>
                            </div>
                            <small class="text-muted">
                                Password must be at least 12 characters long and contain lowercase, uppercase, numbers, and special characters ($@#&!)
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="editConfirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="editConfirmPassword" name="confirm_password">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateUser()">Save changes</button>
                </div>
            </div>
        </div>
    </div>';

    return $content;
}

function render_add_user_modal($departments, $positions)
{
    $content = '
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label for="addUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="addUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="addEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="addEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="addRole" class="form-label">Role</label>
                            <select class="form-select" id="addRole" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                                <option value="super">Super Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addDepartment" class="form-label">Department</label>
                            <select class="form-select" id="addDepartment" name="department_id">
                                <option value="">Select Department</option>';

    foreach ($departments as $dept) {
        $content .= '<option value="' . $dept['department_id'] . '">' . htmlspecialchars($dept['department_name']) . '</option>';
    }

    $content .= '
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addPosition" class="form-label">Position</label>
                            <select class="form-select" id="addPosition" name="position_id">
                                <option value="">Select Position</option>';

    foreach ($positions as $pos) {
        $content .= '<option value="' . $pos['position_id'] . '">' . htmlspecialchars($pos['position_name']) . '</option>';
    }

    $content .= '
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="addPassword" name="password" required>
                            <div id="addStrengthIndicator" class="progress" style="height: 5px; margin-top: 5px;">
                                <div class="progress-bar" role="progressbar" style="width: 0%; transition: width 0.3s ease"></div>
                            </div>
                            <small class="text-muted">
                                Password must be at least 12 characters long and contain lowercase, uppercase, numbers, and special characters ($@#&!)
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="addConfirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="addConfirmPassword" name="confirm_password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="addUser()">Add User</button>
                </div>
            </div>
        </div>
    </div>';

    return $content;
}
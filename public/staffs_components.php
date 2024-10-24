<?php
// staffs_components.php

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

function render_staffs_table($staffs)
{
    $table = '<div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">List of Staff</h6>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">Add Staff</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="staffsTable">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Account Status</th>
                            <th>Online Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($staffs as $staff) {
        $table .= render_staff_row($staff);
    }

    $table .= '</tbody></table></div></div></div>';

    return $table;
}

function render_staff_row($staff)
{
    $row = '<tr>';
    $row .= '<td>' . htmlspecialchars($staff['user_id']) . '</td>';
    $row .= '<td>' . htmlspecialchars($staff['username']) . '</td>';
    $row .= '<td>' . htmlspecialchars($staff['email']) . '</td>';
    $row .= '<td>' . htmlspecialchars($staff['department_name'] ?? 'N/A') . '</td>';
    $row .= '<td>' . htmlspecialchars($staff['position_name'] ?? 'N/A') . '</td>';
    $row .= '<td>' . get_status_badge($staff['is_active']) . '</td>';
    $row .= '<td>';
    $row .= $staff['is_online'] ? '<span class="badge bg-success">Online</span>' : '<span class="badge bg-danger">Offline</span>';
    $row .= '</td>';
    $row .= '<td>' . htmlspecialchars($staff['last_login'] ?? 'N/A') . '</td>';
    $row .= '<td>';
    $row .= '<div class="btn-group" role="group">';
    
    // Edit button
    $row .= '<button class="btn btn-sm btn-primary me-1 edit-staff-btn" ' .
            'data-user-id="' . htmlspecialchars($staff['user_id']) . '">Edit</button>';
    
    // Status toggle button
    $row .= '<button class="btn btn-sm ' . 
            ($staff['is_active'] === 'active' ? 'btn-warning' : 'btn-success') . 
            ' me-1 toggle-status-btn" ' .
            'data-user-id="' . htmlspecialchars($staff['user_id']) . '" ' .
            'data-current-status="' . htmlspecialchars($staff['is_active']) . '">' .
            ($staff['is_active'] === 'active' ? 'Deactivate' : 'Activate') .
            '</button>';
    
    // Delete button
    $row .= '<button class="btn btn-sm btn-danger delete-staff-btn" ' .
            'data-user-id="' . htmlspecialchars($staff['user_id']) . '" ' .
            'data-username="' . htmlspecialchars($staff['username']) . '">Delete</button>';
    
    $row .= '</div>';
    $row .= '</td></tr>';

    return $row;
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

function render_add_staff_modal($departments, $positions)
{
    $modal = '<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStaffModalLabel">Add New Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStaffForm">
                        <div class="mb-3">
                            <label for="addUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="addUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="addEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="addEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="addDepartment" class="form-label">Department</label>
                            <select class="form-select" id="addDepartment" name="department_id">
                                <option value="">Select Department</option>';
    foreach ($departments as $dept) {
        $modal .= '<option value="' . $dept['department_id'] . '">' . htmlspecialchars($dept['department_name']) . '</option>';
    }
    $modal .= '</select>
                        </div>
                        <div class="mb-3">
                            <label for="addPosition" class="form-label">Position</label>
                            <select class="form-select" id="addPosition" name="position_id">
                                <option value="">Select Position</option>';
    foreach ($positions as $pos) {
        $modal .= '<option value="' . $pos['position_id'] . '">' . htmlspecialchars($pos['position_name']) . '</option>';
    }
    $modal .= '</select>
                        </div>
                        <div class="mb-3">
                            <label for="addPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="addPassword" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="addConfirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="addConfirmPassword" name="confirm_password" required>
                        </div>
                        <input type="hidden" name="role" value="user">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="addStaff()">Add Staff</button>
                </div>
            </div>
        </div>
    </div>';

    return $modal;
}

function render_edit_staff_modal($departments, $positions)
{
    $modal = '<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStaffModalLabel">Edit Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editStaffForm">
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
                            <label for="editDepartment" class="form-label">Department</label>
                            <select class="form-select" id="editDepartment" name="department_id">
                                <option value="">Select Department</option>';
    foreach ($departments as $dept) {
        $modal .= '<option value="' . $dept['department_id'] . '">' . htmlspecialchars($dept['department_name']) . '</option>';
    }
    $modal .= '</select>
                        </div>
                        <div class="mb-3">
                            <label for="editPosition" class="form-label">Position</label>
                            <select class="form-select" id="editPosition" name="position_id">
                                <option value="">Select Position</option>';
    foreach ($positions as $pos) {
        $modal .= '<option value="' . $pos['position_id'] . '">' . htmlspecialchars($pos['position_name']) . '</option>';
    }
    $modal .= '</select>
                        </div>
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="editPassword" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="editConfirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="editConfirmPassword" name="confirm_password">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateStaff()">Save changes</button>
                </div>
            </div>
        </div>
    </div>';

    return $modal;
}

?>
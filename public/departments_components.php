<?php
// department_components.php

require_once 'auth_middleware.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

function render_departments_table($departments)
{
    $table = '<div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">List of Departments</h6>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">Add Department</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="departmentsTable">
                    <thead>
                        <tr>
                            <th>Department ID</th>
                            <th>Department Name</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($departments as $department) {
        $table .= render_department_row($department);
    }

    $table .= '</tbody></table></div></div></div>';

    return $table;
}

function render_department_row($department)
{
    $row = '<tr>';
    $row .= '<td>' . htmlspecialchars($department['department_id']) . '</td>';
    $row .= '<td>' . htmlspecialchars($department['department_name']) . '</td>';
    $row .= '<td>';
    if ($department['status'] === 'active') {
        $row .= '<span class="badge bg-success">Active</span>';
    } else {
        $row .= '<span class="badge bg-danger">Inactive</span>';
    }
    $row .= '</td>';
    $row .= '<td>' . htmlspecialchars($department['created_at']) . '</td>';
    $row .= '<td>' . htmlspecialchars($department['updated_at']) . '</td>';
    $row .= '<td>';
    $row .= '<button class="btn btn-sm btn-primary me-1 edit-department-btn" data-department-id="' . $department['department_id'] . '">Edit</button>';
    $row .= '<button class="btn btn-sm ' . ($department['status'] === 'active' ? 'btn-warning' : 'btn-success') . ' me-1 toggle-status-btn" data-department-id="' . $department['department_id'] . '" data-new-status="' . ($department['status'] === 'active' ? 'inactive' : 'active') . '">';
    $row .= $department['status'] === 'active' ? 'Deactivate' : 'Activate';
    $row .= '</button>';
    $row .= '<button class="btn btn-sm btn-danger delete-department-btn" data-department-id="' . $department['department_id'] . '">Delete</button>';
    $row .= '</td>';
    $row .= '</tr>';

    return $row;
}

function render_add_department_modal()
{
    $modal = '<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDepartmentModalLabel">Add New Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addDepartmentForm">
                        <div class="mb-3">
                            <label for="addDepartmentName" class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="addDepartmentName" name="department_name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="addDepartment()">Add Department</button>
                </div>
            </div>
        </div>
    </div>';

    return $modal;
}

function render_edit_department_modal()
{
    $modal = '<div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDepartmentModalLabel">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editDepartmentForm">
                        <input type="hidden" id="editDepartmentId" name="department_id">
                        <div class="mb-3">
                            <label for="editDepartmentName" class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="editDepartmentName" name="department_name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateDepartment()">Save changes</button>
                </div>
            </div>
        </div>
    </div>';

    return $modal;
}
?>
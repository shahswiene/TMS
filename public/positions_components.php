<?php
// positions_components.php

require_once 'auth_middleware.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

function render_positions_table($positions)
{
    $table = '<div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">List of Positions</h6>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPositionModal">Add Position</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="positionsTable">
                    <thead>
                        <tr>
                            <th>Position ID</th>
                            <th>Position Name</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($positions as $position) {
        $table .= render_position_row($position);
    }

    $table .= '</tbody></table></div></div></div>';

    return $table;
}

function render_position_row($position)
{
    $row = '<tr>';
    $row .= '<td>' . htmlspecialchars($position['position_id']) . '</td>';
    $row .= '<td>' . htmlspecialchars($position['position_name']) . '</td>';
    $row .= '<td>';
    if ($position['status'] === 'active') {
        $row .= '<span class="badge bg-success">Active</span>';
    } else {
        $row .= '<span class="badge bg-danger">Inactive</span>';
    }
    $row .= '</td>';
    $row .= '<td>' . htmlspecialchars($position['created_at']) . '</td>';
    $row .= '<td>' . htmlspecialchars($position['updated_at']) . '</td>';
    $row .= '<td>';
    $row .= '<button class="btn btn-sm btn-primary me-1 edit-position-btn" data-position-id="' . $position['position_id'] . '">Edit</button>';
    $row .= '<button class="btn btn-sm ' . ($position['status'] === 'active' ? 'btn-warning' : 'btn-success') . ' me-1 toggle-status-btn" data-position-id="' . $position['position_id'] . '" data-new-status="' . ($position['status'] === 'active' ? 'inactive' : 'active') . '">';
    $row .= $position['status'] === 'active' ? 'Deactivate' : 'Activate';
    $row .= '</button>';
    $row .= '<button class="btn btn-sm btn-danger delete-position-btn" data-position-id="' . $position['position_id'] . '">Delete</button>';
    $row .= '</td>';
    $row .= '</tr>';

    return $row;
}

function render_add_position_modal()
{
    $modal = '<div class="modal fade" id="addPositionModal" tabindex="-1" aria-labelledby="addPositionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPositionModalLabel">Add New Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addPositionForm">
                        <div class="mb-3">
                            <label for="addPositionName" class="form-label">Position Name</label>
                            <input type="text" class="form-control" id="addPositionName" name="position_name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="addPosition()">Add Position</button>
                </div>
            </div>
        </div>
    </div>';

    return $modal;
}

function render_edit_position_modal()
{
    $modal = '<div class="modal fade" id="editPositionModal" tabindex="-1" aria-labelledby="editPositionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPositionModalLabel">Edit Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPositionForm">
                        <input type="hidden" id="editPositionId" name="position_id">
                        <div class="mb-3">
                            <label for="editPositionName" class="form-label">Position Name</label>
                            <input type="text" class="form-control" id="editPositionName" name="position_name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updatePosition()">Save changes</button>
                </div>
            </div>
        </div>
    </div>';

    return $modal;
}
?>
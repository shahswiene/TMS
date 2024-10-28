<?php
// incident_types_components.php

require_once 'auth_middleware.php';

if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

function render_incident_types_stats($pdo)
{
    // Get statistics
    $stats = [
        'total' => $pdo->query('SELECT COUNT(*) FROM incident_types')->fetchColumn(),
        'active' => $pdo->query('SELECT COUNT(*) FROM incident_types WHERE status = "active"')->fetchColumn(),
        'inactive' => $pdo->query('SELECT COUNT(*) FROM incident_types WHERE status = "inactive"')->fetchColumn(),
        'used' => $pdo->query('
            SELECT COUNT(DISTINCT incident_type_id) 
            FROM tickets 
            WHERE incident_type_id IN (SELECT incident_type_id FROM incident_types)
        ')->fetchColumn()
    ];

    return '<div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Types</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">' . $stats['total'] . '</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-folder h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Types</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">' . $stats['active'] . '</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Inactive Types</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">' . $stats['inactive'] . '</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-x-circle h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Types In Use</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">' . $stats['used'] . '</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-gear-fill h2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}

function render_incident_types_table($incident_types)
{
    $table = '<div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">List of Incident Types</h6>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIncidentTypeModal">
                <i class="bi bi-plus-circle me-2"></i>Add Incident Type
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="incidentTypesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type Name</th>
                            <th>Description</th>
                            <th>Default Priority</th>
                            <th>SLA Hours</th>
                            <th>Status</th>
                            <th>Usage Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($incident_types as $type) {
        $table .= render_incident_type_row($type);
    }

    $table .= '</tbody></table></div></div></div>';
    return $table;
}

function render_incident_type_row($type)
{
    $priority_badges = [
        'low' => 'bg-success',
        'medium' => 'bg-warning text-dark',
        'high' => 'bg-orange',
        'critical' => 'bg-danger'
    ];

    $status_badges = [
        'active' => 'bg-success',
        'inactive' => 'bg-danger'
    ];

    $row = '<tr>';
    $row .= '<td>' . htmlspecialchars($type['incident_type_id']) . '</td>';
    $row .= '<td>' . htmlspecialchars($type['type_name']) . '</td>';
    $row .= '<td>' . htmlspecialchars(substr($type['description'], 0, 50)) .
        (strlen($type['description']) > 50 ? '...' : '') . '</td>';
    $row .= '<td><span class="badge ' .
        ($priority_badges[$type['default_priority']] ?? 'bg-secondary') . '">' .
        htmlspecialchars(ucfirst($type['default_priority'])) . '</span></td>';
    $row .= '<td>' . htmlspecialchars($type['default_sla_hours']) . ' hours</td>';
    $row .= '<td><span class="badge ' .
        ($status_badges[$type['status']] ?? 'bg-secondary') . '">' .
        htmlspecialchars(ucfirst($type['status'])) . '</span></td>';
    $row .= '<td>' . htmlspecialchars($type['ticket_count']) . '</td>';

    $row .= '<td>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-primary me-1 edit-type-btn" 
                    data-type-id="' . htmlspecialchars($type['incident_type_id']) . '">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm ' .
        ($type['status'] === 'active' ? 'btn-warning' : 'btn-success') . ' me-1 toggle-status-btn" 
                    data-type-id="' . htmlspecialchars($type['incident_type_id']) . '" 
                    data-current-status="' . htmlspecialchars($type['status']) . '">
                <i class="bi bi-' . ($type['status'] === 'active' ? 'x-circle' : 'check-circle') . '"></i>
            </button>';

    // Only show delete button if type is not in use
    if ($type['ticket_count'] == 0) {
        $row .= '<button type="button" class="btn btn-sm btn-danger delete-type-btn" 
                    data-type-id="' . htmlspecialchars($type['incident_type_id']) . '" 
                    data-type-name="' . htmlspecialchars($type['type_name']) . '">
                <i class="bi bi-trash"></i>
            </button>';
    }

    $row .= '</div></td></tr>';

    return $row;
}

function render_add_incident_type_modal()
{
    return '<div class="modal fade" id="addIncidentTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Incident Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addIncidentTypeForm" onsubmit="event.preventDefault(); createIncidentType();">
                        <div class="mb-3">
                            <label for="typeName" class="form-label">Type Name</label>
                            <input type="text" class="form-control" id="typeName" name="type_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="defaultPriority" class="form-label">Default Priority</label>
                            <select class="form-select" id="defaultPriority" name="default_priority" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="defaultSlaHours" class="form-label">Default SLA Hours</label>
                            <input type="number" class="form-control" id="defaultSlaHours" name="default_sla_hours" 
                                   min="1" max="168" value="24" required>
                            <small class="text-muted">Maximum 168 hours (1 week)</small>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="createTypeButton">Create Type</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>';
}

function render_edit_incident_type_modal()
{
    return '<div class="modal fade" id="editIncidentTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Incident Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editIncidentTypeForm" onsubmit="event.preventDefault(); updateIncidentType();">
                        <input type="hidden" id="editTypeId" name="incident_type_id">

                        <div class="mb-3">
                            <label for="editTypeName" class="form-label">Type Name</label>
                            <input type="text" class="form-control" id="editTypeName" name="type_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="3" required></textarea>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editDefaultPriority" class="form-label">Default Priority</label>
                            <select class="form-select" id="editDefaultPriority" name="default_priority" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editDefaultSlaHours" class="form-label">Default SLA Hours</label>
                            <input type="number" class="form-control" id="editDefaultSlaHours" name="default_sla_hours" 
                                   min="1" max="168" required>
                            <small class="text-muted">Maximum 168 hours (1 week)</small>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="updateTypeButton">Update Type</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>';
}

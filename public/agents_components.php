<?php
// agents_components.php

require_once 'auth_middleware.php';

// Ensure the user is authenticated and has the correct role
if (!check_auth_and_redirect() || $_SESSION['role'] !== 'super') {
    safe_redirect('https://' . $_SERVER['HTTP_HOST'] . '/login.php');
    exit;
}

function render_agents_table($agents)
{
    $table = '<div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">List of Agents</h6>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAgentModal">Add Agent</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="agentsTable">
                    <thead>
                        <tr>
                            <th>Agent ID</th>
                            <th>Name</th>
                            <th>IP Address</th>
                            <th>Agent Group</th>
                            <th>Operating System</th>
                            <th>Cluster Node</th>
                            <th>Version</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($agents as $agent) {
        $table .= render_agent_row($agent);
    }

    $table .= '</tbody></table></div></div></div>';

    return $table;
}

function render_agent_row($agent)
{
    $row = '<tr>';
    $row .= '<td>' . htmlspecialchars($agent['agent_id']) . '</td>';
    $row .= '<td>' . htmlspecialchars($agent['name']) . '</td>';
    $row .= '<td>' . htmlspecialchars($agent['ip_address']) . '</td>';
    $row .= '<td>' . htmlspecialchars($agent['agent_group']) . '</td>';
    $row .= '<td>' . htmlspecialchars($agent['operating_system']) . '</td>';
    $row .= '<td>' . htmlspecialchars($agent['cluster_node']) . '</td>';
    $row .= '<td>' . htmlspecialchars($agent['version']) . '</td>';
    $row .= '<td>' . htmlspecialchars($agent['registration_date']) . '</td>';
    $row .= '<td>';
    if ($agent['status'] === 'active') {
        $row .= '<span class="badge bg-success">Active</span>';
    } else {
        $row .= '<span class="badge bg-danger">Inactive</span>';
    }
    $row .= '</td>';
    $row .= '<td>';
    $row .= '<button class="btn btn-sm btn-primary me-1 edit-agent-btn" data-agent-id="' . $agent['agent_id'] . '">Edit</button>';
    $row .= '<button class="btn btn-sm ' . ($agent['status'] === 'active' ? 'btn-warning' : 'btn-success') . ' me-1 toggle-status-btn" data-agent-id="' . $agent['agent_id'] . '" data-new-status="' . ($agent['status'] === 'active' ? 'inactive' : 'active') . '">';
    $row .= $agent['status'] === 'active' ? 'Deactivate' : 'Activate';
    $row .= '</button>';
    $row .= '<button class="btn btn-sm btn-danger delete-agent-btn" data-agent-id="' . $agent['agent_id'] . '">Delete</button>';
    $row .= '</td>';
    $row .= '</tr>';

    return $row;
}

function render_add_agent_modal()
{
    $modal = '<div class="modal fade" id="addAgentModal" tabindex="-1" aria-labelledby="addAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAgentModalLabel">Add New Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addAgentForm">
                        <div class="mb-3">
                            <label for="addName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="addName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="addIpAddress" class="form-label">IP Address</label>
                            <input type="text" class="form-control" id="addIpAddress" name="ip_address" required>
                        </div>
                        <div class="mb-3">
                            <label for="addAgentGroup" class="form-label">Agent Group</label>
                            <input type="text" class="form-control" id="addAgentGroup" name="agent_group">
                        </div>
                        <div class="mb-3">
                            <label for="addOperatingSystem" class="form-label">Operating System</label>
                            <input type="text" class="form-control" id="addOperatingSystem" name="operating_system">
                        </div>
                        <div class="mb-3">
                            <label for="addClusterNode" class="form-label">Cluster Node</label>
                            <input type="text" class="form-control" id="addClusterNode" name="cluster_node">
                        </div>
                        <div class="mb-3">
                            <label for="addVersion" class="form-label">Version</label>
                            <input type="text" class="form-control" id="addVersion" name="version">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="addAgent()">Add Agent</button>
                </div>
            </div>
        </div>
    </div>';

    return $modal;
}

function render_edit_agent_modal()
{
    $modal = '<div class="modal fade" id="editAgentModal" tabindex="-1" aria-labelledby="editAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAgentModalLabel">Edit Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editAgentForm">
                        <input type="hidden" id="editAgentId" name="agent_id">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editIpAddress" class="form-label">IP Address</label>
                            <input type="text" class="form-control" id="editIpAddress" name="ip_address" required>
                        </div>
                        <div class="mb-3">
                            <label for="editAgentGroup" class="form-label">Agent Group</label>
                            <input type="text" class="form-control" id="editAgentGroup" name="agent_group">
                        </div>
                        <div class="mb-3">
                            <label for="editOperatingSystem" class="form-label">Operating System</label>
                            <input type="text" class="form-control" id="editOperatingSystem" name="operating_system">
                        </div>
                        <div class="mb-3">
                            <label for="editClusterNode" class="form-label">Cluster Node</label>
                            <input type="text" class="form-control" id="editClusterNode" name="cluster_node">
                        </div>
                        <div class="mb-3">
                            <label for="editVersion" class="form-label">Version</label>
                            <input type="text" class="form-control" id="editVersion" name="version">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateAgent()">Save changes</button>
                </div>
            </div>
        </div>
    </div>';

    return $modal;
}

?>
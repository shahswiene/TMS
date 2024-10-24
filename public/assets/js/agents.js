// agents.js

document.addEventListener('DOMContentLoaded', function() {
    initializeDataTable();
    initializeEventListeners();
});

function initializeDataTable() {
    const table = document.getElementById("agentsTable");
    if (table) {
        $(table).DataTable({
            "pageLength": 10,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pagingType": "full_numbers",
            "language": {
                "paginate": {
                    "first": "&laquo;",
                    "previous": "&lsaquo;",
                    "next": "&rsaquo;",
                    "last": "&raquo;"
                }
            },
            "drawCallback": function(settings) {
                var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                pagination.toggle(this.api().page.info().pages > 1);
                pagination.addClass('d-flex justify-content-center');
            }
        });
    }
}

function initializeEventListeners() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-agent-btn')) {
            const agentId = e.target.getAttribute('data-agent-id');
            populateEditAgentModal(agentId);
        } else if (e.target.classList.contains('toggle-status-btn')) {
            const agentId = e.target.getAttribute('data-agent-id');
            const newStatus = e.target.getAttribute('data-new-status');
            toggleAgentStatus(agentId, newStatus);
        } else if (e.target.classList.contains('delete-agent-btn')) {
            const agentId = e.target.getAttribute('data-agent-id');
            deleteAgent(agentId);
        }
    });
}

function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : null;
}

function populateEditAgentModal(agentId) {
    fetch(`get_agent_details.php?id=${agentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("editAgentId").value = data.data.agent_id;
                document.getElementById("editName").value = data.data.name;
                document.getElementById("editIpAddress").value = data.data.ip_address;
                document.getElementById("editAgentGroup").value = data.data.agent_group;
                document.getElementById("editOperatingSystem").value = data.data.operating_system;
                document.getElementById("editClusterNode").value = data.data.cluster_node;
                document.getElementById("editVersion").value = data.data.version;
                new bootstrap.Modal(document.getElementById("editAgentModal")).show();
            } else {
                Swal.fire("Error", data.message, "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            Swal.fire("Error", "An unexpected error occurred", "error");
        });
}

function toggleAgentStatus(agentId, newStatus) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }

    fetch("toggle_agent_status.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            agent_id: agentId,
            status: newStatus,
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire("Success", data.message, "success").then(() => {
                location.reload();
            });
        } else {
            Swal.fire("Error", data.message, "error");
        }
    })
    .catch(error => {
        console.error("Error:", error);
        Swal.fire("Error", "An unexpected error occurred", "error");
    });
}

function addAgent() {
    const form = document.getElementById('addAgentForm');
    const formData = new FormData(form);
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }
    formData.append("csrf_token", csrfToken);

    fetch("add_agent.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire("Success", "Agent added successfully", "success").then(() => {
                location.reload();
            });
        } else {
            Swal.fire("Error", data.message, "error");
        }
    })
    .catch(error => {
        console.error("Error:", error);
        Swal.fire("Error", "An unexpected error occurred", "error");
    });
}

function updateAgent() {
    const form = document.getElementById('editAgentForm');
    const formData = new FormData(form);
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }
    formData.append("csrf_token", csrfToken);

    fetch("update_agent.php", {
        method: "POST",
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire("Success", "Agent updated successfully", "success").then(() => {
                location.reload();
            });
        } else {
            Swal.fire("Error", data.message || "An error occurred while updating the agent", "error");
        }
    })
    .catch(error => {
        console.error("Error:", error);
        Swal.fire("Error", "An unexpected error occurred. Please try again.", "error");
    });
}

function deleteAgent(agentId) {
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {
        if (result.isConfirmed) {
            const csrfToken = getCsrfToken();
            if (!csrfToken) {
                Swal.fire("Error", "CSRF token not found", "error");
                return;
            }

            fetch("delete_agent.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    agent_id: agentId,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire("Deleted!", data.message, "success").then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire("Error", data.message, "error");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                Swal.fire("Error", "An unexpected error occurred", "error");
            });
        }
    });
}
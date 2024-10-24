// positions.js

document.addEventListener('DOMContentLoaded', function() {
    initializeDataTable();
    initializeEventListeners();
});

function initializeDataTable() {
    const table = document.getElementById("positionsTable");
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
        if (e.target.classList.contains('edit-position-btn')) {
            const positionId = e.target.getAttribute('data-position-id');
            populateEditPositionModal(positionId);
        } else if (e.target.classList.contains('toggle-status-btn')) {
            const positionId = e.target.getAttribute('data-position-id');
            const newStatus = e.target.getAttribute('data-new-status');
            togglePositionStatus(positionId, newStatus);
        } else if (e.target.classList.contains('delete-position-btn')) {
            const positionId = e.target.getAttribute('data-position-id');
            deletePosition(positionId);
        }
    });
}

function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : null;
}

function populateEditPositionModal(positionId) {
    fetch(`get_position_details.php?id=${positionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("editPositionId").value = data.data.position_id;
                document.getElementById("editPositionName").value = data.data.position_name;
                new bootstrap.Modal(document.getElementById("editPositionModal")).show();
            } else {
                Swal.fire("Error", data.message, "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            Swal.fire("Error", "An unexpected error occurred", "error");
        });
}

function togglePositionStatus(positionId, newStatus) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }

    fetch("toggle_position_status.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            position_id: positionId,
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

function addPosition() {
    const form = document.getElementById('addPositionForm');
    const formData = new FormData(form);
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }
    formData.append("csrf_token", csrfToken);

    fetch("add_position.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire("Success", "Position added successfully", "success").then(() => {
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

function updatePosition() {
    const form = document.getElementById('editPositionForm');
    const formData = new FormData(form);
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }
    formData.append("csrf_token", csrfToken);

    fetch("update_position.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire("Success", "Position updated successfully", "success").then(() => {
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

function deletePosition(positionId) {
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

            fetch("delete_position.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    position_id: positionId,
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
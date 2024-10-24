// administrators.js

document.addEventListener('DOMContentLoaded', function() {
    initializeDataTable();
    initializeEventListeners();
});

function initializeDataTable() {
    const table = document.getElementById("administratorsTable");
    if (table) {
        $(table).DataTable({
            "pageLength": 10,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                   '<"row"<"col-sm-12"tr>>' +
                   '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
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
        if (e.target.classList.contains('edit-admin-btn')) {
            const userId = e.target.getAttribute('data-user-id');
            populateEditAdministratorModal(userId);
        } else if (e.target.classList.contains('toggle-status-btn')) {
            const userId = e.target.getAttribute('data-user-id');
            const currentStatus = e.target.getAttribute('data-current-status'); // Changed from newStatus
            toggleUserStatus(userId, currentStatus);
        } else if (e.target.classList.contains('delete-admin-btn')) {
            const userId = e.target.getAttribute('data-user-id');
            const username = e.target.getAttribute('data-username');
            deleteAdministrator(userId, username);
        }
    });
}

function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : null;
}

function populateEditAdministratorModal(userId) {
    fetch(`get_user_details.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("editUserId").value = data.data.user_id;
                document.getElementById("editUsername").value = data.data.username;
                document.getElementById("editEmail").value = data.data.email;
                document.getElementById("editDepartment").value = data.data.department_id;
                document.getElementById("editPosition").value = data.data.position_id;
                new bootstrap.Modal(document.getElementById("editAdministratorModal")).show();
            } else {
                Swal.fire("Error", data.message, "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            Swal.fire("Error", "An unexpected error occurred", "error");
        });
}

function toggleUserStatus(userId, currentStatus) {
    if (!userId || !currentStatus) {
        Swal.fire("Error", "User ID and current status are required", "error");
        return;
    }

    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }

    const action = currentStatus === 'active' ? 'deactivate' : 'activate';

    Swal.fire({
        title: `Are you sure?`,
        text: currentStatus === 'active' 
            ? "Administrator will be logged out and unable to access the system."
            : "Administrator will be able to log in to the system.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: `Yes, ${action} administrator`
    }).then((result) => {
        if (result.isConfirmed) {
            fetch("toggle_user_status.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    user_id: userId,
                    current_status: currentStatus,
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: "Success",
                        text: data.message,
                        icon: "success"
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire("Error", data.message || "Failed to update status", "error");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                Swal.fire("Error", "An unexpected error occurred", "error");
            });
        }
    });
}

function addAdministrator() {
    const form = document.getElementById('addAdministratorForm');
    const formData = new FormData(form);
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }
    formData.append("csrf_token", csrfToken);

    fetch("add_user.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire("Success", "Administrator added successfully", "success").then(() => {
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

function updateAdministrator() {
    const form = document.getElementById('editAdministratorForm');
    const formData = new FormData(form);
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }
    formData.append("csrf_token", csrfToken);

    fetch("update_user.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire("Success", "Administrator updated successfully", "success").then(() => {
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

function deleteAdministrator(userId, username) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }

    Swal.fire({
        title: "Are you sure?",
        text: `You are about to delete administrator "${username}". This action cannot be undone!`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete administrator",
        cancelButtonText: "No, keep administrator",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            fetch("delete_user.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    user_id: userId,
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

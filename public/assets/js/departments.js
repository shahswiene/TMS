// departments.js

document.addEventListener('DOMContentLoaded', function () {
    initializeDataTable();
    initializeEventListeners();
});

function initializeDataTable() {
    const table = document.getElementById("departmentsTable");
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
            "drawCallback": function (settings) {
                var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                pagination.toggle(this.api().page.info().pages > 1);
                pagination.addClass('d-flex justify-content-center');
            }
        });
    }
}

function initializeEventListeners() {
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('edit-department-btn')) {
            const departmentId = e.target.getAttribute('data-department-id');
            populateEditDepartmentModal(departmentId);
        } else if (e.target.classList.contains('toggle-status-btn')) {
            const departmentId = e.target.getAttribute('data-department-id');
            const newStatus = e.target.getAttribute('data-new-status');
            toggleDepartmentStatus(departmentId, newStatus);
        } else if (e.target.classList.contains('delete-department-btn')) {
            const departmentId = e.target.getAttribute('data-department-id');
            deleteDepartment(departmentId);
        }
    });
}

function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : null;
}

function populateEditDepartmentModal(departmentId) {
    fetch(`get_department_details.php?id=${departmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("editDepartmentId").value = data.data.department_id;
                document.getElementById("editDepartmentName").value = data.data.department_name;
                new bootstrap.Modal(document.getElementById("editDepartmentModal")).show();
            } else {
                Swal.fire("Error", data.message, "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            Swal.fire("Error", "An unexpected error occurred", "error");
        });
}

function toggleDepartmentStatus(departmentId, newStatus) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }

    fetch("toggle_department_status.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            department_id: departmentId,
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

function addDepartment() {
    const form = document.getElementById('addDepartmentForm');
    const formData = new FormData(form);
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }
    formData.append("csrf_token", csrfToken);

    fetch("add_department.php", {
        method: "POST",
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire("Success", "Department added successfully", "success").then(() => {
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

function updateDepartment() {
    const form = document.getElementById('editDepartmentForm');
    const formData = new FormData(form);
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }
    formData.append("csrf_token", csrfToken);

    fetch("update_department.php", {
        method: "POST",
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire("Success", "Department updated successfully", "success").then(() => {
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

function deleteDepartment(departmentId) {
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

            fetch("delete_department.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    department_id: departmentId,
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
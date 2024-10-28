document.addEventListener('DOMContentLoaded', function () {
    initializeDataTable();
    initializeEventListeners();
});

function initializeDataTable() {
    const table = document.getElementById("incidentTypesTable");
    if (table) {
        $(table).DataTable({
            "pageLength": 10,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "columnDefs": [
                { "orderable": false, "targets": -1 }
            ],
            "order": [[0, 'asc']],
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
    // Add event listener for create button
    const createBtn = document.querySelector('button[onclick="createIncidentType()"]');
    if (createBtn) {
        createBtn.addEventListener('click', createIncidentType);
    }

    document.addEventListener('click', function (e) {
        if (e.target.closest('.edit-type-btn')) {
            const typeId = e.target.closest('.edit-type-btn').dataset.typeId;
            editIncidentType(typeId);
        } else if (e.target.closest('.toggle-status-btn')) {
            const btn = e.target.closest('.toggle-status-btn');
            const typeId = btn.dataset.typeId;
            const currentStatus = btn.dataset.currentStatus;
            toggleIncidentTypeStatus(typeId, currentStatus);
        } else if (e.target.closest('.delete-type-btn')) {
            const btn = e.target.closest('.delete-type-btn');
            const typeId = btn.dataset.typeId;
            const typeName = btn.dataset.typeName;
            deleteIncidentType(typeId, typeName);
        }
    });
}

function createIncidentType(e) {
    if (e) e.preventDefault(); // Prevent default button behavior

    const form = document.getElementById('addIncidentTypeForm');
    if (!form) {
        console.error('Add Incident Type form not found');
        return;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    formData.append('csrf_token', csrfToken);

    // Find the submit button within the modal
    const submitBtn = document.querySelector('#addIncidentTypeModal .btn-primary');
    if (!submitBtn) return;

    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating...';

    fetch('create_incident_type.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Success',
                    text: data.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An unexpected error occurred', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
}
function editIncidentType(typeId) {
    fetch(`get_incident_type_details.php?id=${typeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Change from data.type to data.data to match server response
                populateEditModal(data.data);
                new bootstrap.Modal(document.getElementById('editIncidentTypeModal')).show();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An unexpected error occurred', 'error');
        });
}

function populateEditModal(type) {
    document.getElementById('editTypeId').value = type.incident_type_id;
    document.getElementById('editTypeName').value = type.type_name;
    document.getElementById('editDescription').value = type.description;
    document.getElementById('editDefaultPriority').value = type.default_priority;
    document.getElementById('editDefaultSlaHours').value = type.default_sla_hours;
    document.getElementById('editStatus').value = type.status;
}

function updateIncidentType() {
    const form = document.getElementById('editIncidentTypeForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    formData.append('csrf_token', csrfToken);

    // Disable submit button and show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

    fetch('update_incident_type.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Success',
                    text: data.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', data.message, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An unexpected error occurred', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
}

function toggleIncidentTypeStatus(typeId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';

    Swal.fire({
        title: 'Confirm Status Change',
        text: `Are you sure you want to ${action} this incident type?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: `Yes, ${action} it`
    }).then((result) => {
        if (result.isConfirmed) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('toggle_incident_type_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    incident_type_id: typeId,
                    current_status: currentStatus,
                    csrf_token: csrfToken
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Success',
                            text: data.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An unexpected error occurred', 'error');
                });
        }
    });
}

function deleteIncidentType(typeId, typeName) {
    Swal.fire({
        title: 'Delete Incident Type',
        text: `Are you sure you want to delete "${typeName}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'No, keep it'
    }).then((result) => {
        if (result.isConfirmed) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('delete_incident_type.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    incident_type_id: typeId,
                    csrf_token: csrfToken
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: data.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An unexpected error occurred', 'error');
                });
        }
    });
}
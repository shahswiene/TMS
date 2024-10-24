// superadmin_dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    initializeDataTable();
    initializeFormSubmissions();
    initializeEventListeners();
    initializePasswordValidation();
});

function initializeDataTable() {
    const table = document.getElementById("usersTable");
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

function initializeFormSubmissions() {
    handleFormSubmission("addUserForm", "add_user.php", "User added successfully");
    handleFormSubmission("editUserForm", "update_user.php", "User updated successfully");
}

function initializePasswordValidation() {
    const passwordFields = ['addPassword', 'editPassword'];
    const confirmFields = ['addConfirmPassword', 'editConfirmPassword'];

    passwordFields.forEach((fieldId, index) => {
        const passwordField = document.getElementById(fieldId);
        const confirmField = document.getElementById(confirmFields[index]);

        if (passwordField && confirmField) {
            passwordField.addEventListener('input', function() {
                validatePassword(this);
                validatePasswordMatch(this, confirmField);
            });

            confirmField.addEventListener('input', function() {
                validatePasswordMatch(passwordField, this);
            });
        }
    });
}

function validatePassword(passwordField) {
    const password = passwordField.value;
    const hasMinLength = password.length >= 12;
    const hasUpper = /[A-Z]/.test(password);
    const hasLower = /[a-z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecial = /[@$!%*?&]/.test(password);
    
    const requirements = [];
    if (!hasMinLength) requirements.push("at least 12 characters");
    if (!hasUpper) requirements.push("an uppercase letter");
    if (!hasLower) requirements.push("a lowercase letter");
    if (!hasNumber) requirements.push("a number");
    if (!hasSpecial) requirements.push("a special character");

    const isValid = requirements.length === 0;
    
    if (!isValid) {
        passwordField.setCustomValidity(`Password must contain ${requirements.join(", ")}`);
    } else {
        passwordField.setCustomValidity('');
    }
}

function validatePasswordMatch(passwordField, confirmField) {
    if (passwordField.value !== confirmField.value) {
        confirmField.setCustomValidity("Passwords do not match");
    } else {
        confirmField.setCustomValidity("");
    }
}

function initializeEventListeners() {
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-user-btn')) {
            const userId = e.target.getAttribute('data-user-id');
            const isSuper = e.target.getAttribute('data-is-super') === 'true';
            const isSoleSuper = e.target.getAttribute('data-is-sole-super') === 'true';
            populateEditUserModal(userId, isSuper, isSoleSuper);
        } else if (e.target.classList.contains('toggle-status-btn')) {
            const userId = e.target.getAttribute('data-user-id');
            const currentStatus = e.target.getAttribute('data-current-status');
            toggleUserStatus(userId, currentStatus);
        } else if (e.target.classList.contains('delete-user-btn')) {
            const userId = e.target.getAttribute('data-user-id');
            const username = e.target.getAttribute('data-username');
            deleteUser(userId, username);
        }
    });

    // Role selection handlers
    document.querySelectorAll('select[name="role"]').forEach(select => {
        select.addEventListener('change', handleRoleChange);
    });
}

function handleRoleChange(e) {
    const roleSelect = e.target;
    const form = roleSelect.closest('form');
    const isSuperRole = roleSelect.value === 'super';

    // Show warning when selecting super admin role
    if (isSuperRole) {
        Swal.fire({
            title: "Warning",
            text: "Creating a super admin user grants full system access. Are you sure?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, proceed",
            cancelButtonText: "No, change role"
        }).then((result) => {
            if (!result.isConfirmed) {
                roleSelect.value = 'admin';
            }
        });
    }
}

function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : null;
}

function handleFormSubmission(formId, actionUrl, successMessage) {
    const form = document.getElementById(formId);
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate passwords if they're present
            const passwordField = form.querySelector('input[type="password"]');
            const confirmField = form.querySelector('input[name="confirm_password"]');
            
            if (passwordField && confirmField && passwordField.value) {
                validatePassword(passwordField);
                validatePasswordMatch(passwordField, confirmField);
                
                if (passwordField.validationMessage || confirmField.validationMessage) {
                    Swal.fire("Error", passwordField.validationMessage || confirmField.validationMessage, "error");
                    return;
                }
            }

            const formData = new FormData(form);
            const csrfToken = getCsrfToken();
            if (!csrfToken) {
                Swal.fire("Error", "CSRF token not found", "error");
                return;
            }
            formData.append("csrf_token", csrfToken);

            fetch(actionUrl, {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire("Success", successMessage, "success").then(() => {
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
        });
    }
}

function populateEditUserModal(userId, isSuper, isSoleSuper) {
    fetch(`get_user_details.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.data;
                const roleSelect = document.getElementById("editRole");
                const statusSelect = document.getElementById("editStatus");
                
                // Populate form fields
                document.getElementById("editUserId").value = user.user_id;
                document.getElementById("editUsername").value = user.username;
                document.getElementById("editEmail").value = user.email;
                roleSelect.value = user.role;
                document.getElementById("editDepartment").value = user.department_id || '';
                document.getElementById("editPosition").value = user.position_id || '';
                statusSelect.value = user.is_active;
                
                // Handle super admin restrictions
                if (isSoleSuper) {
                    roleSelect.disabled = true;
                    statusSelect.disabled = true;
                    roleSelect.title = "Cannot modify the only super admin's role";
                    statusSelect.title = "Cannot modify the only super admin's status";
                } else {
                    roleSelect.disabled = false;
                    statusSelect.disabled = false;
                    roleSelect.title = "";
                    statusSelect.title = "";
                }
                
                new bootstrap.Modal(document.getElementById("editUserModal")).show();
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
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }

    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const actionText = currentStatus === 'active' ? 'deactivate' : 'activate';

    Swal.fire({
        title: `Are you sure?`,
        text: currentStatus === 'active' 
            ? "User will be logged out and unable to access the system."
            : "User will be able to log in to the system.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: `Yes, ${actionText} user`
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
    });
}

function deleteUser(userId, username) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }

    Swal.fire({
        title: "Are you sure?",
        text: `You are about to delete user "${username}". This action cannot be undone!`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete user",
        cancelButtonText: "No, keep user",
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

function updateUser() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        Swal.fire("Error", "CSRF token not found", "error");
        return;
    }
    formData.append("csrf_token", csrfToken);

    // Validate password if it's being changed
    const passwordField = form.querySelector('input[type="password"]');
    const confirmField = form.querySelector('input[name="confirm_password"]');
    
    if (passwordField && confirmField && passwordField.value) {
        validatePassword(passwordField);
        validatePasswordMatch(passwordField, confirmField);
        
        if (passwordField.validationMessage || confirmField.validationMessage) {
            Swal.fire("Error", passwordField.validationMessage || confirmField.validationMessage, "error");
            return;
        }
    }

    fetch("update_user.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire("Success", "User updated successfully", "success").then(() => {
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
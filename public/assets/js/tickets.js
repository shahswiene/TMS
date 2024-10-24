document.addEventListener('DOMContentLoaded', function () {
    initializeDataTable();
    initializeLogDataEditors();
});

function initializeDataTable() {
    const table = document.getElementById("ticketsTable");
    if (table) {
        $(table).DataTable({
            "pageLength": 10,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "columnDefs": [
                { "orderable": false, "targets": -1 }
            ]
        });
    }
}

let addLogDataEditor, editLogDataEditor;
let currentUserId;
let logDataTable;

function setUserId(userId) {
    currentUserId = userId;
}

function get_user_id() {
    return currentUserId;
}

function initializeLogDataEditors() {
    addLogDataEditor = ace.edit("addLogDataEditor");
    addLogDataEditor.setTheme("ace/theme/monokai");
    addLogDataEditor.session.setMode("ace/mode/json");

    editLogDataEditor = ace.edit("editLogDataEditor");
    editLogDataEditor.setTheme("ace/theme/monokai");
    editLogDataEditor.session.setMode("ace/mode/json");
}

function addTicket() {
    const form = document.getElementById('addTicketForm');
    const formData = new FormData(form);

    formData.append('log_data', addLogDataEditor.getValue());

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    formData.append('csrf_token', csrfToken);

    fetch('/add_ticket.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Success', data.message, 'success').then(() => {
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

function populateEditTicketModal(ticketId) {
    fetch(`/get_ticket_details.php?id=${ticketId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ticket = data.data;
                document.getElementById('editTicketId').value = ticket.ticket_id;
                document.getElementById('editTitle').value = ticket.title;
                document.getElementById('editType').value = ticket.type;
                document.getElementById('editDescription').value = ticket.description;
                document.getElementById('editStatus').value = ticket.status;
                document.getElementById('editPriority').value = ticket.priority;
                document.getElementById('editAssignedTo').value = ticket.assigned_to || '';
                document.getElementById('editDepartment').value = ticket.department_id || '';
                document.getElementById('editAgent').value = ticket.agent_id || '';

                let formattedLogData = '';
                try {
                    const logDataObj = JSON.parse(ticket.log_data);
                    formattedLogData = JSON.stringify(logDataObj, null, 2);
                } catch (e) {
                    console.error('Error parsing log data:', e);
                    formattedLogData = ticket.log_data || '';
                }

                editLogDataEditor.setValue(formattedLogData);
                editLogDataEditor.clearSelection();

                // Populate comments
                const commentsSection = document.getElementById('commentsSection');
                commentsSection.innerHTML = '';
                ticket.comments.forEach(comment => {
                    const commentDiv = document.createElement('div');
                    commentDiv.className = 'mb-2';
                    commentDiv.innerHTML = `
            <strong>${comment.username}</strong> (${new Date(comment.created_at).toLocaleString()}):
            <br>${comment.comment}
            ${comment.user_id == get_user_id() ?
                            `<br><button class="btn btn-sm btn-primary mt-1" onclick="editComment(event, ${comment.comment_id}, '${comment.comment.replace(/'/g, "\\'")}')">Edit</button>` : ''}
        `;
                    commentsSection.appendChild(commentDiv);
                });

                // Show/hide new comment section based on user's role
                const newCommentSection = document.getElementById('newCommentSection');
                if (get_user_id() == ticket.created_by || get_user_id() == ticket.assigned_to) {
                    newCommentSection.style.display = 'block';
                } else {
                    newCommentSection.style.display = 'none';
                }

                // Populate attachments
                const attachmentsSection = document.getElementById('attachmentsSection');
                attachmentsSection.innerHTML = '';
                ticket.attachments.forEach(attachment => {
                    const attachmentDiv = document.createElement('div');
                    attachmentDiv.className = 'mb-2';
                    attachmentDiv.innerHTML = `
            <a href="/download_attachment.php?id=${attachment.attachment_id}" target="_blank">${attachment.file_name}</a>
            (${formatFileSize(attachment.file_size)})
            <button class="btn btn-sm btn-danger" onclick="deleteAttachment(${attachment.attachment_id}, event)">Delete</button>
        `;
                    attachmentsSection.appendChild(attachmentDiv);
                });

                new bootstrap.Modal(document.getElementById('editTicketModal')).show();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An unexpected error occurred', 'error');
        });
}

function updateTicket() {
    const form = document.getElementById('editTicketForm');
    const formData = new FormData(form);

    formData.append('log_data', editLogDataEditor.getValue());

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    formData.append('csrf_token', csrfToken);

    fetch('/update_ticket.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Success', data.message, 'success').then(() => {
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

function deleteTicket(ticketId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/delete_ticket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ticket_id: ticketId,
                    csrf_token: csrfToken
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', data.message, 'success').then(() => {
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

function viewTicket(ticketId) {
    fetch(`/get_ticket_details.php?id=${ticketId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ticket = data.data;
                document.getElementById('viewTicketTitle').textContent = ticket.title;
                document.getElementById('viewTicketType').textContent = ticket.type;
                document.getElementById('viewTicketStatus').textContent = ticket.status;
                document.getElementById('viewTicketPriority').textContent = ticket.priority;
                document.getElementById('viewTicketAssignedTo').textContent = ticket.assigned_to_name || 'Unassigned';
                document.getElementById('viewTicketDepartment').textContent = ticket.department_name || 'N/A';
                document.getElementById('viewTicketAgent').textContent = ticket.agent_name || 'N/A';
                document.getElementById('viewTicketDescription').textContent = ticket.description;

                // Initialize DataTable for log data
                if (logDataTable) {
                    logDataTable.destroy();
                }

                try {
                    const logDataObj = JSON.parse(ticket.log_data);
                    const flattenedData = flattenObject(logDataObj);
                    const tableData = Object.entries(flattenedData).map(([key, value]) => [key, JSON.stringify(value)]);

                    logDataTable = $('#viewTicketLogData').DataTable({
                        data: tableData,
                        columns: [
                            { title: "Key", width: "30%" },
                            { title: "Value", width: "70%" }
                        ],
                        paging: false,
                        scrollY: "350px",
                        scrollCollapse: true,
                        searching: true,
                        info: false,
                        ordering: true
                    });
                } catch (e) {
                    console.error('Error parsing log data:', e);
                    logDataTable = $('#viewTicketLogData').DataTable({
                        data: [["Error", "Invalid JSON data"], ["Raw", ticket.log_data]],
                        columns: [
                            { title: "Key", width: "30%" },
                            { title: "Value", width: "70%" }
                        ],
                        paging: false,
                        scrollY: "350px",
                        scrollCollapse: true,
                        searching: false,
                        info: false,
                        ordering: false
                    });
                }

                // Populate comments
                const commentsSection = document.getElementById('viewTicketComments');
                commentsSection.innerHTML = ticket.comments.map(comment => `
                    <div class="mb-2">
                        <strong>${comment.username}</strong> (${new Date(comment.created_at).toLocaleString()}):
                        <br>${comment.comment}
                    </div>
                `).join('');

                // Populate attachments
                const attachmentsSection = document.getElementById('viewTicketAttachments');
                attachmentsSection.innerHTML = ticket.attachments.map(attachment => `
                    <div class="mb-2">
                        <a href="#" onclick="downloadAttachment(${attachment.attachment_id}, '${attachment.file_name}'); return false;">
                            ${attachment.file_name}
                        </a>
                        (${formatFileSize(attachment.file_size)})
                    </div>
                `).join('');

                new bootstrap.Modal(document.getElementById('viewTicketModal')).show();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'An unexpected error occurred', 'error');
        });
}

// Helper function to flatten nested objects
function flattenObject(obj, prefix = '') {
    return Object.keys(obj).reduce((acc, k) => {
        const pre = prefix.length ? prefix + '.' : '';
        if (typeof obj[k] === 'object' && obj[k] !== null && !Array.isArray(obj[k])) {
            Object.assign(acc, flattenObject(obj[k], pre + k));
        } else {
            acc[pre + k] = obj[k];
        }
        return acc;
    }, {});
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Helper function to flatten nested objects
function flattenObject(obj, prefix = '') {
    return Object.keys(obj).reduce((acc, k) => {
        const pre = prefix.length ? prefix + '.' : '';
        if (typeof obj[k] === 'object' && obj[k] !== null && !Array.isArray(obj[k])) {
            Object.assign(acc, flattenObject(obj[k], pre + k));
        } else {
            acc[pre + k] = obj[k];
        }
        return acc;
    }, {});
}

function deleteAttachment(attachmentId, event) {
    event.preventDefault();
    event.stopPropagation();

    const editTicketModal = document.getElementById('editTicketModal');
    const modal = bootstrap.Modal.getInstance(editTicketModal);
    modal.hide();

    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to recover this attachment!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/delete_attachment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    attachment_id: attachmentId,
                    csrf_token: csrfToken
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', data.message, 'success').then(() => {
                            const ticketId = document.getElementById('editTicketId').value;
                            populateEditTicketModal(ticketId);
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An unexpected error occurred', 'error');
                });
        } else {
            // If the user cancels, show the edit ticket modal again
            modal.show();
        }
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function editComment(event, commentId, currentComment) {
    const editTicketModal = document.getElementById('editTicketModal');
    const modal = bootstrap.Modal.getInstance(editTicketModal);
    modal.hide();
    event.preventDefault();
    event.stopPropagation();

    Swal.fire({
        title: 'Edit Comment',
        input: 'textarea',
        inputValue: currentComment,
        showCancelButton: true,
        confirmButtonText: 'Update',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) {
                return 'You need to write something!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/update_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    comment_id: commentId,
                    comment: result.value,
                    csrf_token: csrfToken
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Updated!', 'Your comment has been updated.', 'success').then(() => {
                            const ticketId = document.getElementById('editTicketId').value;
                            populateEditTicketModal(ticketId);
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An unexpected error occurred', 'error');
                });
        } else {
            // If the user cancels, show the edit ticket modal again
            modal.show();
        }
    });
}

function downloadAttachment(attachmentId, fileName) {
    fetch(`/download_attachment.php?id=${attachmentId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => {
            console.error('Download failed:', error);
            Swal.fire('Error', 'Failed to download the attachment', 'error');
        });
}

// Event listeners for buttons
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('edit-ticket-btn')) {
        const ticketId = e.target.getAttribute('data-ticket-id');
        populateEditTicketModal(ticketId);
    } else if (e.target.classList.contains('delete-ticket-btn')) {
        const ticketId = e.target.getAttribute('data-ticket-id');
        deleteTicket(ticketId);
    } else if (e.target.classList.contains('view-ticket-btn')) {
        const ticketId = e.target.getAttribute('data-ticket-id');
        viewTicket(ticketId);
    }
});


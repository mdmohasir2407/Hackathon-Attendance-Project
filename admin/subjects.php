<?php
require_once 'includes/header.php';

// Fetch departments for form
$dept_stmt = $pdo->query("SELECT * FROM departments ORDER BY name ASC");
$departments = $dept_stmt->fetchAll();

// Fetch semesters for form
$sem_stmt = $pdo->query("SELECT s.*, ay.name as ay_name FROM semesters s JOIN academic_years ay ON s.academic_year_id = ay.id ORDER BY ay.start_date DESC, s.name ASC");
$semesters = $sem_stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Subjects Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
        <i class="bi bi-plus-lg"></i> Add Subject
    </button>
</div>

<!-- Alert for AJAX responses -->
<div id="alert-container"></div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="subjectsTable">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Subject Name</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Credits</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Subjects will be loaded here via AJAX -->
                    <tr><td colspan="8" class="text-center">Loading subjects...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSubjectForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Subject Code</label>
                        <input type="text" class="form-control" id="code" name="code" required placeholder="e.g., MCA306">
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Subject Name</label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., Artificial Intelligence">
                    </div>

                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id" required>
                            <option value="">Select Department...</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="semester_id" class="form-label">Semester</label>
                        <select class="form-select" id="semester_id" name="semester_id" required>
                            <option value="">Select Semester...</option>
                            <?php foreach($semesters as $sem): ?>
                                <option value="<?php echo $sem['id']; ?>"><?php echo htmlspecialchars($sem['name'] . ' - ' . $sem['ay_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="credits" class="form-label">Credits</label>
                        <input type="number" class="form-control" id="credits" name="credits" min="1" max="10" value="3" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadSubjects();

    function loadSubjects() {
        $.ajax({
            url: '../api/subjects.php?action=list',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    var html = '';
                    if(response.data.length === 0) {
                        html = '<tr><td colspan="8" class="text-center">No subjects found.</td></tr>';
                    } else {
                        $.each(response.data, function(index, subject) {
                            var statusBadge = subject.status == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
                            var toggleAction = subject.status == 1 ? 'deactivate' : 'activate';
                            var toggleIcon = subject.status == 1 ? 'bi-x-circle' : 'bi-check-circle';
                            var toggleBtnClass = subject.status == 1 ? 'btn-outline-warning' : 'btn-outline-success';

                            html += '<tr>' +
                                '<td>' + subject.id + '</td>' +
                                '<td>' + $('<div>').text(subject.code).html() + '</td>' +
                                '<td>' + $('<div>').text(subject.name).html() + '</td>' +
                                '<td>' + $('<div>').text(subject.department_name).html() + '</td>' +
                                '<td>' + $('<div>').text(subject.semester_name).html() + '</td>' +
                                '<td>' + subject.credits + '</td>' +
                                '<td>' + statusBadge + '</td>' +
                                '<td>' +
                                    '<button class="btn btn-sm ' + toggleBtnClass + ' toggle-status-btn me-1" data-id="' + subject.id + '" data-action="' + toggleAction + '" title="Toggle Status"><i class="bi ' + toggleIcon + '"></i></button>' +
                                    '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' + subject.id + '" title="Delete"><i class="bi bi-trash"></i></button>' +
                                '</td>' +
                            '</tr>';
                        });
                    }
                    $('#subjectsTable tbody').html(html);
                }
            }
        });
    }

    function showAlert(type, message) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                        message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>';
        $('#alert-container').html(alertHtml);
        setTimeout(function(){ $('.alert').alert('close'); }, 3000);
    }

    $('#addSubjectForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../api/subjects.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addSubjectModal').modal('hide');
                    $('#addSubjectForm')[0].reset();
                    showAlert('success', response.message);
                    loadSubjects();
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while saving the subject.');
            }
        });
    });

    $(document).on('click', '.toggle-status-btn', function() {
        var id = $(this).data('id');
        var action = $(this).data('action');
        $.ajax({
            url: '../api/subjects.php',
            type: 'POST',
            data: { action: 'toggle_status', id: id, status_action: action },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    showAlert('success', response.message);
                    loadSubjects();
                } else {
                    showAlert('danger', response.message);
                }
            }
        });
    });

    $(document).on('click', '.delete-btn', function() {
        if(confirm("Are you sure you want to delete this subject?")) {
            var id = $(this).data('id');
            $.ajax({
                url: '../api/subjects.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        showAlert('success', response.message);
                        loadSubjects();
                    } else {
                        showAlert('danger', response.message);
                    }
                }
            });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

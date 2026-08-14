<?php
require_once 'includes/header.php';

$teacher_id = $_SESSION['user_id'];

// Fetch classes/subjects for this teacher for the form
$stmt = $pdo->prepare("
    SELECT ts.*, c.name as class_name, d.name as dept_name, s.name as subject_name, s.code as subject_code
    FROM teacher_subjects ts
    JOIN classes c ON ts.class_id = c.id
    JOIN departments d ON c.department_id = d.id
    JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.teacher_id = ?
    ORDER BY d.name, c.name, s.name
");
$stmt->execute([$teacher_id]);
$assignments_options = $stmt->fetchAll();

// Handle Add Assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $assignment_val = explode('|', $_POST['assignment']);
    $class_id = $assignment_val[0] ?? null;
    $subject_id = $assignment_val[1] ?? null;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $deadline = $_POST['deadline'];
    
    if ($class_id && $subject_id && !empty($title) && !empty($deadline)) {
        $file_path = null;
        
        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip'];
            $filename = $_FILES['attachment']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if ($_FILES['attachment']['size'] <= 5000000) { // 5MB limit
                    $new_filename = uniqid('assgn_') . '.' . $ext;
                    $upload_dir = '../assets/uploads/assignments/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $new_filename)) {
                        $file_path = $new_filename;
                    } else {
                        $error_msg = "Failed to upload file.";
                    }
                } else {
                    $error_msg = "File is too large. Limit is 5MB.";
                }
            } else {
                $error_msg = "Invalid file type. Allowed: PDF, DOC/X, PPT/X, ZIP.";
            }
        }
        
        if (!isset($error_msg)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO assignments (teacher_id, subject_id, class_id, title, description, deadline, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$teacher_id, $subject_id, $class_id, $title, $description, $deadline, $file_path]);
                $success_msg = "Assignment created successfully.";
            } catch (PDOException $e) {
                $error_msg = "Database error while creating assignment.";
            }
        }
    } else {
        $error_msg = "Please fill all required fields.";
    }
}

// Fetch created assignments
$stmt = $pdo->prepare("
    SELECT a.*, c.name as class_name, s.name as subject_name,
           (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as submission_count,
           (SELECT COUNT(*) FROM enrollments WHERE class_id = a.class_id) as total_students
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    JOIN subjects s ON a.subject_id = s.id
    WHERE a.teacher_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$teacher_id]);
$assignments_list = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Assignments Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssignmentModal">
        <i class="bi bi-plus-lg"></i> Create Assignment
    </button>
</div>

<?php if(isset($success_msg)): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <?php echo $success_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(isset($error_msg)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <?php echo $error_msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <?php foreach($assignments_list as $a): ?>
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="badge bg-primary"><?php echo htmlspecialchars($a['subject_name']); ?></span>
                <small class="text-muted">Due: <?php echo date('M d, Y h:i A', strtotime($a['deadline'])); ?></small>
            </div>
            <div class="card-body">
                <h5 class="card-title text-truncate"><?php echo htmlspecialchars($a['title']); ?></h5>
                <h6 class="card-subtitle mb-2 text-muted">Class: <?php echo htmlspecialchars($a['class_name']); ?></h6>
                <p class="card-text small text-truncate-2"><?php echo nl2br(htmlspecialchars($a['description'])); ?></p>
                
                <?php if($a['file_path']): ?>
                    <a href="../assets/uploads/assignments/<?php echo $a['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="bi bi-paperclip"></i> View Attachment
                    </a>
                <?php endif; ?>
                
                <div class="progress mb-2" style="height: 20px;">
                    <?php 
                        $pct = $a['total_students'] > 0 ? round(($a['submission_count'] / $a['total_students']) * 100) : 0;
                        $bg = $pct == 100 ? 'bg-success' : 'bg-info';
                    ?>
                    <div class="progress-bar <?php echo $bg; ?>" role="progressbar" style="width: <?php echo $pct; ?>%;" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $pct; ?>%</div>
                </div>
                <div class="small text-muted d-flex justify-content-between">
                    <span><?php echo $a['submission_count']; ?> of <?php echo $a['total_students']; ?> Submitted</span>
                    <a href="#" class="text-decoration-none">View Submissions <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($assignments_list)): ?>
        <div class="col-12">
            <div class="alert alert-info shadow-sm text-center py-4">
                <i class="bi bi-journal-text fs-1 d-block mb-2"></i>
                No assignments created yet.
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Assignment Modal -->
<div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="assignments.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="assignment" class="form-label">Class & Subject</label>
                            <select class="form-select" id="assignment" name="assignment" required>
                                <option value="">-- Choose --</option>
                                <?php foreach ($assignments_options as $opt): ?>
                                    <option value="<?php echo $opt['class_id'] . '|' . $opt['subject_id']; ?>">
                                        <?php echo htmlspecialchars($opt['class_name'] . ' - ' . $opt['subject_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="deadline" class="form-label">Deadline</label>
                            <input type="datetime-local" class="form-control" id="deadline" name="deadline" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Assignment Title</label>
                        <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Chapter 4 Essay">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Instructions / Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter detailed instructions here..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="attachment" class="form-label">Attachment (Optional)</label>
                        <input class="form-control" type="file" id="attachment" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip">
                        <div class="form-text">Max size: 5MB. Allowed: PDF, DOC, PPT, ZIP.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

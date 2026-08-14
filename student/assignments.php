<?php
require_once 'includes/header.php';

$student_id = $_SESSION['user_id'];

// Get student's class
$stmt = $pdo->prepare("SELECT class_id FROM enrollments WHERE student_id = ?");
$stmt->execute([$student_id]);
$enrollment = $stmt->fetch();
$class_id = $enrollment ? $enrollment['class_id'] : null;

// Handle File Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'submit') {
    $assignment_id = $_POST['assignment_id'];
    
    // Check if deadline passed
    $chk = $pdo->prepare("SELECT deadline FROM assignments WHERE id = ?");
    $chk->execute([$assignment_id]);
    $assgn = $chk->fetch();
    $status = (strtotime($assgn['deadline']) < time()) ? 'LATE' : 'SUBMITTED';

    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'zip'];
        $filename = $_FILES['submission_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            if ($_FILES['submission_file']['size'] <= 5000000) { // 5MB
                $new_filename = uniqid('sub_') . '_' . $student_id . '.' . $ext;
                $upload_dir = '../assets/uploads/submissions/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $upload_dir . $new_filename)) {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO assignment_submissions (assignment_id, student_id, file_path, status) 
                            VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), submission_time = CURRENT_TIMESTAMP, status = VALUES(status)
                        ");
                        $stmt->execute([$assignment_id, $student_id, $new_filename, $status]);
                        
                        // Give XP if it's not late (only if they haven't been awarded yet)
                        if ($status != 'LATE') {
                            $chk_xp = $pdo->prepare("SELECT id FROM student_achievements WHERE student_id = ? AND achievement_name = ?");
                            $chk_xp->execute([$student_id, 'Assignment Submitted: ' . $assgn['title']]);
                            if (!$chk_xp->fetch()) {
                                $xp_stmt = $pdo->prepare("INSERT INTO student_achievements (student_id, achievement_name, xp_points) VALUES (?, ?, 50)");
                                $xp_stmt->execute([$student_id, 'Assignment Submitted: ' . $assgn['title']]);
                            }
                        }

                        $success_msg = "Assignment submitted successfully.";
                    } catch (PDOException $e) {
                        $error_msg = "Database error during submission.";
                    }
                } else {
                    $error_msg = "Failed to upload file.";
                }
            } else {
                $error_msg = "File is too large. Limit is 5MB.";
            }
        } else {
            $error_msg = "Invalid file type. Allowed: PDF, DOC/X, ZIP.";
        }
    } else {
        $error_msg = "Please select a file to upload.";
    }
}

$assignments = [];
if ($class_id) {
    // Fetch assignments with submission status
    $stmt = $pdo->prepare("
        SELECT a.*, s.name as subject_name, s.code as subject_code,
               sub.id as submission_id, sub.file_path as sub_file, sub.status as sub_status, sub.submission_time
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
        WHERE a.class_id = ?
        ORDER BY a.deadline ASC
    ");
    $stmt->execute([$student_id, $class_id]);
    $assignments = $stmt->fetchAll();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Assignments</h1>
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

<?php if (!$class_id): ?>
    <div class="alert alert-warning">You are not enrolled in any class to view assignments.</div>
<?php else: ?>
    <ul class="nav nav-pills mb-4" id="assignmentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">Pending / Due Soon</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="submitted-tab" data-bs-toggle="tab" data-bs-target="#submitted" type="button" role="tab">Submitted</button>
        </li>
    </ul>

    <div class="tab-content" id="assignmentTabsContent">
        <!-- PENDING TAB -->
        <div class="tab-pane fade show active" id="pending" role="tabpanel">
            <div class="row">
                <?php 
                $has_pending = false;
                foreach($assignments as $a): 
                    if(!$a['submission_id']): 
                        $has_pending = true;
                        $is_overdue = (strtotime($a['deadline']) < time());
                        $card_border = $is_overdue ? 'border-danger' : 'border-primary';
                ?>
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card shadow-sm h-100 <?php echo $card_border; ?>">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($a['subject_code']); ?></span>
                            <?php if($is_overdue): ?>
                                <span class="badge bg-danger">OVERDUE</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">DUE SOON</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-truncate" title="<?php echo htmlspecialchars($a['title']); ?>"><?php echo htmlspecialchars($a['title']); ?></h5>
                            <p class="card-text small text-muted text-truncate-2 flex-grow-1"><?php echo nl2br(htmlspecialchars($a['description'])); ?></p>
                            
                            <div class="mb-3">
                                <small class="text-muted d-block"><i class="bi bi-clock-history"></i> Deadline:</small>
                                <strong class="<?php echo $is_overdue ? 'text-danger' : ''; ?>"><?php echo date('D, M d, Y - h:i A', strtotime($a['deadline'])); ?></strong>
                            </div>

                            <?php if($a['file_path']): ?>
                                <a href="../assets/uploads/assignments/<?php echo $a['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-info mb-3">
                                    <i class="bi bi-download"></i> Download Resource
                                </a>
                            <?php endif; ?>
                            
                            <button class="btn btn-primary mt-auto" data-bs-toggle="modal" data-bs-target="#submitModal<?php echo $a['id']; ?>">
                                <i class="bi bi-cloud-arrow-up"></i> Submit Work
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit Modal -->
                <div class="modal fade" id="submitModal<?php echo $a['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Submit: <?php echo htmlspecialchars($a['title']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="assignments.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="submit">
                                    <input type="hidden" name="assignment_id" value="<?php echo $a['id']; ?>">
                                    <?php if($is_overdue): ?>
                                        <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle-fill"></i> Warning: This assignment is past the deadline. It will be marked as LATE.</div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label class="form-label">Upload File</label>
                                        <input class="form-control" type="file" name="submission_file" accept=".pdf,.doc,.docx,.zip" required>
                                        <div class="form-text">Max size: 5MB. Allowed: PDF, DOC, ZIP.</div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success">Upload & Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; endforeach; ?>
                
                <?php if(!$has_pending): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-emoji-smile fs-1 text-success d-block mb-3"></i>
                        <h5>All caught up!</h5>
                        <p class="text-muted">You have no pending assignments.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SUBMITTED TAB -->
        <div class="tab-pane fade" id="submitted" role="tabpanel">
            <div class="row">
                <?php 
                $has_submitted = false;
                foreach($assignments as $a): 
                    if($a['submission_id']): 
                        $has_submitted = true;
                ?>
                <div class="col-md-6 col-xl-4 mb-4">
                    <div class="card shadow-sm h-100 border-success border-opacity-50">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($a['subject_code']); ?></span>
                            <?php if($a['sub_status'] == 'LATE'): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock-history"></i> LATE SUBMISSION</span>
                            <?php else: ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> SUBMITTED</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-truncate"><?php echo htmlspecialchars($a['title']); ?></h5>
                            <hr>
                            <div class="small mb-2">
                                <span class="text-muted">Submitted on:</span><br>
                                <strong><?php echo date('M d, Y h:i A', strtotime($a['submission_time'])); ?></strong>
                            </div>
                            <a href="../assets/uploads/submissions/<?php echo $a['sub_file']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-file-earmark-check"></i> View My Submission
                            </a>
                            
                            <button class="btn btn-sm btn-link text-decoration-none mt-2 d-block p-0" data-bs-toggle="modal" data-bs-target="#submitModal<?php echo $a['id']; ?>">
                                Resubmit (Overwrite)
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Resubmit Modal -->
                <div class="modal fade" id="submitModal<?php echo $a['id']; ?>" tabindex="-1" aria-hidden="true">
                    <!-- Same structure as submit modal -->
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Resubmit: <?php echo htmlspecialchars($a['title']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="assignments.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="submit">
                                    <input type="hidden" name="assignment_id" value="<?php echo $a['id']; ?>">
                                    <div class="alert alert-info py-2">Resubmitting will overwrite your previous file.</div>
                                    <div class="mb-3">
                                        <label class="form-label">Upload New File</label>
                                        <input class="form-control" type="file" name="submission_file" accept=".pdf,.doc,.docx,.zip" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success">Resubmit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; endforeach; ?>
                
                <?php if(!$has_submitted): ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">You haven't submitted any assignments yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

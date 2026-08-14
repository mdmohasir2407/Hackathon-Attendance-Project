<?php
require_once 'includes/header.php';

$teacher_id = $_SESSION['user_id'];
$pre_select_student = $_GET['student_id'] ?? '';

// Fetch classes/subjects for this teacher
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
$assignments = $stmt->fetchAll();

// Group subjects uniquely for the form dropdown
$unique_subjects = [];
foreach($assignments as $a) {
    $unique_subjects[$a['subject_id']] = $a;
}

// Fetch all students taught by this teacher
$stmt = $pdo->prepare("
    SELECT DISTINCT s.id, s.roll_number, s.first_name, s.last_name, c.name as class_name
    FROM students s
    JOIN enrollments e ON s.id = e.student_id
    JOIN classes c ON e.class_id = c.id
    JOIN teacher_subjects ts ON c.id = ts.class_id
    WHERE ts.teacher_id = ?
    ORDER BY c.name, s.roll_number
");
$stmt->execute([$teacher_id]);
$students = $stmt->fetchAll();

// Handle Feedback Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'send') {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $feedback_type = $_POST['feedback_type'];
    $note = substr(trim($_POST['note']), 0, 50); // Enforce max 50 chars in PHP too
    
    if ($student_id && $subject_id && $feedback_type) {
        try {
            $pdo->beginTransaction();
            
            // Insert Feedback
            $stmt = $pdo->prepare("INSERT INTO feedback (teacher_id, student_id, subject_id, feedback_type, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$teacher_id, $student_id, $subject_id, $feedback_type, $note]);
            
            // Generate Notification for Student
            // Fetch subject name for notification
            $subj_stmt = $pdo->prepare("SELECT name FROM subjects WHERE id = ?");
            $subj_stmt->execute([$subject_id]);
            $subj_name = $subj_stmt->fetchColumn();
            
            $notif_title = "New Feedback: $subj_name";
            $notif_message = "Teacher feedback ($feedback_type): " . ($note ? $note : 'No extra notes.');
            
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'Feedback')");
            $notif_stmt->execute([$student_id, $notif_title, $notif_message]);
            
            $pdo->commit();
            $success_msg = "Smart feedback sent successfully and student notified.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Database error while sending feedback.";
        }
    } else {
        $error_msg = "Student, Subject, and Feedback Type are required.";
    }
}

// Fetch recently sent feedback
$stmt = $pdo->prepare("
    SELECT f.*, s.first_name, s.last_name, s.roll_number, sub.name as subject_name
    FROM feedback f
    JOIN students s ON f.student_id = s.id
    JOIN subjects sub ON f.subject_id = sub.id
    WHERE f.teacher_id = ?
    ORDER BY f.created_at DESC LIMIT 10
");
$stmt->execute([$teacher_id]);
$recent_feedback = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Smart Teacher Feedback</h1>
</div>

<div class="row">
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <i class="bi bi-chat-left-text me-1"></i> Send Quick Feedback
            </div>
            <div class="card-body">
                <?php if(isset($success_msg)): ?>
                    <div class="alert alert-success py-2"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if(isset($error_msg)): ?>
                    <div class="alert alert-danger py-2"><?php echo $error_msg; ?></div>
                <?php endif; ?>
                
                <form action="feedback.php" method="POST">
                    <input type="hidden" name="action" value="send">
                    
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Select Student</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($pre_select_student == $s['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['roll_number'] . ' - ' . $s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['class_name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="subject_id" class="form-label">Subject</label>
                        <select class="form-select" id="subject_id" name="subject_id" required>
                            <option value="">-- Choose Subject --</option>
                            <?php foreach ($unique_subjects as $id => $opt): ?>
                                <option value="<?php echo $id; ?>">
                                    <?php echo htmlspecialchars($opt['subject_code'] . ' - ' . $opt['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Feedback Type</label>
                        <div class="d-flex gap-2">
                            <input type="radio" class="btn-check" name="feedback_type" id="type_excellent" value="Excellent" required>
                            <label class="btn btn-outline-success w-100 py-2" for="type_excellent">
                                <i class="bi bi-emoji-smile fs-4 d-block"></i> Excellent
                            </label>

                            <input type="radio" class="btn-check" name="feedback_type" id="type_improving" value="Improving">
                            <label class="btn btn-outline-info w-100 py-2" for="type_improving">
                                <i class="bi bi-graph-up-arrow fs-4 d-block"></i> Improving
                            </label>

                            <input type="radio" class="btn-check" name="feedback_type" id="type_needs" value="Needs Practice">
                            <label class="btn btn-outline-warning w-100 py-2" for="type_needs">
                                <i class="bi bi-exclamation-triangle fs-4 d-block"></i> Needs Practice
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="note" class="form-label">Short Note (Optional, max 50 chars)</label>
                        <input type="text" class="form-control" id="note" name="note" maxlength="50" placeholder="e.g., Focus on normalization.">
                        <div class="form-text text-end"><span id="charCount">0</span>/50</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Send Feedback</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <i class="bi bi-clock-history me-1"></i> Recently Sent Feedback
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Feedback</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_feedback as $f): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($f['first_name'] . ' ' . $f['last_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($f['roll_number']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($f['subject_name']); ?></td>
                                <td>
                                    <?php 
                                        $badge = 'bg-secondary';
                                        if($f['feedback_type'] == 'Excellent') $badge = 'bg-success';
                                        if($f['feedback_type'] == 'Improving') $badge = 'bg-info';
                                        if($f['feedback_type'] == 'Needs Practice') $badge = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $badge; ?>"><?php echo $f['feedback_type']; ?></span>
                                    <?php if($f['note']): ?>
                                        <div class="small mt-1 fst-italic text-muted">"<?php echo htmlspecialchars($f['note']); ?>"</div>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?php echo date('M d, g:i A', strtotime($f['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($recent_feedback)): ?>
                            <tr><td colspan="4" class="text-center py-4">No feedback sent recently.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#note').on('input', function() {
        $('#charCount').text($(this).val().length);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

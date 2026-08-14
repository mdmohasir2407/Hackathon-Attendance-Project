<?php
require_once 'includes/header.php';

$student_id = $_SESSION['user_id'];

// Fetch all feedback
$stmt = $pdo->prepare("
    SELECT f.*, s.name as subject_name, s.code as subject_code, t.first_name, t.last_name
    FROM feedback f
    JOIN subjects s ON f.subject_id = s.id
    JOIN teachers t ON f.teacher_id = t.id
    WHERE f.student_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$student_id]);
$feedback_list = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Teacher Feedback</h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Feedback Type</th>
                                <th>Note</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($feedback_list as $f): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold"><?php echo htmlspecialchars($f['subject_code']); ?></span><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($f['subject_name']); ?></small>
                                </td>
                                <td>Mr/Ms. <?php echo htmlspecialchars($f['last_name']); ?></td>
                                <td>
                                    <?php 
                                        $badge = 'bg-secondary';
                                        $icon = '';
                                        if($f['feedback_type'] == 'Excellent') { $badge = 'bg-success'; $icon = 'bi-emoji-smile'; }
                                        if($f['feedback_type'] == 'Improving') { $badge = 'bg-info'; $icon = 'bi-graph-up-arrow'; }
                                        if($f['feedback_type'] == 'Needs Practice') { $badge = 'bg-warning text-dark'; $icon = 'bi-exclamation-triangle'; }
                                    ?>
                                    <span class="badge <?php echo $badge; ?> fs-6"><i class="bi <?php echo $icon; ?>"></i> <?php echo $f['feedback_type']; ?></span>
                                </td>
                                <td class="fst-italic text-muted">
                                    <?php echo $f['note'] ? '"' . htmlspecialchars($f['note']) . '"' : '-'; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($f['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($feedback_list)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">You haven't received any feedback yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

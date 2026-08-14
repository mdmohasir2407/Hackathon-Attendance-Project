<?php
require_once 'includes/header.php';

$teacher_id = $_SESSION['user_id'];

// Fetch classes and subjects assigned to this teacher
$stmt = $pdo->prepare("
    SELECT ts.*, c.name as class_name, d.name as dept_name, s.name as subject_name, s.code as subject_code, s.credits
    FROM teacher_subjects ts
    JOIN classes c ON ts.class_id = c.id
    JOIN departments d ON c.department_id = d.id
    JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.teacher_id = ?
    ORDER BY d.name, c.name, s.name
");
$stmt->execute([$teacher_id]);
$assignments = $stmt->fetchAll();

// Group by Class
$grouped_classes = [];
foreach ($assignments as $row) {
    $class_key = $row['dept_name'] . ' - ' . $row['class_name'];
    $grouped_classes[$class_key][] = $row;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Classes & Subjects</h1>
</div>

<?php if (empty($grouped_classes)): ?>
    <div class="alert alert-info shadow-sm">
        <i class="bi bi-info-circle me-2"></i> You have not been assigned to any classes or subjects yet. Please contact the administrator.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($grouped_classes as $class_name => $subjects): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-easel me-2"></i> <?php echo htmlspecialchars($class_name); ?></h5>
                        <span class="badge bg-light text-dark rounded-pill"><?php echo count($subjects); ?> Subjects</span>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($subjects as $sub): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                                    <div>
                                        <h6 class="mb-0 text-primary"><?php echo htmlspecialchars($sub['subject_code']); ?></h6>
                                        <div class="fw-bold"><?php echo htmlspecialchars($sub['subject_name']); ?></div>
                                        <small class="text-muted"><?php echo $sub['credits']; ?> Credits</small>
                                    </div>
                                    <div>
                                        <a href="students.php?class_id=<?php echo $sub['class_id']; ?>" class="btn btn-sm btn-outline-secondary" title="View Students">
                                            <i class="bi bi-people"></i>
                                        </a>
                                        <a href="qr_attendance.php?class_id=<?php echo $sub['class_id']; ?>&subject_id=<?php echo $sub['subject_id']; ?>" class="btn btn-sm btn-outline-primary" title="Take Attendance">
                                            <i class="bi bi-qr-code"></i>
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
